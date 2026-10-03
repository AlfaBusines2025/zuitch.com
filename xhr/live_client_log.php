<?php
/**
 * Registro de diagnóstico de /livestream (guardar / no guardar en vivo).
 * Archivos: cache/live_client_logs/live_YYYY-MM-DD.log
 * Requiere sesión iniciada + hash válido.
 */
if ($f == 'live_client_log') {
    header('Content-Type: application/json; charset=utf-8');

    if ($wo['loggedin'] != true || Wo_CheckMainSession($hash_id) !== true) {
        echo json_encode(array('status' => 403, 'error' => 'auth'));
        exit;
    }

    $log_dir = dirname(__DIR__) . '/cache/live_client_logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0750, true);
    }
    $log_file = $log_dir . '/live_' . gmdate('Y-m-d') . '.log';

    $raw = file_get_contents('php://input');
    $body = array();
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }

    $events = array();
    if (!empty($body['events']) && is_array($body['events'])) {
        $events = $body['events'];
    } elseif (!empty($body['type']) || isset($body['message'])) {
        $events = array($body);
    }

    if (count($events) > 40) {
        $events = array_slice($events, 0, 40);
    }

    $ip = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $ua_server = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    $uid = (int) $wo['user']['user_id'];

    $truncate = function ($str, $max) {
        $str = (string) $str;
        if (function_exists('mb_substr')) {
            return mb_strlen($str) > $max ? mb_substr($str, 0, $max) . '…' : $str;
        }
        return strlen($str) > $max ? substr($str, 0, $max) . '…' : $str;
    };

    $isIosWvUa = function ($ua) {
        $ua = (string) $ua;
        if (!preg_match('/iPhone|iPad|iPod/i', $ua)) {
            return false;
        }
        if (preg_match('/Mobile\//i', $ua) && !preg_match('/Safari\//i', $ua)) {
            return true;
        }
        return false;
    };

    $lines = array();
    foreach ($events as $ev) {
        if (!is_array($ev)) {
            continue;
        }
        $type = isset($ev['type']) ? preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) $ev['type']) : 'unknown';
        if ($type === '') {
            $type = 'unknown';
        }
        $level = isset($ev['level']) ? (string) $ev['level'] : 'info';
        if (!in_array($level, array('info', 'warn', 'error'), true)) {
            $level = 'info';
        }
        $msg = isset($ev['message']) ? $truncate(strip_tags((string) $ev['message']), 2000) : '';
        $post_id = isset($ev['postId']) ? (int) $ev['postId'] : (isset($ev['post_id']) ? (int) $ev['post_id'] : 0);

        $client = array();
        if (!empty($ev['client']) && is_array($ev['client'])) {
            $client = $ev['client'];
        }

        $ctx = null;
        if (array_key_exists('context', $ev)) {
            if (is_array($ev['context']) || is_object($ev['context'])) {
                $ctx = $ev['context'];
            } elseif (is_string($ev['context'])) {
                $ctx = $truncate($ev['context'], 4000);
            } elseif (is_scalar($ev['context'])) {
                $ctx = (string) $ev['context'];
            }
        }

        $clientUa = isset($client['ua']) ? (string) $client['ua'] : $ua_server;
        /* iOS WebView: tracking más completo (flip/proxy/recording). */
        $forceKeep = $isIosWvUa($clientUa) || $isIosWvUa($ua_server);
        /* Siempre guardar cierres / fallos de guardado. */
        $always = in_array($type, array(
            'live_stop',
            'live_stop_server',
            'live_golive',
            'live_save_decision',
            'live_normalize_result',
            'live_pagehide_stop',
            'live_mp4_missing',
            'live_client_rec_upload',
            'live_cam_proxy_start',
            'live_camera_flip_proxy_ok',
            'live_camera_flip_fail',
            'live_camera_flip_apply_fail',
            'live_camera_flip_apply_ok',
            'live_camera_flip_gum_ok',
            'live_camera_flip_gum_retry',
            'live_camera_flip_release',
            'live_camera_flip_click',
            'live_camera_flip_ok',
        ), true);
        if (!$forceKeep && !$always) {
            continue;
        }

        $row = array(
            'ts_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => $level,
            'type' => $type,
            'message' => $msg,
            'post_id' => $post_id,
            'user_id' => $uid,
            'username' => isset($wo['user']['username']) ? (string) $wo['user']['username'] : '',
            'ip' => $truncate($ip, 45),
            'http_user_agent' => $truncate($ua_server, 600),
            'client' => $client,
            'context' => $ctx,
        );

        $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line !== false && strlen($line) > 20000) {
            $row['context'] = array('truncated' => true);
            $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($line !== false) {
            $lines[] = $line;
            // #region agent log
            /* Dual-write flip/debug events for Cursor session cadeb8 (mobile cannot reach localhost:7513). */
            if (strpos($type, 'live_camera_flip') === 0 || $type === 'live_golive') {
                $dbgPath = dirname(dirname(__DIR__)) . '/.cursor/debug-cadeb8.log';
                $dbgRow = array(
                    'sessionId' => 'cadeb8',
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'location' => 'live_client_log.php',
                    'message' => $type,
                    'hypothesisId' => (is_array($ctx) && isset($ctx['hypothesisId'])) ? $ctx['hypothesisId'] : 'H-A',
                    'runId' => (is_array($ctx) && isset($ctx['runId'])) ? $ctx['runId'] : 'pre-fix',
                    'data' => array(
                        'type' => $type,
                        'level' => $level,
                        'msg' => $msg,
                        'post_id' => $post_id,
                        'user_id' => $uid,
                        'username' => isset($wo['user']['username']) ? (string) $wo['user']['username'] : '',
                        'context' => $ctx,
                    ),
                );
                $dbgLine = json_encode($dbgRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($dbgLine !== false) {
                    @file_put_contents($dbgPath, $dbgLine . "\n", FILE_APPEND | LOCK_EX);
                }
            }
            // #endregion
        }
    }

    if (!empty($lines)) {
        @file_put_contents($log_file, implode("\n", $lines) . "\n", FILE_APPEND | LOCK_EX);
    }

    echo json_encode(array('status' => 200, 'written' => count($lines)));
    exit;
}
