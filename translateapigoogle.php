<?php
// Traduce "Hellow, its me" de EN a ES con Google Translate v2 (REST)

// === Configuración ===
//$apiKey  = 'AIzaSyBBqGaLDxhgl4kI_5D0He9aYw6taWcaySE'; // Tu API Key (restringe y mantén en secreto)
$text    = 'Hellow, its me';
$source  = 'en';
$target  = 'es';

// Endpoint v2 con API key por querystring
$url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey);

// Cuerpo JSON (puedes omitir "source" para autodetección)
$payload = [
    'q'       => $text,
    'source'  => $source,
    'target'  => $target,
    'format'  => 'text',
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

// Decodificar respuesta
$data = json_decode($response, true);

// Manejo de errores de la API
if (isset($data['error'])) {
    $msg = $data['error']['message'] ?? 'Error desconocido';
    $code = $data['error']['code'] ?? 500;
    http_response_code(500);
    exit("Error API ($code): $msg");
}

// Extraer traducción
$translated = $data['data']['translations'][0]['translatedText'] ?? null;
if (!$translated) {
    http_response_code(500);
    exit('No se encontró "translatedText" en la respuesta: ' . $response);
}

// Google devuelve entidades HTML (p. ej., &#39; para apostrofes), así que las decodificamos:
$translated = html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Mostrar resultado
header('Content-Type: text/plain; charset=utf-8');
echo $translated; // Ejemplo esperado: "Hola, soy yo"