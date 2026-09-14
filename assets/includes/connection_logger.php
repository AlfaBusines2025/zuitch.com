<?php
/**
 * Sistema de logging de conexiones para diagnóstico
 * 
 * Este sistema registra información detallada sobre las conexiones
 * para ayudar a diagnosticar problemas con webview móvil
 */

if (!defined('ENABLE_CONNECTION_LOG')) {
    require_once(__DIR__ . '/connection_log_config.php');
}

/**
 * Detecta si la conexión viene de un webview móvil
 */
function isWebViewMobile() {
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
    
    // Detectar webview de Android
    $isAndroidWebView = (
        strpos($userAgent, 'android') !== false && 
        (
            strpos($userAgent, 'wv') !== false || // Android WebView
            strpos($userAgent, 'version/') === false || // No tiene "Version/" típico de Chrome
            (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'safari') === false)
        )
    );
    
    // Detectar webview de iOS
    $isIOSWebView = (
        (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) &&
        strpos($userAgent, 'safari') === false &&
        strpos($userAgent, 'crios') === false &&
        strpos($userAgent, 'fxios') === false
    );
    
    // Detectar headers específicos de webview
    $hasWebViewHeaders = (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_X_WAP_PROFILE']) && strpos($_SERVER['HTTP_X_WAP_PROFILE'], 'webview') !== false)
    );
    
    return $isAndroidWebView || $isIOSWebView || $hasWebViewHeaders;
}

/**
 * Obtiene información del dispositivo/cliente
 */
function getClientInfo() {
    $info = array();
    
    // User Agent
    $info['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A';
    
    // IP del cliente
    $info['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $info['ip'] .= ' (X-Forwarded-For: ' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ')';
    }
    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $info['ip'] .= ' (X-Real-IP: ' . $_SERVER['HTTP_X_REAL_IP'] . ')';
    }
    
    // Método HTTP
    $info['method'] = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A';
    
    // URL solicitada
    $info['url'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A';
    $info['full_url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                        '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . 
                        (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
    
    // Protocolo
    $info['protocol'] = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'N/A';
    
    // Timestamp
    $info['timestamp'] = date('Y-m-d H:i:s');
    $info['timestamp_unix'] = time();
    
    // Detección de webview
    $info['is_webview'] = isWebViewMobile();
    $info['is_mobile'] = preg_match('/(android|iphone|ipad|mobile)/i', $info['user_agent']);
    
    // Headers importantes
    $info['headers'] = array();
    $importantHeaders = array(
        'HTTP_ACCEPT',
        'HTTP_ACCEPT_LANGUAGE',
        'HTTP_ACCEPT_ENCODING',
        'HTTP_REFERER',
        'HTTP_X_REQUESTED_WITH',
        'HTTP_X_WAP_PROFILE',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_COOKIE',
        'HTTP_AUTHORIZATION'
    );
    
    foreach ($importantHeaders as $header) {
        if (isset($_SERVER[$header])) {
            $headerName = str_replace('HTTP_', '', $header);
            $headerValue = $_SERVER[$header];
            
            // Ocultar valores sensibles en cookies y authorization
            if ($header === 'HTTP_COOKIE' || $header === 'HTTP_AUTHORIZATION') {
                $headerValue = substr($headerValue, 0, 100) . '... [truncado por seguridad]';
            }
            
            $info['headers'][$headerName] = $headerValue;
        }
    }
    
    // Información de sesión
    $info['session_id'] = session_id() ?: 'N/A';
    $info['has_session'] = !empty($_SESSION);
    
    // Información de autenticación
    $info['has_access_token'] = !empty($_GET['access_token']) || !empty($_COOKIE['access_token']);
    $info['has_user_id_cookie'] = !empty($_COOKIE['user_id']);
    $info['has_user_session'] = !empty($_SESSION['user_id']);
    
    // Query parameters (sin valores sensibles)
    if (!empty($_GET)) {
        $info['get_params'] = array();
        foreach ($_GET as $key => $value) {
            if (in_array(strtolower($key), array('access_token', 'password', 'token', 'key'))) {
                $info['get_params'][$key] = substr($value, 0, 10) . '... [oculto]';
            } else {
                $info['get_params'][$key] = is_array($value) ? '[array]' : substr($value, 0, 100);
            }
        }
    }
    
    return $info;
}

/**
 * Escribe el log de conexión
 */
function logConnection() {
    // Verificar si el logging está activado
    if (!defined('ENABLE_CONNECTION_LOG') || !ENABLE_CONNECTION_LOG) {
        return;
    }
    
    // Verificar si solo debemos loguear webview
    if (defined('LOG_ONLY_WEBVIEW') && LOG_ONLY_WEBVIEW && !isWebViewMobile()) {
        return;
    }
    
    // Obtener información del cliente
    $clientInfo = getClientInfo();
    
    // Crear directorio de logs si no existe
    $logDir = defined('CONNECTION_LOG_PATH') ? CONNECTION_LOG_PATH : __DIR__ . '/../../logs/connection_logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Nombre del archivo de log (uno por día)
    $logFilePrefix = defined('CONNECTION_LOG_FILE_PREFIX') ? CONNECTION_LOG_FILE_PREFIX : 'connection_log_';
    $logFile = $logDir . $logFilePrefix . date('Y-m-d') . '.log';
    
    // Verificar tamaño del archivo si hay límite
    if (defined('CONNECTION_LOG_MAX_SIZE_MB') && CONNECTION_LOG_MAX_SIZE_MB > 0) {
        if (file_exists($logFile) && filesize($logFile) > (CONNECTION_LOG_MAX_SIZE_MB * 1024 * 1024)) {
            // Rotar el archivo
            $backupFile = $logFile . '.' . time() . '.bak';
            @rename($logFile, $backupFile);
        }
    }
    
    // Preparar el mensaje de log según el nivel
    $logLevel = defined('CONNECTION_LOG_LEVEL') ? CONNECTION_LOG_LEVEL : 'detailed';
    
    $logEntry = array();
    $logEntry['timestamp'] = $clientInfo['timestamp'];
    $logEntry['ip'] = $clientInfo['ip'];
    $logEntry['method'] = $clientInfo['method'];
    $logEntry['url'] = $clientInfo['url'];
    $logEntry['is_webview'] = $clientInfo['is_webview'] ? 'YES' : 'NO';
    $logEntry['is_mobile'] = $clientInfo['is_mobile'] ? 'YES' : 'NO';
    $logEntry['user_agent'] = $clientInfo['user_agent'];
    
    if ($logLevel === 'detailed' || $logLevel === 'full') {
        $logEntry['full_url'] = $clientInfo['full_url'];
        $logEntry['protocol'] = $clientInfo['protocol'];
        $logEntry['session_id'] = $clientInfo['session_id'];
        $logEntry['has_session'] = $clientInfo['has_session'] ? 'YES' : 'NO';
        $logEntry['has_access_token'] = $clientInfo['has_access_token'] ? 'YES' : 'NO';
        $logEntry['has_user_id_cookie'] = $clientInfo['has_user_id_cookie'] ? 'YES' : 'NO';
        $logEntry['has_user_session'] = $clientInfo['has_user_session'] ? 'YES' : 'NO';
        
        if (!empty($clientInfo['headers'])) {
            $logEntry['headers'] = $clientInfo['headers'];
        }
        
        if (!empty($clientInfo['get_params'])) {
            $logEntry['get_params'] = $clientInfo['get_params'];
        }
    }
    
    if ($logLevel === 'full' && !empty($_POST)) {
        $postData = array();
        foreach ($_POST as $key => $value) {
            if (in_array(strtolower($key), array('password', 'access_token', 'token', 'key'))) {
                $postData[$key] = '[oculto por seguridad]';
            } else {
                $postData[$key] = is_array($value) ? '[array]' : substr($value, 0, 200);
            }
        }
        $logEntry['post_data'] = $postData;
    }
    
    // Formatear el log como JSON para fácil lectura
    $logLine = json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Agregar separador visual
    $separator = "\n" . str_repeat('=', 80) . "\n";
    
    // Escribir al archivo
    @file_put_contents($logFile, $separator . $logLine . "\n", FILE_APPEND | LOCK_EX);
}

// La función logConnection() se llamará manualmente desde init.php
// después de que la sesión esté iniciada

