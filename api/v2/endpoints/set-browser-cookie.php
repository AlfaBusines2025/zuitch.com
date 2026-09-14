<?php
// +------------------------------------------------------------------------+
// | WoWonder - set-browser-cookie + redirección con lang normalizado
// +------------------------------------------------------------------------+
$response_data = array(
    'api_status' => 400,
);

// ================== LENGUAJE ==================

// Nombre por defecto que WoWonder entiende
$default_lang = 'english';

// Lo que llegue en la request (puede ser "en", "english", "es-ES", etc.)
$raw_lang = isset($_REQUEST['lang']) ? strtolower(trim($_REQUEST['lang'])) : '';

// Mapa: abreviatura / alias => nombre completo que WoWonder espera
$lang_aliases = array(
    // === De tu modal ===
    'arabic'      => 'arabic',
    'ar'          => 'arabic',

    'bengali'     => 'bengali',
    'bn'          => 'bengali',

    'chinese'     => 'chinese',
    'zh'          => 'chinese',

    'croatian'    => 'croatian',
    'hr'          => 'croatian',

    'danish'      => 'danish',
    'da'          => 'danish',

    'dutch'       => 'dutch',
    'nl'          => 'dutch',

    'english'     => 'english',
    'en'          => 'english',

    'filipino'    => 'filipino',
    'fil'         => 'filipino',
    'tl'          => 'filipino',

    'french'      => 'french',
    'fr'          => 'french',

    'german'      => 'german',
    'de'          => 'german',

    'hebrew'      => 'hebrew',
    'he'          => 'hebrew',
    'iw'          => 'hebrew',

    'hindi'       => 'hindi',
    'hi'          => 'hindi',

    'indonesian'  => 'indonesian',
    'id'          => 'indonesian',

    'italian'     => 'italian',
    'it'          => 'italian',

    'japanese'    => 'japanese',
    'ja'          => 'japanese',

    'korean'      => 'korean',
    'ko'          => 'korean',

    'persian'     => 'persian',
    'fa'          => 'persian',

    'portuguese'  => 'portuguese',
    'pt'          => 'portuguese',

    'russian'     => 'russian',
    'ru'          => 'russian',

    'spanish'     => 'spanish',
    'es'          => 'spanish',

    'swedish'     => 'swedish',
    'sv'          => 'swedish',

    'turkish'     => 'turkish',
    'tr'          => 'turkish',

    'urdu'        => 'urdu',
    'ur'          => 'urdu',

    'vietnamese'  => 'vietnamese',
    'vi'          => 'vietnamese',

    // === Extras de tu código C# anterior (por si los usas) ===
    'greek'       => 'greek',
    'el'          => 'greek',

    'romanian'    => 'romanian',
    'ro'          => 'romanian',

    'albanian'    => 'albanian',
    'sq'          => 'albanian',

    'serbian'     => 'serbian',
    'sr'          => 'serbian',
);

// Valor final que enviaremos en la URL de redirección
$lang = $default_lang;

if (!empty($raw_lang)) {
    // 1) Coincidencia exacta (en, english, es, spanish, etc.)
    if (isset($lang_aliases[$raw_lang])) {
        $lang = $lang_aliases[$raw_lang];
    } else {
        // 2) Formatos tipo "en-US" o "es_ES"
        if (preg_match('/^[a-z]{2}[-_]/', $raw_lang)) {
            $short = substr($raw_lang, 0, 2);
            if (isset($lang_aliases[$short])) {
                $lang = $lang_aliases[$short];
            }
        } else {
            // 3) Fallback: buscar el código o la palabra dentro del string
            //    Ej: "English (US)" => contiene "english"
            foreach ($lang_aliases as $key => $value) {
                if (strpos($raw_lang, $key) !== false) {
                    $lang = $value;
                    break;
                }
            }
        }
    }
}

// ================== URL REDIRECCIÓN ==================

$default_redirect = 'https://zuitch.com';

$url_redirect = !empty($_REQUEST['url_redirect'])
    ? trim($_REQUEST['url_redirect'])
    : $default_redirect;

$redirect_used = ($url_redirect === $default_redirect) ? 'defecto' : 'parametro';

// ================== LOGIN + COOKIE + REDIRECT ==================

if (!empty(Wo_GetUserFromSessionID($_GET['access_token']))) {
    $cookie = Wo_Secure($_GET['access_token']);
    $_SESSION['user_id'] = $cookie;

    // 20 años
    setcookie("user_id", $cookie, time() + (20 * 365 * 24 * 60 * 60));

    // Añadimos lang NOMBRE COMPLETO a la URL de redirección
    if (!empty($lang)) {
        $separator = (parse_url($url_redirect, PHP_URL_QUERY) ? '&' : '?');
        $url_redirect .= $separator . 'lang=' . urlencode($lang);
    }

    header("Location: " . $url_redirect);
    exit();
}