<?php
/**
 * Script de diagnóstico para webview
 * 
 * Este script ayuda a diagnosticar problemas con webview móvil
 * Accede desde: https://zuitch.com/webview_debug.php
 */

header('Content-Type: text/html; charset=utf-8');

// Detectar si viene de webview
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$isWebView = false;
$isAndroid = false;
$isIOS = false;

// Detectar Android WebView
if (preg_match('/android/i', $userAgent)) {
    $isAndroid = true;
    // Android WebView típicamente tiene "wv" en el User Agent
    if (preg_match('/wv/i', $userAgent) || 
        (preg_match('/chrome/i', $userAgent) && !preg_match('/version\//i', $userAgent))) {
        $isWebView = true;
    }
}

// Detectar iOS WebView
if (preg_match('/iphone|ipad/i', $userAgent)) {
    $isIOS = true;
    if (!preg_match('/safari/i', $userAgent) && 
        !preg_match('/crios/i', $userAgent) && 
        !preg_match('/fxios/i', $userAgent)) {
        $isWebView = true;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico WebView - Zuitch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #555;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.ok { background: #28a745; color: white; }
        .status.warning { background: #ffc107; color: black; }
        .status.error { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de WebView</h1>
        
        <div class="info-box">
            <strong>Información del Cliente:</strong><br>
            <strong>User Agent:</strong> <code><?php echo htmlspecialchars($userAgent); ?></code><br>
            <strong>IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?><br>
            <strong>Plataforma:</strong> 
            <?php if ($isAndroid) echo '<span class="status ok">Android</span>'; ?>
            <?php if ($isIOS) echo '<span class="status ok">iOS</span>'; ?>
            <?php if (!$isAndroid && !$isIOS) echo '<span class="status warning">Desktop/Otro</span>'; ?>
            <br>
            <strong>WebView Detectado:</strong> 
            <?php if ($isWebView): ?>
                <span class="status ok">SÍ</span>
            <?php else: ?>
                <span class="status warning">NO (o no detectado)</span>
            <?php endif; ?>
        </div>

        <?php if ($isWebView): ?>
            <div class="success-box">
                <strong>✅ WebView detectado correctamente</strong><br>
                El sistema ha identificado que estás accediendo desde un webview.
            </div>
        <?php else: ?>
            <div class="warning-box">
                <strong>⚠️ WebView no detectado</strong><br>
                Si estás accediendo desde un webview pero no se detecta, puede haber un problema con el User Agent.
            </div>
        <?php endif; ?>

        <div class="test-section">
            <h3>🧪 Pruebas de Funcionalidad</h3>
            
            <p><strong>1. JavaScript:</strong> <span id="js-test" class="status">Probando...</span></p>
            <p><strong>2. Carga de recursos:</strong> <span id="resource-test" class="status">Probando...</span></p>
            <p><strong>3. Cookies:</strong> <span id="cookie-test" class="status">Probando...</span></p>
            <p><strong>4. LocalStorage:</strong> <span id="storage-test" class="status">Probando...</span></p>
            <p><strong>5. Fetch API:</strong> <span id="fetch-test" class="status">Probando...</span></p>
        </div>

        <div class="test-section">
            <h3>📊 Información de Headers</h3>
            <pre><?php
            $headers = array(
                'HTTP_ACCEPT' => 'Accept',
                'HTTP_ACCEPT_LANGUAGE' => 'Accept-Language',
                'HTTP_ACCEPT_ENCODING' => 'Accept-Encoding',
                'HTTP_REFERER' => 'Referer',
                'HTTP_X_REQUESTED_WITH' => 'X-Requested-With',
                'HTTP_COOKIE' => 'Cookie'
            );
            foreach ($headers as $key => $name) {
                if (isset($_SERVER[$key])) {
                    $value = $_SERVER[$key];
                    if ($key === 'HTTP_COOKIE') {
                        $value = substr($value, 0, 100) . '... [truncado]';
                    }
                    echo $name . ': ' . htmlspecialchars($value) . "\n";
                }
            }
            ?></pre>
        </div>

        <div class="test-section">
            <h3>🔗 Enlaces de Prueba</h3>
            <p><a href="/" target="_blank">Ir a página principal</a></p>
            <p><a href="/welcome" target="_blank">Ir a página de bienvenida</a></p>
            <p><a href="/index.php" target="_blank">Ir a index.php</a></p>
        </div>

        <div class="test-section">
            <h3>📝 Log de Errores</h3>
            <div id="error-log" style="background: #f4f4f4; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                <p>Esperando errores...</p>
            </div>
        </div>
    </div>

    <script>
        // Test 1: JavaScript
        document.getElementById('js-test').textContent = 'OK';
        document.getElementById('js-test').className = 'status ok';

        // Test 2: Recursos
        var img = new Image();
        img.onload = function() {
            document.getElementById('resource-test').textContent = 'OK';
            document.getElementById('resource-test').className = 'status ok';
        };
        img.onerror = function() {
            document.getElementById('resource-test').textContent = 'ERROR';
            document.getElementById('resource-test').className = 'status error';
        };
        img.src = '/upload/photos/d-avatar.jpg';

        // Test 3: Cookies
        try {
            document.cookie = 'webview_test=1; path=/';
            document.getElementById('cookie-test').textContent = 'OK';
            document.getElementById('cookie-test').className = 'status ok';
        } catch(e) {
            document.getElementById('cookie-test').textContent = 'ERROR';
            document.getElementById('cookie-test').className = 'status error';
        }

        // Test 4: LocalStorage
        try {
            localStorage.setItem('webview_test', '1');
            localStorage.removeItem('webview_test');
            document.getElementById('storage-test').textContent = 'OK';
            document.getElementById('storage-test').className = 'status ok';
        } catch(e) {
            document.getElementById('storage-test').textContent = 'ERROR';
            document.getElementById('storage-test').className = 'status error';
        }

        // Test 5: Fetch API
        fetch('/requests.php?f=test&hash=test')
            .then(function(response) {
                document.getElementById('fetch-test').textContent = 'OK';
                document.getElementById('fetch-test').className = 'status ok';
            })
            .catch(function(error) {
                document.getElementById('fetch-test').textContent = 'ERROR';
                document.getElementById('fetch-test').className = 'status error';
            });

        // Capturar errores
        var errorLog = document.getElementById('error-log');
        errorLog.innerHTML = '';

        window.addEventListener('error', function(e) {
            var errorMsg = document.createElement('p');
            errorMsg.style.color = 'red';
            errorMsg.textContent = 'Error: ' + e.message + ' en ' + e.filename + ':' + e.lineno;
            errorLog.appendChild(errorMsg);
        });

        // Log de consola
        var originalLog = console.log;
        console.log = function() {
            originalLog.apply(console, arguments);
            var logMsg = document.createElement('p');
            logMsg.textContent = Array.from(arguments).join(' ');
            errorLog.appendChild(logMsg);
        };
    </script>
</body>
</html>

