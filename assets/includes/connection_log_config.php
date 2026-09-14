<?php
/**
 * Configuración del sistema de logging de conexiones
 * 
 * Para ACTIVAR el logging: cambia ENABLE_CONNECTION_LOG a true
 * Para DESACTIVAR el logging: cambia ENABLE_CONNECTION_LOG a false
 */

// Activar/Desactivar logging de conexiones
define('ENABLE_CONNECTION_LOG', false);

// Ruta donde se guardarán los logs (relativa a httpdocs)
define('CONNECTION_LOG_PATH', __DIR__ . '/../../logs/connection_logs/');

// Nombre del archivo de log (se creará uno por día)
define('CONNECTION_LOG_FILE_PREFIX', 'connection_log_');

// Nivel de detalle del log
// 'minimal' = Solo información básica
// 'detailed' = Información completa con headers
// 'full' = Todo incluyendo POST data (cuidado con datos sensibles)
define('CONNECTION_LOG_LEVEL', 'detailed');

// Filtrar solo conexiones desde webview móvil (true) o todas las conexiones (false)
define('LOG_ONLY_WEBVIEW', false);

// Tamaño máximo del archivo de log en MB (0 = sin límite)
define('CONNECTION_LOG_MAX_SIZE_MB', 10);

