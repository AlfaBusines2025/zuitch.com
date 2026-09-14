# Sistema de Logging de Conexiones

## 📋 Descripción

Sistema de logging completo para diagnosticar problemas de conexión, especialmente con webview móvil de Android. El sistema registra información detallada sobre cada conexión que llega al servidor.

## 🚀 Activación/Desactivación

### Para ACTIVAR el logging:

1. Edita el archivo: `httpdocs/assets/includes/connection_log_config.php`
2. Cambia la línea:
   ```php
   define('ENABLE_CONNECTION_LOG', true);
   ```

### Para DESACTIVAR el logging:

1. Edita el archivo: `httpdocs/assets/includes/connection_log_config.php`
2. Cambia la línea:
   ```php
   define('ENABLE_CONNECTION_LOG', false);
   ```

## 📁 Archivos Creados

- `assets/includes/connection_log_config.php` - Configuración del sistema
- `assets/includes/connection_logger.php` - Motor de logging
- `logs/connection_logs/` - Directorio donde se guardan los logs
- `view_connection_logs.php` - Visor web de logs (OPCIONAL)

## 📊 Información Registrada

El sistema registra:

- ✅ IP del cliente (incluyendo proxies)
- ✅ User Agent completo
- ✅ URL solicitada
- ✅ Método HTTP (GET, POST, etc.)
- ✅ Detección automática de webview móvil
- ✅ Headers importantes (Accept, Referer, etc.)
- ✅ Estado de sesión y autenticación
- ✅ Parámetros GET (sin datos sensibles)
- ✅ Timestamp preciso

## 🔍 Ver los Logs

### Opción 1: Visor Web (Recomendado)

1. Accede a: `https://zuitch.com/view_connection_logs.php`
2. Selecciona el archivo de log del día que quieres revisar
3. Filtra por tipo de conexión (todas, webview, móvil)
4. Revisa las estadísticas y detalles de cada conexión

**⚠️ IMPORTANTE:** Protege este archivo en producción o elimínalo después de diagnosticar.

### Opción 2: Línea de Comandos

```bash
# Ver el log de hoy
cat /var/www/vhosts/zuitch.com/httpdocs/logs/connection_logs/connection_log_$(date +%Y-%m-%d).log

# Buscar solo conexiones de webview
grep -A 50 '"is_webview":"YES"' /var/www/vhosts/zuitch.com/httpdocs/logs/connection_logs/connection_log_$(date +%Y-%m-%d).log

# Ver las últimas 10 conexiones
tail -n 100 /var/www/vhosts/zuitch.com/httpdocs/logs/connection_logs/connection_log_$(date +%Y-%m-%d).log
```

## ⚙️ Configuración Avanzada

Edita `assets/includes/connection_log_config.php` para ajustar:

- **CONNECTION_LOG_LEVEL**: `'minimal'`, `'detailed'` o `'full'`
- **LOG_ONLY_WEBVIEW**: `true` para solo loguear webview, `false` para todas
- **CONNECTION_LOG_MAX_SIZE_MB**: Tamaño máximo del archivo (0 = sin límite)

## 🔒 Seguridad

- Los datos sensibles (passwords, tokens) se ocultan automáticamente
- Los logs se guardan fuera del webroot público (recomendado)
- El directorio de logs está protegido con `.htaccess`
- El visor web debe ser protegido o eliminado en producción

## 🐛 Diagnóstico de Problemas

### Problema: No aparecen logs

1. Verifica que `ENABLE_CONNECTION_LOG` esté en `true`
2. Verifica permisos del directorio: `chmod 755 logs/connection_logs/`
3. Verifica que el directorio exista: `ls -la logs/connection_logs/`

### Problema: Logs muy grandes

1. Reduce `CONNECTION_LOG_LEVEL` a `'minimal'`
2. Activa `LOG_ONLY_WEBVIEW` a `true`
3. Establece un `CONNECTION_LOG_MAX_SIZE_MB` menor

### Problema: No detecta webview correctamente

El sistema detecta webview basándose en:
- User Agent que contiene "wv" (Android WebView)
- User Agent sin "Version/" típico de Chrome
- Headers específicos de webview

Si necesitas ajustar la detección, edita la función `isWebViewMobile()` en `connection_logger.php`.

## 📝 Ejemplo de Log

```json
{
    "timestamp": "2024-01-15 14:30:25",
    "ip": "192.168.1.100",
    "method": "GET",
    "url": "/index.php",
    "is_webview": "YES",
    "is_mobile": "YES",
    "user_agent": "Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/74.0.3729.157 Mobile Safari/537.36 wv",
    "full_url": "https://zuitch.com/index.php",
    "headers": {
        "ACCEPT": "text/html,application/xhtml+xml",
        "ACCEPT_LANGUAGE": "es-ES,es;q=0.9"
    }
}
```

## 🧹 Limpieza

Los logs se acumulan día a día. Para limpiar:

```bash
# Eliminar logs de más de 7 días
find /var/www/vhosts/zuitch.com/httpdocs/logs/connection_logs/ -name "*.log" -mtime +7 -delete

# Comprimir logs antiguos
find /var/www/vhosts/zuitch.com/httpdocs/logs/connection_logs/ -name "*.log" -mtime +1 -exec gzip {} \;
```

## 📞 Soporte

Si encuentras problemas o necesitas ajustar el sistema, revisa:
1. Los logs de PHP: `/var/log/php-errors.log`
2. Los logs del servidor web
3. Los permisos de archivos y directorios

---

**Creado para diagnosticar problemas de conexión desde webview móvil de Android**

