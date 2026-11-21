<?php
/**
 * Zuitch AI — core helpers (OpenAI-compatible chat, intent, catalog search, corrections).
 */

if (!defined('T_ZUITCH_AI_CHATS')) {
    define('T_ZUITCH_AI_CHATS', 'Wo_ZuitchAi_Chats');
    define('T_ZUITCH_AI_MESSAGES', 'Wo_ZuitchAi_Messages');
    define('T_ZUITCH_AI_RATINGS', 'Wo_ZuitchAi_Ratings');
}

function zuitch_ai_cfg($key, $default = '')
{
    global $wo;
    if (!empty($wo['config'][$key]) || (isset($wo['config'][$key]) && $wo['config'][$key] === '0')) {
        return $wo['config'][$key];
    }
    return $default;
}

function zuitch_ai_enabled()
{
    return (string) zuitch_ai_cfg('zuitch_ai', '0') === '1';
}

function zuitch_ai_is_superadmin()
{
    global $wo;
    if (empty($wo['loggedin']) || empty($wo['user']['id'])) {
        return false;
    }
    if ((int) $wo['user']['id'] === 1) {
        return true;
    }
    return function_exists('Wo_IsAdmin') && Wo_IsAdmin();
}

function zuitch_ai_api_key()
{
    $dedicated = trim((string) zuitch_ai_cfg('zuitch_ai_api_key', ''));
    if ($dedicated !== '') {
        return $dedicated;
    }
    return trim((string) zuitch_ai_cfg('openai_token', ''));
}

function zuitch_ai_default_intent_prompt()
{
    return "Eres el clasificador de Zuitch AI. Lee el mensaje del usuario y responde una sola palabra, sin explicación:\n"
        . "- producto: quiere comprar, ver precio o que le recomienden un artículo del mercado.\n"
        . "- pagina: busca una página, negocio, tienda o perfil de página.\n"
        . "- grupo: busca un grupo o comunidad.\n"
        . "- soporte: pregunta cómo se usa Zuitch (cuenta, contraseña, publicar, comprar, páginas, grupos u otra función de la plataforma), o qué es Zuitch/Switch/Suichi.\n"
        . "- chat: saludo, quién soy/mi perfil/mi usuario, opinión, conocimiento general (geografía, significados, cultura) o charla que no pide catálogo ni ayuda de uso.\n"
        . "Si pide más de una cosa, elige la principal. No inventes otra etiqueta.";
}

function zuitch_ai_default_reply_prompt()
{
    return "Eres Zuitch AI, el asistente de zuitch.com. Responde en el idioma del usuario, breve y claro.\n"
        . "Identidad de la plataforma: Zuitch (a veces dicho Switch, Suichi, Suich o similar) es la red social y mercado en https://zuitch.com. Ahí la gente crea perfil, publica en el feed, usa historias/reels/lives, páginas, grupos, vende y compra en el Mercado, monedero, mensajes, etc. Si preguntan qué es Zuitch/Switch/Suichi, explícalo así (2–5 frases) y ofrece ayuda concreta (perfil, publicar, mercado, páginas, grupos). No digas que es otra app ni inventes funciones.\n"
        . "Usuario que escribe ahora (sesión actual):\n{usuario}\n"
        . "Si pregunta quién soy, cuál es mi usuario, mi perfil o datos básicos de su cuenta, responde SOLO con ese bloque (nombre, @usuario, URL de perfil y lo que aparezca). No inventes datos. No reveles correo, teléfono, contraseña, monedero ni datos de otras personas.\n"
        . "Prioridad 1 — datos de Zuitch: si {correcciones} trae una respuesta aprobada por el superadmin y la pregunta se parece, usa esa respuesta (manda sobre tu criterio). Si {contexto} trae fichas de catálogo (producto/pagina/grupo), DEBES recomendar esas fichas: copia el nombre EXACTO, precio y URL del contexto. Prohibido decir que no hay coincidencias si hay fichas. Prohibido inventar otras URLs, marcas o listings de Zuitch.\n"
        . "Prioridad 2 — documentación: si {contexto} trae DOC o guía de la plataforma, explica con esos pasos.\n"
        . "Prioridad 3 — conocimiento general: si {contexto} está vacío o es (vacío) y la pregunta NO pide un listing concreto del mercado de Zuitch, responde con tu conocimiento general (geografía, significados, cultura, consejo genérico, etc.). Puedes aclarar en una frase que no es un dato del catálogo de Zuitch cuando aplique.\n"
        . "Si pidió un producto/página/grupo de Zuitch y el contexto está vacío: dilo claro (no hay coincidencias en Zuitch) y, si ayuda, orienta en general SIN inventar productos, precios ni enlaces de Zuitch ni de Amazon/Mercado Libre u otras tiendas externas.\n"
        . "Si pide Amazon, Mercado Libre u otra tienda externa, explica que solo puedes ayudar con el mercado de Zuitch; no des links externos.\n"
        . "Si una palabra se corrigió, menciónalo en una frase.\n"
        . "No leas mensajes privados, pedidos ni datos de otros usuarios. No publiques ni compres por el usuario.";
}

/**
 * Basic public profile of the chat owner (for “quién soy”).
 */
function zuitch_ai_user_context_block($userId)
{
    $userId = (int) $userId;
    if ($userId < 1) {
        return '(sin sesión de usuario)';
    }
    $u = null;
    if (function_exists('Wo_UserData')) {
        $u = Wo_UserData($userId);
    }
    if (empty($u) || !is_array($u)) {
        return '(usuario no disponible)';
    }
    $username = trim((string) ($u['username'] ?? ''));
    $first = trim((string) ($u['first_name'] ?? ''));
    $last = trim((string) ($u['last_name'] ?? ''));
    $name = trim($first . ' ' . $last);
    if ($name === '') {
        $name = trim((string) ($u['name'] ?? $username));
    }
    $url = '';
    if (!empty($u['url'])) {
        $url = (string) $u['url'];
    } elseif ($username !== '' && function_exists('Wo_SeoLink')) {
        $url = Wo_SeoLink('index.php?link1=timeline&u=' . $username);
    } else {
        $url = zuitch_ai_site_url() . '/' . rawurlencode($username);
    }
    $lines = array();
    if ($name !== '') {
        $lines[] = 'Nombre: ' . $name;
    }
    if ($username !== '') {
        $lines[] = 'Usuario: @' . $username;
    }
    if ($url !== '') {
        $lines[] = 'Perfil: ' . $url;
    }
    $gender = trim((string) ($u['gender'] ?? ''));
    if ($gender !== '' && !preg_match('/^\d+$/', $gender)) {
        $lines[] = 'Género: ' . $gender;
    }
    $country = '';
    global $wo;
    $cid = isset($u['country_id']) ? (int) $u['country_id'] : 0;
    if ($cid > 0 && !empty($wo['countries_name'][$cid])) {
        $country = (string) $wo['countries_name'][$cid];
    } elseif (!empty($u['country'])) {
        $country = (string) $u['country'];
    }
    if ($country !== '' && strtolower($country) !== 'select country') {
        $lines[] = 'País: ' . $country;
    }
    $city = trim((string) ($u['city'] ?? ''));
    if ($city !== '') {
        $lines[] = 'Ciudad: ' . $city;
    }
    $about = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($u['about'] ?? ''))));
    if ($about !== '') {
        $lines[] = 'Bio: ' . mb_substr($about, 0, 220);
    }
    $verified = !empty($u['verified']) && (string) $u['verified'] === '1';
    if ($verified) {
        $lines[] = 'Cuenta verificada: sí';
    }
    if (empty($lines)) {
        return '(perfil básico no disponible)';
    }
    return implode("\n", $lines);
}

/** “Quién soy / mi perfil / mi usuario” del remitente. */
function zuitch_ai_is_whoami_ask($userText)
{
    $n = zuitch_ai_normalize($userText);
    if ($n === '') {
        return false;
    }
    if (preg_match('/\b(quien\s+soy|quién\s+soy|quien\s+eres\s+tu\s+piensas\s+que\s+soy|como\s+me\s+llamo|cual\s+es\s+mi\s+(nombre|usuario|perfil|cuenta)|mi\s+(nombre|usuario|perfil|cuenta)\s*\??|who\s+am\s+i|what(?:\'s|s|\s+is)\s+my\s+(name|username|profile))\b/u', $n)) {
        return true;
    }
    if (preg_match('/^(mi\s+perfil|mi\s+usuario|mi\s+cuenta)\??$/u', $n)) {
        return true;
    }
    return false;
}

function zuitch_ai_chat_request($messages, $opts = array())
{
    $apiKey = zuitch_ai_api_key();
    if ($apiKey === '') {
        throw new Exception('Zuitch AI: falta la API key de OpenAI.');
    }
    $base = rtrim((string) zuitch_ai_cfg('zuitch_ai_api_url', 'https://api.openai.com/v1'), '/');
    $model = (string) zuitch_ai_cfg('zuitch_ai_model', 'gpt-4o-mini');
    $org = trim((string) zuitch_ai_cfg('zuitch_ai_org_id', ''));
    $maxTokens = !empty($opts['max_tokens']) ? (int) $opts['max_tokens'] : 700;
    $temperature = isset($opts['temperature']) ? (float) $opts['temperature'] : 0.4;

    $payload = array(
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => $temperature,
    );
    $url = $base . '/chat/completions';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $headers = array(
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    );
    if ($org !== '') {
        $headers[] = 'OpenAI-Organization: ' . $org;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($errno) {
        throw new Exception('Zuitch AI cURL: ' . $err);
    }
    $result = json_decode($raw);
    if (!empty($result->error) && !empty($result->error->message)) {
        throw new Exception($result->error->message);
    }
    if (empty($result->choices[0]->message->content)) {
        throw new Exception('Zuitch AI: respuesta vacía del modelo.');
    }
    return trim((string) $result->choices[0]->message->content);
}

/**
 * Absolute httpdocs path (…/httpdocs).
 */
function zuitch_ai_httpdocs()
{
    return dirname(__DIR__, 2);
}

function zuitch_ai_site_url()
{
    global $wo;
    if (!empty($wo['config']['site_url'])) {
        return rtrim((string) $wo['config']['site_url'], '/');
    }
    return 'https://zuitch.com';
}

/**
 * Persist uploaded voice note under upload/zuitch_ai/voice/{userId}/.
 *
 * @return string Public URL
 */
function zuitch_ai_store_voice_file($tmpPath, $userId, $ext)
{
    $userId = (int) $userId;
    $ext = strtolower(preg_replace('/[^a-z0-9]/', '', (string) $ext));
    if ($ext === '' || $ext === 'blob') {
        $ext = 'webm';
    }
    $relDir = 'upload/zuitch_ai/voice/' . $userId;
    $absDir = zuitch_ai_httpdocs() . '/' . $relDir;
    if (!is_dir($absDir) && !@mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        throw new Exception('No se pudo crear la carpeta de audio.');
    }
    $name = 'v' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $absDir . '/' . $name;
    // Prefer copy: tmp may still be needed by PHP after Whisper; then unlink if moveable.
    $ok = @copy($tmpPath, $dest);
    if (!$ok) {
        $ok = @move_uploaded_file($tmpPath, $dest);
    }
    if (!$ok) {
        throw new Exception('No se pudo guardar el audio.');
    }
    @chmod($dest, 0644);
    return zuitch_ai_site_url() . '/' . $relDir . '/' . $name;
}

/**
 * Transcribe audio via OpenAI Whisper (auto language).
 *
 * @return array{text:string,language:string}
 */
function zuitch_ai_transcribe($tmpPath, $filename = 'audio.webm')
{
    $apiKey = zuitch_ai_api_key();
    if ($apiKey === '') {
        throw new Exception('Zuitch AI: falta la API key de OpenAI.');
    }
    if (!is_readable($tmpPath)) {
        throw new Exception('No se pudo leer el audio.');
    }
    $size = filesize($tmpPath);
    if ($size === false || $size < 64) {
        throw new Exception('El audio está vacío o es demasiado corto.');
    }
    if ($size > 5 * 1024 * 1024) {
        throw new Exception('El audio supera 5 MB. Graba una nota más corta (máx. ~60 s).');
    }
    $base = rtrim((string) zuitch_ai_cfg('zuitch_ai_api_url', 'https://api.openai.com/v1'), '/');
    $model = trim((string) zuitch_ai_cfg('zuitch_ai_whisper_model', 'whisper-1'));
    if ($model === '') {
        $model = 'whisper-1';
    }
    $org = trim((string) zuitch_ai_cfg('zuitch_ai_org_id', ''));

    $mimeHint = 'application/octet-stream';
    if (function_exists('mime_content_type')) {
        $detected = @mime_content_type($tmpPath);
        if (is_string($detected) && $detected !== '') {
            $mimeHint = $detected;
        }
    }
    $cfile = class_exists('CURLFile')
        ? new CURLFile($tmpPath, $mimeHint, $filename)
        : '@' . $tmpPath;

    // No forced language: multi-idioma (verbose_json returns language code).
    $payload = array(
        'file' => $cfile,
        'model' => $model,
        'response_format' => 'verbose_json',
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base . '/audio/transcriptions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    $headers = array('Authorization: Bearer ' . $apiKey);
    if ($org !== '') {
        $headers[] = 'OpenAI-Organization: ' . $org;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($errno) {
        throw new Exception('Zuitch AI cURL (voz): ' . $err);
    }
    $result = json_decode($raw);
    if (!empty($result->error) && !empty($result->error->message)) {
        throw new Exception($result->error->message);
    }
    $text = isset($result->text) ? trim((string) $result->text) : '';
    if ($text === '') {
        throw new Exception('No se pudo transcribir el audio. Intenta de nuevo.');
    }
    $lang = isset($result->language) ? strtolower(trim((string) $result->language)) : '';
    return array(
        'text' => $text,
        'language' => $lang,
    );
}

/**
 * Generate speech via OpenAI TTS; returns public URL to mp3.
 *
 * @param string $text
 * @param string $lang BCP-47 or ISO-639-1 hint
 * @return string URL
 */
function zuitch_ai_tts($text, $lang = 'es')
{
    $apiKey = zuitch_ai_api_key();
    if ($apiKey === '') {
        throw new Exception('Zuitch AI: falta la API key de OpenAI.');
    }
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
    if ($text === '') {
        throw new Exception('No hay texto para leer.');
    }
    if (mb_strlen($text) > 2000) {
        $text = mb_substr($text, 0, 2000);
    }
    $base = rtrim((string) zuitch_ai_cfg('zuitch_ai_api_url', 'https://api.openai.com/v1'), '/');
    $org = trim((string) zuitch_ai_cfg('zuitch_ai_org_id', ''));
    $voice = 'nova';
    $lang = strtolower(substr((string) $lang, 0, 2));
    if (in_array($lang, array('en', 'pt', 'fr', 'de', 'it'), true)) {
        $voice = 'alloy';
    }

    $payload = json_encode(array(
        'model' => 'tts-1',
        'voice' => $voice,
        'input' => $text,
        'response_format' => 'mp3',
    ));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base . '/audio/speech');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    $headers = array(
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    );
    if ($org !== '') {
        $headers[] = 'OpenAI-Organization: ' . $org;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($errno) {
        throw new Exception('Zuitch AI cURL (TTS): ' . $err);
    }
    $maybeJson = json_decode($raw);
    if ($code >= 400 || (!empty($maybeJson->error) && !empty($maybeJson->error->message))) {
        $msg = !empty($maybeJson->error->message) ? $maybeJson->error->message : ('TTS HTTP ' . $code);
        throw new Exception($msg);
    }
    if ($raw === false || $raw === '') {
        throw new Exception('TTS vacío.');
    }

    $relDir = 'upload/zuitch_ai/tts';
    $absDir = zuitch_ai_httpdocs() . '/' . $relDir;
    if (!is_dir($absDir) && !@mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        throw new Exception('No se pudo crear la carpeta TTS.');
    }
    $name = 't' . time() . '_' . bin2hex(random_bytes(4)) . '.mp3';
    $dest = $absDir . '/' . $name;
    if (@file_put_contents($dest, $raw) === false) {
        throw new Exception('No se pudo guardar el audio TTS.');
    }
    @chmod($dest, 0644);
    return zuitch_ai_site_url() . '/' . $relDir . '/' . $name;
}

/**
 * Extract plain text from an uploaded document for the AI context.
 *
 * @return string|false Text or false if unsupported
 */
function zuitch_ai_extract_file_text($tmpPath, $ext, $maxChars = 4000)
{
    $ext = strtolower((string) $ext);
    if (!is_readable($tmpPath)) {
        return false;
    }
    if (in_array($ext, array('txt', 'csv', 'md', 'log'), true)) {
        $raw = file_get_contents($tmpPath);
        if ($raw === false) {
            return false;
        }
        return mb_substr($raw, 0, $maxChars);
    }
    if ($ext === 'pdf') {
        // Prefer pdftotext when available; otherwise refuse clearly.
        $bin = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
        if ($bin !== '') {
            $out = array();
            $code = 0;
            exec(escapeshellcmd($bin) . ' -enc UTF-8 -q ' . escapeshellarg($tmpPath) . ' - 2>/dev/null', $out, $code);
            if ($code === 0 && !empty($out)) {
                return mb_substr(implode("\n", $out), 0, $maxChars);
            }
        }
        return false;
    }
    return false;
}

function zuitch_ai_normalize($text)
{
    $text = mb_strtolower(trim((string) $text), 'UTF-8');
    $map = array(
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
    );
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s\-]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function zuitch_ai_stopwords()
{
    return array(
        'busco', 'buscar', 'busca', 'quiero', 'necesito', 'un', 'una', 'unos', 'unas', 'el', 'la', 'los', 'las',
        'de', 'del', 'y', 'o', 'en', 'para', 'por', 'con', 'me', 'mi', 'mis', 'hay', 'tiene', 'tienen',
        'some', 'the', 'a', 'an', 'of', 'to', 'for', 'with', 'please', 'hola', 'buenas', 'gracias',
        'es', 'mas', 'cual', 'dame', 'link', 'links', 'url', 'http', 'https', 'www',
        'encontrar', 'encuentra', 'sirvan', 'sirve', 'que', 'uno', 'nada', 'tienes', 'tiene',
        'tener', 'algo', 'alguna', 'alguno',
    );
}

/** Words that describe the entity type, not the search topic (noise in catalog LIKE). */
function zuitch_ai_catalog_noise_words()
{
    return array(
        'pagina', 'paginas', 'grupo', 'grupos', 'producto', 'productos', 'comunidad', 'comunidades',
        'mercado', 'tienda', 'tiendas', 'negocio', 'negocios', 'empresa', 'empresas', 'comercio',
        'perfil', 'perfiles', 'zuitch', 'sitio', 'plataforma',
    );
}

function zuitch_ai_filter_catalog_words($words)
{
    $noise = array_flip(zuitch_ai_catalog_noise_words());
    $out = array();
    foreach ((array) $words as $w) {
        $w = trim((string) $w);
        if ($w === '' || isset($noise[$w]) || mb_strlen($w) < 3) {
            continue;
        }
        $out[] = $w;
    }
    return array_values(array_unique($out));
}

/** “Qué es Switch/Suichi/Zuitch” — identidad de la plataforma (Aldo y usuarios). */
function zuitch_ai_is_platform_identity_ask($userText)
{
    $n = zuitch_ai_normalize($userText);
    if ($n === '') {
        return false;
    }
    if (!preg_match('/\b(zuitch|switch|suichi|suich|sweet)\b/u', $n)) {
        return false;
    }
    // Nombre solo, o pregunta de definición / explicación.
    if (preg_match('/^(que\s+es|que\s+significa|que\s+tal\s+es|explica|explícame|explicame|dime|hola|we\s+a|q\s+es|para\s+que\s+sirve)\b/u', $n)
        || preg_match('/\b(que\s+es|que\s+significa|saber\s+que\s+es|explica|explícame|explicame|dime|para\s+que\s+sirve|como\s+funciona|me\s+explicas|me\s+explica)\b/u', $n)
        || preg_match('/^(zuitch|switch|suichi|suich|sweet)\??$/u', $n)
        || preg_match('/\b(zuitch|switch|suichi)\b.*\b(plataforma|red\s+social|app|sitio|pagina|página)\b/u', $n)) {
        return true;
    }
    return false;
}

/** Fallback DOC when admin docs miss the identity chunk. */
function zuitch_ai_platform_identity_card()
{
    return array(
        'type' => 'doc',
        'title' => 'Qué es Zuitch',
        'snippet' => "Zuitch (también llamado Switch o Suichi) es la red social y mercado en línea de https://zuitch.com.\n"
            . "En Zuitch puedes: crear perfil, publicar en el feed, historias, reels y lives; seguir gente; páginas y grupos; vender y comprar en el Mercado; usar monedero, mensajes y más.\n"
            . "Para empezar: 1) Entra o regístrate en zuitch.com. 2) Completa tu perfil. 3) Explora el feed, el Mercado y el Directorio. 4) Si necesitas ayuda, usa este asistente (Zuitch AI).\n"
            . "No es otra red social ajena: es la plataforma Zuitch.",
        'url' => zuitch_ai_site_url() . '/',
    );
}

function zuitch_ai_is_howto_support($userText)
{
    $n = zuitch_ai_normalize($userText);
    if ($n === '') {
        return false;
    }
    if (preg_match('/\b(amazon|mercadolibre|ebay|aliexpress)\b/u', $n)) {
        return false;
    }
    if (zuitch_ai_is_platform_identity_ask($userText)) {
        return true;
    }
    if (preg_match('/\b(como|donde|puedo|ayuda|guia|tutorial|que es|que son|que significa|how|what is)\b/u', $n)) {
        return true;
    }
    if (preg_match('/\b(create|publish|change|edit|buy|sell)\b/u', $n)
        && preg_match('/\b(page|group|product|profile|password|blog|event|ad|album)\b/u', $n)) {
        return true;
    }
    if (preg_match('/^(y\s+)?(los\s+|las\s+|el\s+|la\s+)?(reels?|lives?|historias?)\??$/u', $n)) {
        return true;
    }
    if (preg_match('/\b(uno|unir|unirme|unirse|crear|editar|publicar|cambiar|recargar|enviar)\b/u', $n)
        && preg_match('/\b(grupo|pagina|producto|perfil|cuenta|monedero|pedido|orden)\b/u', $n)) {
        return true;
    }
    return false;
}

function zuitch_ai_is_external_shop_request($userText)
{
    $n = zuitch_ai_normalize($userText);
    return (bool) preg_match('/\b(amazon|mercadolibre|ebay|aliexpress)\b/u', $n);
}

function zuitch_ai_is_product_followup($userText)
{
    $n = zuitch_ai_normalize($userText);
    return (bool) preg_match('/\b(barato|caro|barata|cara|mejor|peor|otra|otro|esa|ese|recomend|cuanto|tambien|además|ademas|trago|bebida|comer|comida)\b/u', $n)
        || (bool) preg_match('/^(y\s+)?(uno|una|algo)\b/u', $n);
}

/** Availability / product noun asks that the LLM often tags as chat. */
function zuitch_ai_looks_like_product_ask($userText)
{
    $n = zuitch_ai_normalize($userText);
    if ($n === '' || zuitch_ai_is_howto_support($userText) || zuitch_ai_is_external_shop_request($userText)) {
        return false;
    }
    if (preg_match('/\b(whisky|whiskey|wisky|wisqui|cerveza|vino|ron|trago|bebida|cola|agua|comida|komida|comer|hamburguesa|sopa|cacao|choclo|lampara|bluetooth|celular|honor|infinix|perfume|casa|producto|productos|precio|barato|barata|caro|tomar|beber|food)\b/u', $n)) {
        return true;
    }
    // Verbos de compra/disponibilidad (evitar “tiene el ecuador”, geografía, etc.).
    if (preg_match('/\b(venden|vende|consigo|conseguir|encuentra|encontrar|busco|busca|comprar|compro|recomienda|recomendame)\b/u', $n)) {
        return true;
    }
    if (preg_match('/\b(tienes|tiene|hay)\b/u', $n)
        && preg_match('/\b(producto|productos|mercado|precio|barato|tienda|en\s+venta)\b/u', $n)) {
        return true;
    }
    return false;
}

function zuitch_ai_previous_user_text($chatId, $currentText)
{
    $msgs = zuitch_ai_get_messages((int) $chatId, 40);
    $prev = '';
    foreach ($msgs as $m) {
        if (($m['role'] ?? '') !== 'user') {
            continue;
        }
        $c = trim((string) ($m['content'] ?? ''));
        if ($c === '' || $c === $currentText) {
            continue;
        }
        $prev = $c;
    }
    return $prev;
}

function zuitch_ai_filter_relevant_cards($cards, $words)
{
    $words = zuitch_ai_filter_catalog_words($words);
    if (empty($words) || empty($cards)) {
        return $cards;
    }
    $kept = array();
    foreach ($cards as $c) {
        if (($c['type'] ?? '') === 'doc') {
            $kept[] = $c;
            continue;
        }
        $blob = zuitch_ai_normalize(($c['title'] ?? '') . ' ' . ($c['snippet'] ?? ''));
        $ok = false;
        foreach ($words as $w) {
            if ($w !== '' && mb_strpos($blob, $w) !== false) {
                $ok = true;
                break;
            }
        }
        if ($ok) {
            $kept[] = $c;
        }
    }
    return $kept;
}

function zuitch_ai_equivalents()
{
    return array(
        'auto' => array('carro', 'coche', 'vehiculo', 'vehiculo'),
        'carro' => array('auto', 'coche', 'vehiculo'),
        'coche' => array('auto', 'carro', 'vehiculo'),
        'vehiculo' => array('auto', 'carro', 'coche'),
        'tienda' => array('negocio', 'empresa', 'comercio'),
        'negocio' => array('tienda', 'empresa', 'comercio'),
        'pagina' => array('negocio', 'tienda', 'empresa'),
        'grupo' => array('comunidad'),
        'comunidad' => array('grupo'),
        'telefono' => array('celular', 'movil', 'phone'),
        'celular' => array('telefono', 'movil'),
        'publico' => array('publicar', 'publica'),
        'publicar' => array('publico', 'publica'),
        'guia' => array('ayuda', 'manual', 'tutorial'),
        'plataforma' => array('zuitch', 'sitio', 'switch', 'suichi'),
        'zuitch' => array('switch', 'suichi', 'suich', 'sweet', 'plataforma', 'sitio'),
        'switch' => array('zuitch', 'suichi', 'suich', 'sweet', 'plataforma'),
        'suichi' => array('zuitch', 'switch', 'suich', 'plataforma'),
        'suich' => array('zuitch', 'switch', 'suichi', 'plataforma'),
        'sweet' => array('zuitch', 'switch', 'suichi', 'plataforma'),
        // Categorías → términos que existen en el catálogo (evita “comer/trago” vacíos).
        'comer' => array('comida', 'alimento', 'hamburguesa', 'sopa', 'choclo', 'cacao'),
        'comida' => array('comer', 'alimento', 'hamburguesa', 'sopa', 'choclo', 'cacao', 'komida'),
        'alimento' => array('comida', 'comer', 'hamburguesa', 'sopa'),
        'trago' => array('whisky', 'bebida', 'licor', 'alcohol', 'cerveza', 'vino', 'ron', 'cola', 'agua'),
        'bebida' => array('trago', 'whisky', 'agua', 'cola', 'licor', 'cerveza'),
        'licor' => array('whisky', 'trago', 'ron', 'vino'),
        'alcohol' => array('whisky', 'trago', 'cerveza', 'vino', 'ron'),
        'whisky' => array('whiskey', 'wisky', 'wisqui', 'trago', 'licor'),
        'whiskey' => array('whisky', 'wisky', 'trago'),
        'tomar' => array('trago', 'bebida', 'whisky', 'agua', 'cola'),
        'beber' => array('trago', 'bebida', 'whisky', 'agua', 'cola'),
        'empleo' => array('empleos', 'vacante', 'trabajo', 'jobs'),
        'empleos' => array('empleo', 'vacante', 'trabajo'),
        'vacante' => array('empleo', 'empleos', 'trabajo'),
        'trabajo' => array('empleo', 'vacante'),
        // Typos / EN / variantes frecuentes
        'wisky' => array('whisky', 'whiskey'),
        'wisqui' => array('whisky', 'whiskey'),
        'komida' => array('comida', 'comer', 'hamburguesa', 'sopa'),
        'food' => array('comida', 'comer', 'hamburguesa', 'sopa', 'choclo'),
        'page' => array('pagina', 'paginas'),
        'pages' => array('pagina', 'paginas'),
        'group' => array('grupo', 'grupos'),
        'bluetooth' => array('blue', 'tooth', 'lampara'),
        'compro' => array('comprar', 'checkout', 'mercado'),
        'comprar' => array('compro', 'checkout', 'mercado'),
    );
}

function zuitch_ai_extract_keywords($text, $withEquivalents = true)
{
    $norm = zuitch_ai_normalize($text);
    $parts = preg_split('/\s+/', $norm);
    $stop = array_flip(zuitch_ai_stopwords());
    $eq = zuitch_ai_equivalents();
    $words = array();
    $original = array();
    foreach ($parts as $p) {
        if ($p === '' || isset($stop[$p]) || mb_strlen($p) < 2) {
            continue;
        }
        $words[$p] = true;
        $original[$p] = true;
        if ($withEquivalents && !empty($eq[$p])) {
            foreach ($eq[$p] as $alt) {
                $words[$alt] = true;
            }
        }
    }
    return array(
        'all' => array_keys($words),
        'original' => array_keys($original),
    );
}

function zuitch_ai_classify_intent($userText)
{
    // Deterministic overrides before the LLM (how-to vs catalog vs external shops).
    if (zuitch_ai_is_external_shop_request($userText)) {
        return 'chat';
    }
    if (zuitch_ai_is_platform_identity_ask($userText)) {
        return 'soporte';
    }
    if (zuitch_ai_is_howto_support($userText)) {
        return 'soporte';
    }
    $nPre = zuitch_ai_normalize($userText);
    // "páginas de…" / "grupos de…" are catalog searches, not product category asks.
    if (preg_match('/\b(paginas?|pagina)\b/u', $nPre) && !preg_match('/\b(como|crear|editar)\b/u', $nPre)) {
        return 'pagina';
    }
    if (preg_match('/\b(grupos?|comunidad)\b/u', $nPre) && !preg_match('/\b(como|crear|unir|unirme|unirse)\b/u', $nPre)) {
        return 'grupo';
    }
    if (zuitch_ai_looks_like_product_ask($userText)) {
        return 'producto';
    }

    $prompt = trim((string) zuitch_ai_cfg('zuitch_ai_intent_prompt', ''));
    if ($prompt === '') {
        $prompt = zuitch_ai_default_intent_prompt();
    }
    $out = zuitch_ai_chat_request(array(
        array('role' => 'system', 'content' => $prompt),
        array('role' => 'user', 'content' => $userText),
    ), array('max_tokens' => 8, 'temperature' => 0));
    $out = zuitch_ai_normalize($out);
    $out = preg_replace('/[^a-z]/', '', $out);
    $allowed = array('producto', 'pagina', 'grupo', 'soporte', 'chat');
    if (!in_array($out, $allowed, true)) {
        // Heuristic fallback
        if (preg_match('/\b(producto|precio|comprar|vender|auto|carro|coche|zapat|ropa|iphone)\b/u', zuitch_ai_normalize($userText))) {
            return 'producto';
        }
        if (preg_match('/\b(pagina|negocio|tienda|concesion)\b/u', zuitch_ai_normalize($userText))) {
            return 'pagina';
        }
        if (preg_match('/\b(grupo|comunidad)\b/u', zuitch_ai_normalize($userText))) {
            return 'grupo';
        }
        if (preg_match('/\b(como|contrasena|publicar|configur|ayuda|soporte)\b/u', zuitch_ai_normalize($userText))) {
            return 'soporte';
        }
        return 'chat';
    }
    // LLM sometimes tags how-tos as grupo/pagina/producto because of the noun.
    if (in_array($out, array('grupo', 'pagina', 'producto'), true) && zuitch_ai_is_howto_support($userText)) {
        return 'soporte';
    }
    return $out;
}

function zuitch_ai_levenshtein_ok($a, $b)
{
    $la = mb_strlen($a);
    $lb = mb_strlen($b);
    if (abs($la - $lb) > 2) {
        return false;
    }
    return levenshtein($a, $b) <= 2;
}

function zuitch_ai_suggest_word_fix($word, $candidates)
{
    $best = null;
    $bestDist = 99;
    $word = zuitch_ai_normalize($word);
    $wLen = mb_strlen($word);
    if ($wLen < 3) {
        return null;
    }
    $prefixLen = min(2, $wLen);
    $prefix = mb_substr($word, 0, $prefixLen);
    foreach ($candidates as $c) {
        $c = zuitch_ai_normalize($c);
        if ($c === '' || mb_strlen($c) < 3) {
            continue;
        }
        // Must share the same starting letters (avoids carro → cacao)
        if (mb_strpos($c, $prefix) !== 0) {
            continue;
        }
        $d = levenshtein($word, $c);
        $maxDist = ($wLen <= 4) ? 1 : 2;
        if ($d > 0 && $d <= $maxDist && $d < $bestDist) {
            $bestDist = $d;
            $best = $c;
        }
    }
    return $best;
}

function zuitch_ai_collect_name_tokens($type)
{
    global $sqlConnect;
    $tokens = array();
    if ($type === 'producto') {
        $q = mysqli_query($sqlConnect, "SELECT `name` FROM " . T_PRODUCTS . " WHERE `status` <> '1' LIMIT 500");
        while ($q && ($row = mysqli_fetch_assoc($q))) {
            foreach (preg_split('/\s+/', zuitch_ai_normalize($row['name'])) as $t) {
                if (mb_strlen($t) >= 2) {
                    $tokens[$t] = true;
                }
            }
        }
    } elseif ($type === 'pagina') {
        $q = mysqli_query($sqlConnect, "SELECT `page_name`,`page_title` FROM " . T_PAGES . " WHERE `active` = '1' LIMIT 800");
        while ($q && ($row = mysqli_fetch_assoc($q))) {
            foreach (array($row['page_name'], $row['page_title']) as $field) {
                foreach (preg_split('/\s+/', zuitch_ai_normalize($field)) as $t) {
                    if (mb_strlen($t) >= 2) {
                        $tokens[$t] = true;
                    }
                }
            }
        }
    } elseif ($type === 'grupo') {
        $q = mysqli_query($sqlConnect, "SELECT `group_name`,`group_title` FROM " . T_GROUPS . " WHERE `active` = '1' LIMIT 500");
        while ($q && ($row = mysqli_fetch_assoc($q))) {
            foreach (array($row['group_name'], $row['group_title']) as $field) {
                foreach (preg_split('/\s+/', zuitch_ai_normalize($field)) as $t) {
                    if (mb_strlen($t) >= 2) {
                        $tokens[$t] = true;
                    }
                }
            }
        }
    }
    return array_keys($tokens);
}

function zuitch_ai_rank_sql_score($columns, $words)
{
    global $sqlConnect;
    $parts = array();
    foreach ($words as $w) {
        $w = mysqli_real_escape_string($sqlConnect, $w);
        if ($w === '') {
            continue;
        }
        foreach ($columns as $col => $weight) {
            $parts[] = "(CASE WHEN `{$col}` LIKE '%{$w}%' THEN {$weight} ELSE 0 END)";
        }
    }
    if (empty($parts)) {
        return '0';
    }
    return '(' . implode(' + ', $parts) . ')';
}

function zuitch_ai_search_catalog($intent, $userText, $limit = 6)
{
    global $sqlConnect, $wo;
    $limit = max(1, min(12, (int) $limit));
    $extracted = zuitch_ai_extract_keywords($userText, true);
    $words = zuitch_ai_filter_catalog_words($extracted['all']);
    $original = zuitch_ai_filter_catalog_words($extracted['original']);
    $corrections = array();
    $cards = array();

    if (empty($original) && empty($words)) {
        return array('cards' => $cards, 'corrections_note' => '', 'words' => $words);
    }

    $nameTokens = zuitch_ai_collect_name_tokens($intent);
    $searchWords = !empty($original) ? $original : $words;

    // 1) Original topic keywords only (avoids weak synonym / type-word hits)
    $probe = zuitch_ai_run_catalog_query($intent, $searchWords, $limit);
    if (!empty($probe)) {
        $cards = $probe;
    } else {
        // 2) Add equivalents (also noise-filtered)
        $probe = zuitch_ai_run_catalog_query($intent, $words, $limit);
        if (!empty($probe)) {
            $cards = $probe;
            $searchWords = $words;
        } else {
            // 3) Typo fix only on original user words
            $searchWords = $words;
            foreach ($original as $w) {
                $found = false;
                foreach ($nameTokens as $tok) {
                    if ($tok === $w || mb_strpos($tok, $w) === 0) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    continue;
                }
                $prefixCands = array();
                $pref = mb_substr($w, 0, min(2, mb_strlen($w)));
                foreach ($nameTokens as $tok) {
                    if ($pref !== '' && mb_strpos($tok, $pref) === 0) {
                        $prefixCands[] = $tok;
                        if (count($prefixCands) >= 20) {
                            break;
                        }
                    }
                }
                $sugg = zuitch_ai_suggest_word_fix($w, $prefixCands);
                if ($sugg) {
                    $corrections[$w] = $sugg;
                    $searchWords[] = $sugg;
                }
            }
            $searchWords = zuitch_ai_filter_catalog_words(array_values(array_unique($searchWords)));
            $cards = zuitch_ai_run_catalog_query($intent, $searchWords, $limit);
        }
    }

    // Pages/groups: only keep hits that match the user's topic word (e.g. comida).
    // Products: if originals don't appear in titles (comer/food/trago), fall back to
    // synonym words so Hamburguesa/Whisky survive.
    $filterWords = !empty($original) ? $original : $searchWords;
    $filtered = zuitch_ai_filter_relevant_cards($cards, $filterWords);
    if (empty($filtered) && $intent === 'producto') {
        if (!empty($words)) {
            // Re-query with synonyms if the first pass only had weak/noisy originals.
            if (empty($cards) || $searchWords === $original) {
                $synProbe = zuitch_ai_run_catalog_query($intent, $words, $limit);
                if (!empty($synProbe)) {
                    $cards = $synProbe;
                    $searchWords = $words;
                }
            }
            $filtered = zuitch_ai_filter_relevant_cards($cards, $words);
        }
    }
    $cards = $filtered;

    $note = '';
    if (!empty($corrections)) {
        $bits = array();
        foreach ($corrections as $from => $to) {
            $bits[] = "\"{$from}\" se tomó como \"{$to}\"";
        }
        $note = implode('; ', $bits);
    }

    return array('cards' => $cards, 'corrections_note' => $note, 'words' => $searchWords);
}

function zuitch_ai_run_catalog_query($intent, $words, $limit)
{
    global $sqlConnect, $wo;
    $cards = array();
    if (empty($words)) {
        return $cards;
    }

    $site = !empty($wo['config']['site_url']) ? rtrim($wo['config']['site_url'], '/') : 'https://zuitch.com';

    if ($intent === 'producto') {
        $score = zuitch_ai_rank_sql_score(array('name' => 5, 'description' => 2), $words);
        $sql = "SELECT p.`id`, p.`name`, p.`price`, p.`description`, ({$score}) AS score,
            (SELECT po.`id` FROM " . T_POSTS . " po WHERE po.`product_id` = p.`id` ORDER BY po.`id` DESC LIMIT 1) AS post_id
            FROM " . T_PRODUCTS . " p
            WHERE p.`status` <> '1' HAVING score > 0 ORDER BY score DESC, p.`id` DESC LIMIT {$limit}";
        $q = mysqli_query($sqlConnect, $sql);
        while ($q && ($row = mysqli_fetch_assoc($q))) {
            $desc = mb_substr(strip_tags((string) $row['description']), 0, 120);
            $postId = !empty($row['post_id']) ? (int) $row['post_id'] : 0;
            $url = $postId > 0
                ? (function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=post&id=' . $postId) : ($site . '/post/' . $postId))
                : ($site . '/products');
            $cards[] = array(
                'type' => 'producto',
                'id' => (int) $row['id'],
                'title' => $row['name'],
                'price' => isset($row['price']) ? $row['price'] : '',
                'snippet' => $desc,
                'url' => $url,
            );
        }
    } elseif ($intent === 'pagina') {
        $score = zuitch_ai_rank_sql_score(array('page_title' => 5, 'page_name' => 4, 'page_description' => 2), $words);
        $sql = "SELECT `page_id`, `page_name`, `page_title`, `page_description`, ({$score}) AS score FROM " . T_PAGES
            . " WHERE `active` = '1' HAVING score > 0 ORDER BY score DESC, `page_id` DESC LIMIT {$limit}";
        $q = mysqli_query($sqlConnect, $sql);
        while ($q && ($row = mysqli_fetch_assoc($q))) {
            $url = function_exists('Wo_SeoLink')
                ? Wo_SeoLink('index.php?link1=timeline&u=' . $row['page_name'])
                : ($site . '/' . $row['page_name']);
            $cards[] = array(
                'type' => 'pagina',
                'id' => (int) $row['page_id'],
                'title' => $row['page_title'],
                'price' => '',
                'snippet' => mb_substr(strip_tags((string) $row['page_description']), 0, 120),
                'url' => $url,
            );
        }
    } elseif ($intent === 'grupo') {
        $score = zuitch_ai_rank_sql_score(array('group_title' => 5, 'group_name' => 4, 'about' => 2), $words);
        $hasAbout = false;
        $chk = mysqli_query($sqlConnect, "SHOW COLUMNS FROM " . T_GROUPS . " LIKE 'about'");
        if ($chk && mysqli_num_rows($chk)) {
            $hasAbout = true;
        }
        if (!$hasAbout) {
            $score = zuitch_ai_rank_sql_score(array('group_title' => 5, 'group_name' => 4), $words);
            $sql = "SELECT `id`, `group_name`, `group_title`, ({$score}) AS score FROM " . T_GROUPS
                . " WHERE `active` = '1' HAVING score > 0 ORDER BY score DESC, `id` DESC LIMIT {$limit}";
        } else {
            $sql = "SELECT `id`, `group_name`, `group_title`, `about`, ({$score}) AS score FROM " . T_GROUPS
                . " WHERE `active` = '1' HAVING score > 0 ORDER BY score DESC, `id` DESC LIMIT {$limit}";
        }
        $q = mysqli_query($sqlConnect, $sql);
        while ($q && ($row = mysqli_fetch_assoc($q))) {
            $snippet = !empty($row['about']) ? $row['about'] : '';
            $url = function_exists('Wo_SeoLink')
                ? Wo_SeoLink('index.php?link1=timeline&u=' . $row['group_name'])
                : ($site . '/' . $row['group_name']);
            $cards[] = array(
                'type' => 'grupo',
                'id' => (int) $row['id'],
                'title' => $row['group_title'],
                'price' => '',
                'snippet' => mb_substr(strip_tags((string) $snippet), 0, 120),
                'url' => $url,
            );
        }
    }
    return $cards;
}

function zuitch_ai_search_docs($userText, $limit = 3)
{
    $docs = (string) zuitch_ai_cfg('zuitch_ai_docs', '');
    if (trim($docs) === '') {
        return array();
    }
    $chunks = preg_split('/(?=^#\s+)/m', $docs);
    $words = zuitch_ai_extract_keywords($userText, true);
    $words = $words['all'];
    // Ignore ultra-common howto words so topic terms (empleo, reel, monedero…) win.
    $docNoise = array_flip(array('como', 'zuitch', 'sitio', 'plataforma', 'puedo', 'quiero'));
    $words = array_values(array_filter($words, function ($w) use ($docNoise) {
        return $w !== '' && !isset($docNoise[$w]);
    }));
    $scored = array();
    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }
        $norm = zuitch_ai_normalize($chunk);
        $titleNorm = zuitch_ai_normalize(mb_substr($chunk, 0, 80));
        $isTitleChunk = (mb_strpos($chunk, '#') === 0);
        $score = 0;
        $weakTitle = array_flip(array('publicar', 'publica', 'crear', 'usar', 'ver', 'abrir', 'cambiar', 'editar'));
        foreach ($words as $w) {
            if ($w !== '' && mb_strpos($norm, $w) !== false) {
                if ($isTitleChunk && mb_strpos($titleNorm, $w) !== false) {
                    $score += isset($weakTitle[$w]) ? 1 : 5;
                } else {
                    $score += 1;
                }
            }
        }
        if ($score > 0) {
            $scored[] = array('score' => $score, 'text' => mb_substr($chunk, 0, 900));
        }
    }
    usort($scored, function ($a, $b) {
        return $b['score'] - $a['score'];
    });
    $out = array();
    foreach (array_slice($scored, 0, $limit) as $item) {
        $out[] = array(
            'type' => 'doc',
            'id' => 0,
            'title' => 'Documentación',
            'price' => '',
            'snippet' => $item['text'],
            'url' => '',
        );
    }
    return $out;
}

function zuitch_ai_find_corrections($userText, $limit = 2)
{
    global $sqlConnect;
    $limit = max(1, min(5, (int) $limit));
    $words = zuitch_ai_extract_keywords($userText, false);
    $words = $words['original'];
    if (empty($words)) {
        return array();
    }
    $likeParts = array();
    foreach (array_slice($words, 0, 5) as $w) {
        $wEsc = mysqli_real_escape_string($sqlConnect, $w);
        $likeParts[] = "u.`content` LIKE '%{$wEsc}%'";
    }
    $where = implode(' OR ', $likeParts);
    // Only the immediate previous user turn (avoids leaking corrections across turns in the same chat).
    $sql = "SELECT r.`admin_ok`, r.`admin_correction`, u.`content` AS question, a.`content` AS answer
        FROM " . T_ZUITCH_AI_RATINGS . " r
        INNER JOIN " . T_ZUITCH_AI_MESSAGES . " a ON a.`id` = r.`message_id` AND a.`role` = 'assistant'
        INNER JOIN " . T_ZUITCH_AI_MESSAGES . " u ON u.`id` = (
            SELECT u2.`id` FROM " . T_ZUITCH_AI_MESSAGES . " u2
            WHERE u2.`chat_id` = a.`chat_id` AND u2.`role` = 'user' AND u2.`id` < a.`id`
            ORDER BY u2.`id` DESC LIMIT 1
        )
        WHERE (r.`admin_ok` = 1 OR (r.`admin_correction` IS NOT NULL AND r.`admin_correction` <> ''))
          AND ({$where})
        ORDER BY r.`id` DESC
        LIMIT 40";
    $q = mysqli_query($sqlConnect, $sql);
    $seen = array();
    $scored = array();
    $qNorm = zuitch_ai_normalize($userText);
    $need = max(1, (int) ceil(count($words) * 0.6));
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $key = md5($row['question'] . '|' . $row['admin_correction'] . '|' . $row['answer']);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $reply = trim((string) $row['admin_correction']);
        if ($reply === '' && (int) $row['admin_ok'] === 1) {
            $reply = trim((string) $row['answer']);
        }
        if ($reply === '') {
            continue;
        }
        $q2 = zuitch_ai_normalize($row['question']);
        $hits = 0;
        foreach ($words as $w) {
            if ($w !== '' && mb_strpos($q2, $w) !== false) {
                $hits++;
            }
        }
        if ($hits < $need) {
            continue;
        }
        // Prefer closer questions (shared length / containment).
        $score = ($hits * 10) - abs(mb_strlen($qNorm) - mb_strlen($q2));
        $scored[] = array(
            'score' => $score,
            'question' => mb_substr($row['question'], 0, 300),
            'answer' => mb_substr($reply, 0, 800),
        );
    }
    usort($scored, function ($a, $b) {
        return $b['score'] - $a['score'];
    });
    $out = array();
    foreach (array_slice($scored, 0, $limit) as $item) {
        $out[] = array(
            'question' => $item['question'],
            'answer' => $item['answer'],
        );
    }
    return $out;
}

function zuitch_ai_format_catalog_answer($cards, $intent = 'producto')
{
    if (empty($cards)) {
        return '';
    }
    $label = ($intent === 'pagina') ? 'páginas' : (($intent === 'grupo') ? 'grupos' : 'productos');
    $lines = array("Encontré estos {$label} en Zuitch:");
    foreach ($cards as $c) {
        if (($c['type'] ?? '') === 'doc') {
            continue;
        }
        $title = trim((string) ($c['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $price = (isset($c['price']) && $c['price'] !== '' && $c['price'] !== null) ? " — precio {$c['price']}" : '';
        $url = !empty($c['url']) ? " — {$c['url']}" : '';
        $lines[] = "• {$title}{$price}{$url}";
    }
    if (count($lines) < 2) {
        return '';
    }
    $lines[] = '¿Te sirve alguno o quieres buscar con otra palabra?';
    return implode("\n", $lines);
}

function zuitch_ai_format_context($cards, $correctionsNote = '')
{
    if (empty($cards)) {
        $ctx = '';
        if ($correctionsNote !== '') {
            $ctx = "Nota de corrección de búsqueda: {$correctionsNote}";
        }
        return $ctx;
    }
    $lines = array();
    if ($correctionsNote !== '') {
        $lines[] = "Nota: {$correctionsNote}";
    }
    foreach ($cards as $i => $c) {
        $n = $i + 1;
        if ($c['type'] === 'doc') {
            $lines[] = "[{$n}] DOC:\n" . $c['snippet'];
            continue;
        }
        $price = ($c['price'] !== '' && $c['price'] !== null) ? " | precio: {$c['price']}" : '';
        $url = !empty($c['url']) ? " | url: {$c['url']}" : '';
        $lines[] = "[{$n}] {$c['type']}: {$c['title']}{$price}{$url} | {$c['snippet']}";
    }
    return implode("\n", $lines);
}

function zuitch_ai_format_corrections_block($items)
{
    if (empty($items)) {
        return '';
    }
    $lines = array();
    foreach ($items as $i => $it) {
        $n = $i + 1;
        $lines[] = "[{$n}] Pregunta: {$it['question']}\nRespuesta aprobada: {$it['answer']}";
    }
    return implode("\n\n", $lines);
}

function zuitch_ai_count_today($userId)
{
    global $sqlConnect;
    $userId = (int) $userId;
    $start = strtotime('today');
    $q = mysqli_query($sqlConnect, "SELECT COUNT(*) AS c FROM " . T_ZUITCH_AI_MESSAGES
        . " WHERE `user_id` = {$userId} AND `role` = 'user' AND `time` >= {$start}");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (int) $row['c'];
    }
    return 0;
}

function zuitch_ai_get_chat($chatId, $userId = 0)
{
    global $sqlConnect;
    $chatId = (int) $chatId;
    $sql = "SELECT * FROM " . T_ZUITCH_AI_CHATS . " WHERE `id` = {$chatId}";
    if ($userId > 0) {
        $userId = (int) $userId;
        $sql .= " AND `user_id` = {$userId}";
    }
    $q = mysqli_query($sqlConnect, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return $row;
    }
    return null;
}

function zuitch_ai_list_chats($userId, $limit = 40)
{
    global $sqlConnect;
    $userId = (int) $userId;
    $limit = (int) $limit;
    $rows = array();
    $q = mysqli_query($sqlConnect, "SELECT * FROM " . T_ZUITCH_AI_CHATS
        . " WHERE `user_id` = {$userId} ORDER BY `updated_at` DESC LIMIT {$limit}");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $rows[] = $row;
    }
    return $rows;
}

function zuitch_ai_create_chat($userId, $title = 'Nueva conversación')
{
    global $sqlConnect;
    $userId = (int) $userId;
    $title = mysqli_real_escape_string($sqlConnect, mb_substr($title, 0, 180));
    $t = time();
    mysqli_query($sqlConnect, "INSERT INTO " . T_ZUITCH_AI_CHATS
        . " (`user_id`,`title`,`time`,`updated_at`) VALUES ({$userId},'{$title}',{$t},{$t})");
    return (int) mysqli_insert_id($sqlConnect);
}

function zuitch_ai_delete_chat($chatId, $userId)
{
    global $sqlConnect;
    $chatId = (int) $chatId;
    $userId = (int) $userId;
    $chat = zuitch_ai_get_chat($chatId, $userId);
    if (!$chat) {
        return false;
    }
    $ids = array();
    $q = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ZUITCH_AI_MESSAGES . " WHERE `chat_id` = {$chatId}");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $ids[] = (int) $row['id'];
    }
    if (!empty($ids)) {
        $in = implode(',', $ids);
        mysqli_query($sqlConnect, "DELETE FROM " . T_ZUITCH_AI_RATINGS . " WHERE `message_id` IN ({$in})");
    }
    mysqli_query($sqlConnect, "DELETE FROM " . T_ZUITCH_AI_MESSAGES . " WHERE `chat_id` = {$chatId}");
    mysqli_query($sqlConnect, "DELETE FROM " . T_ZUITCH_AI_CHATS . " WHERE `id` = {$chatId} AND `user_id` = {$userId}");
    return true;
}

function zuitch_ai_delete_all_chats($userId)
{
    global $sqlConnect;
    $userId = (int) $userId;
    $chats = zuitch_ai_list_chats($userId, 500);
    foreach ($chats as $c) {
        zuitch_ai_delete_chat((int) $c['id'], $userId);
    }
    return true;
}

function zuitch_ai_add_message($chatId, $userId, $role, $content, $intent = '', $cards = null)
{
    global $sqlConnect;
    $chatId = (int) $chatId;
    $userId = (int) $userId;
    $role = mysqli_real_escape_string($sqlConnect, $role);
    $contentEsc = mysqli_real_escape_string($sqlConnect, $content);
    $intent = mysqli_real_escape_string($sqlConnect, $intent);
    $cardsJson = $cards === null ? 'NULL' : ("'" . mysqli_real_escape_string($sqlConnect, json_encode($cards, JSON_UNESCAPED_UNICODE)) . "'");
    $t = time();
    mysqli_query($sqlConnect, "INSERT INTO " . T_ZUITCH_AI_MESSAGES
        . " (`chat_id`,`user_id`,`role`,`content`,`intent`,`cards_json`,`time`) VALUES "
        . "({$chatId},{$userId},'{$role}','{$contentEsc}','{$intent}',{$cardsJson},{$t})");
    $id = (int) mysqli_insert_id($sqlConnect);
    mysqli_query($sqlConnect, "UPDATE " . T_ZUITCH_AI_CHATS . " SET `updated_at` = {$t} WHERE `id` = {$chatId}");
    return $id;
}

function zuitch_ai_get_messages($chatId, $limit = 100)
{
    global $sqlConnect;
    $chatId = (int) $chatId;
    $limit = (int) $limit;
    $rows = array();
    $q = mysqli_query($sqlConnect, "SELECT * FROM " . T_ZUITCH_AI_MESSAGES
        . " WHERE `chat_id` = {$chatId} ORDER BY `id` ASC LIMIT {$limit}");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $row['cards'] = array();
        if (!empty($row['cards_json'])) {
            $decoded = json_decode($row['cards_json'], true);
            if (is_array($decoded)) {
                $row['cards'] = $decoded;
            }
        }
        $rows[] = $row;
    }
    return $rows;
}

function zuitch_ai_history_for_llm($chatId, $take = 6)
{
    $msgs = zuitch_ai_get_messages($chatId, 200);
    $msgs = array_slice($msgs, -$take);
    $out = array();
    foreach ($msgs as $m) {
        $role = ($m['role'] === 'assistant') ? 'assistant' : 'user';
        $content = mb_substr((string) $m['content'], 0, 1200);
        $out[] = array('role' => $role, 'content' => $content);
    }
    return $out;
}

function zuitch_ai_build_reply($userText, $chatId, $imagePayload = null)
{
    $maxCards = (int) zuitch_ai_cfg('zuitch_ai_max_cards', 6);
    if ($maxCards < 1) {
        $maxCards = 6;
    }
    $intent = zuitch_ai_classify_intent($userText);
    $chatOwnerId = 0;
    $chatRow = zuitch_ai_get_chat((int) $chatId, 0);
    if ($chatRow && !empty($chatRow['user_id'])) {
        $chatOwnerId = (int) $chatRow['user_id'];
    } elseif (!empty($GLOBALS['wo']['user']['user_id'])) {
        $chatOwnerId = (int) $GLOBALS['wo']['user']['user_id'];
    } else {
        global $wo;
        if (!empty($wo['user']['user_id'])) {
            $chatOwnerId = (int) $wo['user']['user_id'];
        } elseif (!empty($wo['user']['id'])) {
            $chatOwnerId = (int) $wo['user']['id'];
        }
    }
    $msgsHist = zuitch_ai_get_messages((int) $chatId, 40);
    $lastAssistantIntent = '';
    for ($i = count($msgsHist) - 1; $i >= 0; $i--) {
        if (($msgsHist[$i]['role'] ?? '') === 'assistant' && !empty($msgsHist[$i]['intent'])) {
            $lastAssistantIntent = (string) $msgsHist[$i]['intent'];
            break;
        }
    }
    // “Quién soy” / “qué es Zuitch” no van al catálogo.
    if (zuitch_ai_is_whoami_ask($userText)) {
        $intent = 'chat';
    } elseif (zuitch_ai_is_platform_identity_ask($userText)) {
        $intent = 'soporte';
    }
    // Keep product refinement follow-ups in catalog mode (e.g. "cuál es más barato?").
    if ($intent !== 'producto' && zuitch_ai_is_product_followup($userText) && !zuitch_ai_is_external_shop_request($userText)
        && !zuitch_ai_is_whoami_ask($userText) && !zuitch_ai_is_platform_identity_ask($userText)) {
        if ($lastAssistantIntent === 'producto') {
            $intent = 'producto';
        }
    }
    // Live/reels/chat follow-ups about cámara/micrófono stay in support.
    $nFollow = zuitch_ai_normalize($userText);
    if ($lastAssistantIntent === 'soporte' && preg_match('/\b(camara|microfono|permiso|navegador|transmitir)\b/u', $nFollow)) {
        $intent = 'soporte';
    }
    $cards = array();
    $note = '';

    if (in_array($intent, array('producto', 'pagina', 'grupo'), true)) {
        $searchText = $userText;
        if ($intent === 'producto' && zuitch_ai_is_product_followup($userText)) {
            $prev = zuitch_ai_previous_user_text($chatId, $userText);
            if ($prev !== '') {
                $searchText = $prev . ' ' . $userText;
            }
        }
        $search = zuitch_ai_search_catalog($intent, $searchText, $maxCards);
        $cards = $search['cards'];
        $note = $search['corrections_note'];
        // If follow-up alone yielded nothing, retry with previous topic only.
        if (empty($cards) && $searchText !== $userText) {
            $prev = zuitch_ai_previous_user_text($chatId, $userText);
            if ($prev !== '') {
                $search = zuitch_ai_search_catalog($intent, $prev, $maxCards);
                $cards = $search['cards'];
                if ($note === '') {
                    $note = $search['corrections_note'];
                }
            }
        }
    } elseif ($intent === 'soporte') {
        $docQuery = $userText;
        if (zuitch_ai_is_platform_identity_ask($userText)) {
            $docQuery = 'Qué es Zuitch guía de la plataforma empezar';
        }
        $cards = zuitch_ai_search_docs($docQuery, 3);
        if (zuitch_ai_is_platform_identity_ask($userText)) {
            $idCard = zuitch_ai_platform_identity_card();
            $hasId = false;
            foreach ($cards as $c) {
                if (!empty($c['snippet']) && stripos((string) $c['snippet'], 'red social') !== false) {
                    $hasId = true;
                    break;
                }
            }
            if (!$hasId) {
                array_unshift($cards, $idCard);
            }
            if (empty($cards)) {
                $cards = array($idCard);
            }
        }
    } elseif ($intent === 'chat' && zuitch_ai_is_external_shop_request($userText)) {
        $cards = array();
        $note = '';
    }

    $corrections = zuitch_ai_find_corrections($userText, 2);

    // Catalog hits: answer from real cards only (exact titles/URLs, no LLM rename/hallucination).
    if (in_array($intent, array('producto', 'pagina', 'grupo'), true) && !empty($cards) && empty($imagePayload)) {
        $answer = zuitch_ai_format_catalog_answer($cards, $intent);
        if ($answer === '' && !empty($corrections[0]['answer'])) {
            $answer = $corrections[0]['answer'];
        }
        if ($answer !== '' && $note !== '') {
            $answer = "Nota: {$note}\n\n" . $answer;
        }
        if ($answer !== '') {
            return array(
                'intent' => $intent,
                'answer' => $answer,
                'cards' => $cards,
                'corrections_note' => $note,
            );
        }
    }

    $contexto = zuitch_ai_format_context($cards, $note);
    $corrBlock = zuitch_ai_format_corrections_block($corrections);

    $replyPrompt = trim((string) zuitch_ai_cfg('zuitch_ai_reply_prompt', ''));
    if ($replyPrompt === '') {
        $replyPrompt = zuitch_ai_default_reply_prompt();
    } elseif (strpos($replyPrompt, '{usuario}') === false) {
        $replyPrompt .= "\n\nIdentidad: Zuitch (Switch/Suichi) es la red social y mercado en https://zuitch.com. Si preguntan qué es, explícalo en 2–5 frases y ofrece ayuda concreta.\n"
            . "Usuario que escribe ahora:\n{usuario}\n"
            . "Si pregunta quién soy, mi usuario o mi perfil, responde con ese bloque. No inventes datos. No reveles correo, teléfono, contraseña ni datos de otras personas.\n";
    }
    $userBlock = zuitch_ai_user_context_block($chatOwnerId);
    $replyPrompt = str_replace(
        array('{contexto}', '{correcciones}', '{usuario}'),
        array(
            $contexto === '' ? '(vacío)' : $contexto,
            $corrBlock === '' ? '(vacío)' : $corrBlock,
            $userBlock,
        ),
        $replyPrompt
    );

    $messages = array(
        array('role' => 'system', 'content' => $replyPrompt),
    );
    $history = zuitch_ai_history_for_llm($chatId, 6);
    // Drop trailing user turn if it matches current text (already saved before this call)
    $histCount = count($history);
    if ($histCount > 0 && $history[$histCount - 1]['role'] === 'user') {
        $lastContent = $history[$histCount - 1]['content'];
        if (is_string($lastContent) && $lastContent === $userText) {
            array_pop($history);
        }
    }
    foreach ($history as $h) {
        $messages[] = $h;
    }

    if (!empty($imagePayload) && is_array($imagePayload)) {
        $messages[] = array(
            'role' => 'user',
            'content' => array(
                array('type' => 'text', 'text' => $userText),
                $imagePayload,
            ),
        );
    } else {
        $messages[] = array('role' => 'user', 'content' => $userText);
    }

    $answer = zuitch_ai_chat_request($messages, array('max_tokens' => 700, 'temperature' => 0.3));
    return array(
        'intent' => $intent,
        'answer' => $answer,
        'cards' => $cards,
        'corrections_note' => $note,
    );
}

function zuitch_ai_admin_list_chats($q = '', $limit = 50, $offset = 0)
{
    global $sqlConnect;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $where = '1=1';
    if ($q !== '') {
        $safe = mysqli_real_escape_string($sqlConnect, $q);
        $where = "(u.`username` LIKE '%{$safe}%' OR u.`first_name` LIKE '%{$safe}%' OR c.`title` LIKE '%{$safe}%'
            OR EXISTS (SELECT 1 FROM " . T_ZUITCH_AI_MESSAGES . " m WHERE m.`chat_id` = c.`id` AND m.`content` LIKE '%{$safe}%' LIMIT 1))";
    }
    $sql = "SELECT c.*, u.`username`, u.`avatar`,
        (SELECT COUNT(*) FROM " . T_ZUITCH_AI_MESSAGES . " m WHERE m.`chat_id` = c.`id`) AS msg_count,
        (SELECT m2.`content` FROM " . T_ZUITCH_AI_MESSAGES . " m2 WHERE m2.`chat_id` = c.`id` ORDER BY m2.`id` DESC LIMIT 1) AS last_message
        FROM " . T_ZUITCH_AI_CHATS . " c
        LEFT JOIN " . T_USERS . " u ON u.`user_id` = c.`user_id`
        WHERE {$where}
        ORDER BY c.`updated_at` DESC
        LIMIT {$offset}, {$limit}";
    $rows = array();
    $res = mysqli_query($sqlConnect, $sql);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $rows[] = $row;
    }
    return $rows;
}
