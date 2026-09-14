<?php
/**
 * Google Translate v2 smoke test.
 * API key MUST come from environment — never hardcode secrets in this file.
 *
 * Set GOOGLE_TRANSLATE_API_KEY in the server .env (not committed).
 */

require_once __DIR__ . '/assets/includes/daas_env.php';
daas_env_load();

$apiKey = daas_env('GOOGLE_TRANSLATE_API_KEY', '');
if ($apiKey === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('GOOGLE_TRANSLATE_API_KEY is not configured on this server.');
}

$text   = 'Hello, it is me';
$source = 'en';
$target = 'es';

$url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey);

$payload = [
    'q'      => $text,
    'source' => $source,
    'target' => $target,
    'format' => 'text',
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
]);

$response = curl_exec($ch);
if ($response === false) {
    http_response_code(500);
    exit('Error cURL: ' . curl_error($ch));
}
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['error'])) {
    $msg  = $data['error']['message'] ?? 'Error desconocido';
    $code = $data['error']['code'] ?? 500;
    http_response_code(500);
    exit("Error API ($code): $msg");
}

$translated = $data['data']['translations'][0]['translatedText'] ?? null;
if (!$translated) {
    http_response_code(500);
    exit('No se encontró "translatedText" en la respuesta.');
}

$translated = html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');

header('Content-Type: text/plain; charset=utf-8');
echo $translated;
