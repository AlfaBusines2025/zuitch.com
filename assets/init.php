<?php
@ini_set('session.cookie_httponly', 1);
@ini_set('session.use_only_cookies', 1);
$__zuitch_https =
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
if ($__zuitch_https) {
    @ini_set('session.cookie_secure', '1');
}
session_start();

/* --------- SISTEMA DE LOGGING DE CONEXIONES --------- */
// Cargar el sistema de logging para diagnóstico de conexiones
// Para activar/desactivar: editar assets/includes/connection_log_config.php
require_once(__DIR__ . '/includes/connection_logger.php');
// Ejecutar el logging de la conexión actual
if (function_exists('logConnection')) {
    logConnection();
}

/* --------- LOAD CORE --------- */
require_once('assets/libraries/DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php');
require_once('includes/cache.php');
require_once('includes/functions_general.php');
require_once('includes/tabels.php');
require_once('includes/functions_one.php');
require_once('includes/functions_two.php');
require_once('includes/functions_three.php');
if (file_exists(getcwd() . '/assets/vaneayoung/LiveStream/classes/class.LiveStream.php')) {
    require_once('vaneayoung/LiveStream/classes/class.LiveStream.php');
}

/* ============================================================
   AUTENTICACIÓN SILENCIOSA (WEBVIEW / ?access_token=)
   ============================================================ */

/* 0️⃣  Fallback: si la versión no trae Wo_UserIdFromAccessToken() */
if (!function_exists('Wo_UserIdFromAccessToken')) {
    function Wo_UserIdFromAccessToken($token)
    {
        global $sqlConnect;
        $token = Wo_Secure($token);
        $q     = mysqli_query($sqlConnect,
                  "SELECT user_id FROM " . T_USERS . "
                   WHERE access_token = '{$token}' LIMIT 1");
        if (mysqli_num_rows($q) == 1) {
            $f = mysqli_fetch_assoc($q);
            return (int) $f['user_id'];
        }
        return 0;
    }
}

/* 1️⃣  Si aún NO hay sesión */
if (empty($_SESSION['user_id'])) {

    /* 1.1  Buscar token: ?access_token=… o cookie */
    $mobile_token = '';
    if (!empty($_GET['access_token'])) {
        $mobile_token = $_GET['access_token'];
    } elseif (!empty($_COOKIE['access_token'])) {
        $mobile_token = $_COOKIE['access_token'];
    }

    /* 1.2  Validar token */
    if ($mobile_token) {
        $uid = Wo_UserIdFromAccessToken($mobile_token);
        if ($uid) {

            /* 1.3  Crear sesión y rellenar superglobales para ESTE request */
            $session             = Wo_CreateLoginSession($uid);
            $_SESSION['user_id'] = $session;
            $_COOKIE['user_id']  = $session;        // << clave para que WoWonder ya te vea logueado
            $_COOKIE['access_token'] = $mobile_token;

            /* 1.4  Propagar cookies al navegador */
            setcookie('user_id', $session, time() + (10 * 365 * 24 * 60 * 60), '/', '', true, true);
            setcookie('access_token', $mobile_token, time() + (10 * 365 * 24 * 60 * 60), '/', '', true, true);

            /* 1.5  Limpiar la URL (opcional) */
            if (isset($_GET['access_token'])) {
                $clean = strtok($_SERVER['REQUEST_URI'], '?');
                header('Location: ' . $clean);
                exit;
            }
        }
    }
}
/* ============================================================ */
