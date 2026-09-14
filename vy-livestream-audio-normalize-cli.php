<?php

/**
 * CLI: re-encode audio track to standard AAC for Safari (esds), video stream copy.
 * Waits until the MP4 exists (socket/RTMP finalize runs after stoplive).
 *
 * Usage (from project httpdocs):
 *   php vy-livestream-audio-normalize-cli.php "upload/vy-streams-media/USER/streams/file.mp4"
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

chdir(__DIR__);

$rel = isset($argv[1]) ? trim($argv[1], " \t\n\r\0\x0B\"'") : '';
$rel = ltrim(str_replace('\\', '/', $rel), '/');
if ($rel === '' || substr(strtolower($rel), -4) !== '.mp4') {
    fwrite(STDERR, "usage: php vy-livestream-audio-normalize-cli.php upload/vy-streams-media/UID/streams/name.mp4\n");
    exit(1);
}

$iniFiles = glob(__DIR__ . '/assets/vaneayoung/LiveStream/ini/*.ini');
if (!$iniFiles) {
    fwrite(STDERR, "ini not found\n");
    exit(1);
}
$CONFIG = parse_ini_file($iniFiles[0]);
$ff = !empty($CONFIG['st__ffmpeg_path']) ? $CONFIG['st__ffmpeg_path'] : '/usr/bin/ffmpeg';
$ffOk = is_file($ff) && is_executable($ff);
if (!$ffOk) {
    $o = [];
    $r = 1;
    @exec(escapeshellarg($ff) . ' -version 2>/dev/null', $o, $r);
    $ffOk = $r === 0 && !empty($o[0]) && stripos($o[0], 'ffmpeg') !== false;
}
if (!$ffOk) {
    fwrite(STDERR, "ffmpeg not found or not runnable: {$ff}\n");
    exit(1);
}
$br = isset($CONFIG['st__audioBitsPerSecond']) ? (int) $CONFIG['st__audioBitsPerSecond'] : 128000;
if ($br < 64000) {
    $br = 128000;
}

$root = __DIR__;
$in = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

$maxAttempts = 36;
$sleepSec = 5;

for ($t = 0; $t < $maxAttempts; $t++) {
    if (!is_file($in) || filesize($in) < 1024) {
        sleep($sleepSec);
        continue;
    }

    $tmp = $in . '.safari-aac.' . bin2hex(random_bytes(4)) . '.mp4';
    $cmd = sprintf(
        '%s -y -fflags +genpts -i %s -c:v copy -c:a aac -profile:a aac_low -ar 48000 -ac 2 -b:a %d -movflags +faststart %s 2>&1',
        escapeshellarg($ff),
        escapeshellarg($in),
        $br,
        escapeshellarg($tmp)
    );
    exec($cmd, $out, $ret);

    if ($ret === 0 && is_file($tmp) && filesize($tmp) > 1024) {
        if (@rename($tmp, $in)) {
            echo date('c'), " OK ", $rel, "\n";
            exit(0);
        }
    }
    @unlink($tmp);
    sleep($sleepSec);
}

fwrite(STDERR, date('c') . " FAIL after waits: {$rel}\n");
exit(1);
