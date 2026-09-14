<?php
/**
 * DaaS platform update webhook.
 * POST /api/daas/sync
 * Verifies X-DaaS-Signature HMAC over the raw body, then git pull --ff-only.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$httpdocs = dirname(dirname(__DIR__));
require_once $httpdocs . '/assets/includes/daas_env.php';
require_once $httpdocs . '/assets/includes/Daas/GitSyncService.php';

daas_env_load();

$secret = (string) daas_env('DAAS_UPDATE_SECRET', '');
if ($secret === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'DAAS_UPDATE_SECRET not configured']);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    $raw = '';
}

$sig = '';
if (!empty($_SERVER['HTTP_X_DAAS_SIGNATURE'])) {
    $sig = (string) $_SERVER['HTTP_X_DAAS_SIGNATURE'];
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers as $k => $v) {
        if (strcasecmp($k, 'X-DaaS-Signature') === 0) {
            $sig = (string) $v;
            break;
        }
    }
}

$expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
if (!hash_equals($expected, $sig)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Invalid signature']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = [];
}

$sha = isset($payload['sha']) ? (string) $payload['sha'] : null;
if ($sha === '') {
    $sha = null;
}

$service = new GitSyncService($httpdocs);
$result = $service->pull($sha);

$code = !empty($result['code']) ? (int) $result['code'] : ($result['ok'] ? 200 : 422);
http_response_code($code);

$response = [
    'ok' => !empty($result['ok']),
    'message' => isset($result['message']) ? $result['message'] : '',
];
if (!empty($result['sha'])) {
    $response['sha'] = $result['sha'];
}

echo json_encode($response);
exit;
