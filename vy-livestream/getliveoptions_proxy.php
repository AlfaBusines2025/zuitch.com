<?php
/**
 * Proxy for Node /getliveoptions.
 * If Node marks terminated but PHP still has islivenow=yes, clear terminated
 * so cached/old viewers can join (Android WebView 30d JS cache).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$raw = file_get_contents('php://input');
if ($raw === false) {
    $raw = '';
}

$req = json_decode($raw, true);
$liveId = 0;
if (is_array($req)) {
    if (isset($req['Live_ID'])) {
        $liveId = (int) $req['Live_ID'];
    } elseif (isset($req['live_id'])) {
        $liveId = (int) $req['live_id'];
    }
}

$nodeUrl = 'https://127.0.0.1:3000/getliveoptions';
$ch = curl_init($nodeUrl);
curl_setopt_array($ch, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $raw,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Host: zuitch.com',
        'Content-Length: ' . strlen($raw),
    ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
));
$nodeBody = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($nodeBody === false || $nodeBody === '' || $httpCode < 200 || $httpCode >= 500) {
    http_response_code($httpCode > 0 ? $httpCode : 502);
    echo $nodeBody !== false && $nodeBody !== '' ? $nodeBody : json_encode(array(
        'blocked' => 'no',
        'muted' => 'no',
        'moderator' => false,
        'terminated' => 0,
        'reconnecting' => -1,
        'time' => new stdClass(),
        'proxy_error' => 'upstream',
    ));
    exit;
}

$data = json_decode($nodeBody, true);
if (!is_array($data)) {
    echo $nodeBody;
    exit;
}

$terminated = !empty($data['terminated']);
$phpStillLive = false;

if ($terminated && $liveId > 0) {
    try {
        $sql_db_host = $sql_db_user = $sql_db_pass = $sql_db_name = null;
        require dirname(__DIR__) . '/config.php';
        $mysqli = @mysqli_connect($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name, 3306);
        if ($mysqli) {
            $pid = (int) $liveId;
            $q = mysqli_query(
                $mysqli,
                "SELECT `islivenow`,`ended` FROM `vy_live_broadcasts` WHERE `post_id`='{$pid}' LIMIT 1"
            );
            if ($q && ($row = mysqli_fetch_assoc($q))) {
                $islivenow = isset($row['islivenow']) ? (string) $row['islivenow'] : null;
                $ended = isset($row['ended']) ? (string) $row['ended'] : null;
                $phpStillLive = ($islivenow === 'yes' && $ended !== 'yes');
            }
            mysqli_close($mysqli);
        }
    } catch (Throwable $e) {
        // ignore; fall through with Node payload
    }
}

if ($terminated && $phpStillLive) {
    $data['terminated'] = 0;
    if (!isset($data['time']) || (!is_array($data['time']) && !is_object($data['time']))) {
        $data['time'] = new stdClass();
    }
}

echo json_encode($data);
exit;
