<?php
/**
 * User activity audit (Reels / Livestream) — DB trail for allowlisted users.
 */

if (!defined('USER_ACTIVITY_AUDIT_ENABLED')) {
    require_once __DIR__ . '/user_activity_audit_config.php';
}

/**
 * @return int[]
 */
function Wo_UserActivityAuditUids()
{
    static $uids = null;
    if ($uids !== null) {
        return $uids;
    }
    $uids = array();
    if (!defined('USER_ACTIVITY_AUDIT_UIDS')) {
        return $uids;
    }
    foreach (explode(',', (string) USER_ACTIVITY_AUDIT_UIDS) as $part) {
        $id = (int) trim($part);
        if ($id > 0) {
            $uids[$id] = $id;
        }
    }
    return $uids;
}

/**
 * @param int $user_id
 * @return bool
 */
function Wo_IsUserActivityAudited($user_id)
{
    if (!defined('USER_ACTIVITY_AUDIT_ENABLED') || !USER_ACTIVITY_AUDIT_ENABLED) {
        return false;
    }
    $user_id = (int) $user_id;
    if ($user_id < 1) {
        return false;
    }
    $uids = Wo_UserActivityAuditUids();
    return isset($uids[$user_id]);
}

/**
 * @param int $post_id
 * @return bool
 */
function Wo_IsReelPostId($post_id)
{
    global $db;
    $post_id = (int) $post_id;
    if ($post_id < 1 || !defined('T_POSTS')) {
        return false;
    }
    try {
        if (isset($db) && is_object($db) && method_exists($db, 'where')) {
            $row = $db->where('id', $post_id)->getOne(T_POSTS, 'is_reel');
            if (!empty($row)) {
                if (is_object($row)) {
                    return !empty($row->is_reel) && (int) $row->is_reel === 1;
                }
                return !empty($row['is_reel']) && (int) $row['is_reel'] === 1;
            }
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

/**
 * @return string
 */
function Wo_UserActivityAuditClientIp()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        return substr(trim($parts[0]), 0, 45);
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return substr((string) $_SERVER['REMOTE_ADDR'], 0, 45);
    }
    return '';
}

/**
 * Register an audit row. Never throws; never breaks caller flow.
 *
 * @param int         $user_id
 * @param string      $feature  reels|live|other
 * @param string      $action   e.g. create, like, client.play
 * @param array       $opts     post_id, ref_id, level, meta (array|scalar)
 * @return bool
 */
function Wo_RegisterUserAuditLog($user_id, $feature, $action, $opts = array())
{
    global $sqlConnect;

    try {
        $user_id = (int) $user_id;
        if (!Wo_IsUserActivityAudited($user_id)) {
            return false;
        }
        if (!defined('T_USER_ACTIVITY_LOG')) {
            return false;
        }
        if (empty($sqlConnect)) {
            return false;
        }

        $feature = preg_replace('/[^a-z0-9_-]/i', '', (string) $feature);
        if ($feature === '') {
            $feature = 'other';
        }
        $feature = substr(strtolower($feature), 0, 32);

        $action = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) $action);
        if ($action === '') {
            $action = 'unknown';
        }
        $action = substr($action, 0, 64);

        $level = isset($opts['level']) ? (string) $opts['level'] : 'info';
        if (!in_array($level, array('info', 'warn', 'error'), true)) {
            $level = 'info';
        }

        $post_id = isset($opts['post_id']) ? (int) $opts['post_id'] : 0;
        $ref_id  = isset($opts['ref_id']) ? (int) $opts['ref_id'] : 0;

        $meta = '';
        if (array_key_exists('meta', $opts) && $opts['meta'] !== null) {
            if (is_array($opts['meta']) || is_object($opts['meta'])) {
                $meta = json_encode($opts['meta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $meta = (string) $opts['meta'];
            }
            if ($meta !== false && strlen($meta) > 8000) {
                $meta = substr($meta, 0, 8000) . '…';
            }
            if ($meta === false) {
                $meta = '';
            }
        }

        $ip = isset($opts['ip']) ? substr((string) $opts['ip'], 0, 45) : Wo_UserActivityAuditClientIp();
        $ua = isset($opts['ua'])
            ? substr((string) $opts['ua'], 0, 500)
            : (isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500) : '');

        $created_at = time();

        $feature_e = mysqli_real_escape_string($sqlConnect, $feature);
        $action_e  = mysqli_real_escape_string($sqlConnect, $action);
        $level_e   = mysqli_real_escape_string($sqlConnect, $level);
        $meta_e    = mysqli_real_escape_string($sqlConnect, $meta);
        $ip_e      = mysqli_real_escape_string($sqlConnect, $ip);
        $ua_e      = mysqli_real_escape_string($sqlConnect, $ua);

        $sql = "INSERT INTO `" . T_USER_ACTIVITY_LOG . "`
            (`user_id`,`feature`,`action`,`level`,`post_id`,`ref_id`,`meta`,`ip`,`ua`,`created_at`)
            VALUES
            ({$user_id},'{$feature_e}','{$action_e}','{$level_e}',{$post_id},{$ref_id},'{$meta_e}','{$ip_e}','{$ua_e}',{$created_at})";

        return (bool) @mysqli_query($sqlConnect, $sql);
    } catch (Exception $e) {
        return false;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Log reels.view with session dedupe.
 *
 * @param int $user_id
 * @param int $post_id
 * @return bool
 */
function Wo_RegisterUserAuditReelView($user_id, $post_id)
{
    $user_id = (int) $user_id;
    $post_id = (int) $post_id;
    if ($user_id < 1 || $post_id < 1 || !Wo_IsUserActivityAudited($user_id)) {
        return false;
    }
    $dedupe = defined('USER_ACTIVITY_AUDIT_VIEW_DEDUPE_SEC')
        ? (int) USER_ACTIVITY_AUDIT_VIEW_DEDUPE_SEC
        : 60;
    if ($dedupe < 1) {
        $dedupe = 60;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!isset($_SESSION['audit_reel_views']) || !is_array($_SESSION['audit_reel_views'])) {
            $_SESSION['audit_reel_views'] = array();
        }
        $now = time();
        if (isset($_SESSION['audit_reel_views'][$post_id])
            && ($now - (int) $_SESSION['audit_reel_views'][$post_id]) < $dedupe
        ) {
            return false;
        }
        $_SESSION['audit_reel_views'][$post_id] = $now;
        if (count($_SESSION['audit_reel_views']) > 200) {
            $_SESSION['audit_reel_views'] = array_slice($_SESSION['audit_reel_views'], -100, null, true);
        }
    }
    return Wo_RegisterUserAuditLog($user_id, 'reels', 'view', array('post_id' => $post_id));
}

/**
 * Delete audit rows older than retention days.
 *
 * @param int|null $days
 * @return int rows deleted
 */
function Wo_PurgeUserActivityAuditLog($days = null)
{
    global $sqlConnect;
    if (!defined('T_USER_ACTIVITY_LOG') || empty($sqlConnect)) {
        return 0;
    }
    if ($days === null) {
        $days = defined('USER_ACTIVITY_AUDIT_RETENTION_DAYS')
            ? (int) USER_ACTIVITY_AUDIT_RETENTION_DAYS
            : 90;
    }
    $days = (int) $days;
    if ($days < 1) {
        $days = 90;
    }
    $cutoff = time() - ($days * 86400);
    $ok = @mysqli_query(
        $sqlConnect,
        "DELETE FROM `" . T_USER_ACTIVITY_LOG . "` WHERE `created_at` < " . (int) $cutoff
    );
    if (!$ok) {
        return 0;
    }
    return (int) mysqli_affected_rows($sqlConnect);
}
