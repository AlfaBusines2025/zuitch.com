<?php
/**
 * CLI: complete a DaaS order with real tokens, or list pending (status=new).
 *
 * Usage:
 *   php assets/includes/Daas/daas_complete_order.php --list-pending
 *   php assets/includes/Daas/daas_complete_order.php --order=N --tokens=123456 --notes="resumen corto"
 *
 * Server-side only. Never call from the browser with DAAS_PROJECT_SECRET.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$httpdocs = dirname(dirname(dirname(__DIR__)));
require_once $httpdocs . '/assets/includes/daas_env.php';
require_once $httpdocs . '/assets/includes/Daas/DaasClient.php';

daas_env_load();

$opts = array(
    'order::',
    'tokens::',
    'notes::',
    'list-pending',
    'help',
);
$args = getopt('', $opts);

if (isset($args['help']) || (empty($args) && $argc <= 1)) {
    fwrite(STDOUT, "Usage:\n");
    fwrite(STDOUT, "  php daas_complete_order.php --list-pending\n");
    fwrite(STDOUT, "  php daas_complete_order.php --order=N --tokens=123456 [--notes=\"resumen\"]\n");
    exit(0);
}

$client = new DaasClient();

if (isset($args['list-pending'])) {
    $res = $client->listOrders(array(
        'company_external_id' => daas_env('DAAS_BILLING_EXTERNAL_ID', 'zuitch'),
        'status' => 'new',
        'per_page' => 50,
    ));
    if (empty($res['ok'])) {
        fwrite(STDERR, 'FAIL: ' . (isset($res['message']) ? $res['message'] : 'list failed') . "\n");
        exit(1);
    }
    $orders = isset($res['data']) && is_array($res['data']) ? $res['data'] : array();
    fwrite(STDOUT, json_encode(array(
        'ok' => true,
        'count' => count($orders),
        'orders' => $orders,
        'meta' => isset($res['meta']) ? $res['meta'] : null,
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
    exit(0);
}

$orderId = isset($args['order']) ? (int) $args['order'] : 0;
$tokens = isset($args['tokens']) ? (int) $args['tokens'] : 0;
$notes = isset($args['notes']) ? (string) $args['notes'] : '';

if ($orderId <= 0) {
    fwrite(STDERR, "Missing or invalid --order=N\n");
    exit(1);
}
if ($tokens <= 0) {
    fwrite(STDERR, "Missing or invalid --tokens (positive integer required; do not invent usage)\n");
    exit(1);
}

$res = $client->completeOrder($orderId, $tokens, $notes, 'zuitch_cursor');

if (empty($res['ok'])) {
    fwrite(STDERR, 'FAIL HTTP ' . (isset($res['status']) ? $res['status'] : 0) . ': '
        . (isset($res['message']) ? $res['message'] : 'complete failed') . "\n");
    if (!empty($res['quote'])) {
        fwrite(STDERR, 'quote: ' . json_encode($res['quote']) . "\n");
    }
    exit(1);
}

$quote = isset($res['quote']) && is_array($res['quote']) ? $res['quote'] : array();
fwrite(STDOUT, json_encode(array(
    'ok' => true,
    'order_id' => $orderId,
    'tokens' => $tokens,
    'quote' => array(
        'units' => isset($quote['units']) ? $quote['units'] : null,
        'subtotal_usd' => isset($quote['subtotal_usd']) ? $quote['subtotal_usd'] : null,
        'iva_usd' => isset($quote['iva_usd']) ? $quote['iva_usd'] : null,
        'total_usd' => isset($quote['total_usd']) ? $quote['total_usd'] : null,
    ),
    'data' => isset($res['data']) ? $res['data'] : null,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
exit(0);
