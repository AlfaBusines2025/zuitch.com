<?php
/**
 * Visor de logs de conexiones
 * 
 * IMPORTANTE: Este archivo debe ser eliminado o protegido en producción
 * Solo debe ser accesible para administradores
 * 
 * Para proteger: agregar autenticación o mover fuera del webroot
 */

// PROTECCIÓN BÁSICA - ELIMINAR O MEJORAR EN PRODUCCIÓN
// Descomentar las siguientes líneas para requerir autenticación:
/*
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Acceso denegado. Solo administradores pueden ver los logs.');
}
*/

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Logs de Conexiones</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .controls {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .controls label {
            color: #cccccc;
            margin-right: 10px;
        }
        .controls select, .controls input {
            background: #3c3c3c;
            color: #d4d4d4;
            border: 1px solid #555;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .controls button {
            background: #0e639c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 10px;
        }
        .controls button:hover {
            background: #1177bb;
        }
        .log-entry {
            background: #252526;
            border-left: 3px solid #4ec9b0;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 3px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-entry.webview {
            border-left-color: #f48771;
        }
        .log-entry.mobile {
            border-left-color: #ce9178;
        }
        .log-entry h3 {
            margin-top: 0;
            color: #4ec9b0;
        }
        .log-entry.webview h3 {
            color: #f48771;
        }
        .info-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-right: 5px;
        }
        .badge-webview {
            background: #f48771;
            color: white;
        }
        .badge-mobile {
            background: #ce9178;
            color: white;
        }
        .badge-ip {
            background: #569cd6;
            color: white;
        }
        .stats {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stats h2 {
            color: #4ec9b0;
            margin-top: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-item {
            background: #1e1e1e;
            padding: 10px;
            border-radius: 3px;
        }
        .stat-value {
            font-size: 24px;
            color: #4ec9b0;
            font-weight: bold;
        }
        .stat-label {
            color: #cccccc;
            font-size: 12px;
        }
        pre {
            background: #1e1e1e;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
        .warning {
            background: #8b6914;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Visor de Logs de Conexiones</h1>
        
        <div class="warning">
            ⚠️ <strong>ADVERTENCIA:</strong> Este archivo expone información sensible. 
            Debe ser protegido o eliminado en producción.
        </div>
        
        <div class="controls">
            <label for="logFile">Archivo de log:</label>
            <select id="logFile">
                <?php
                $logDir = __DIR__ . '/logs/connection_logs/';
                $files = glob($logDir . 'connection_log_*.log');
                rsort($files); // Más recientes primero
                
                foreach ($files as $file) {
                    $filename = basename($file);
                    $date = str_replace(array('connection_log_', '.log'), '', $filename);
                    $selected = (isset($_GET['file']) && $_GET['file'] === $filename) ? 'selected' : '';
                    if (empty($selected) && empty($_GET['file']) && $file === $files[0]) {
                        $selected = 'selected';
                    }
                    echo "<option value=\"{$filename}\" {$selected}>{$date}</option>";
                }
                ?>
            </select>
            
            <label for="filter">Filtrar:</label>
            <select id="filter">
                <option value="all">Todas las conexiones</option>
                <option value="webview">Solo WebView</option>
                <option value="mobile">Solo Móvil</option>
            </select>
            
            <button onclick="loadLog()">Cargar Log</button>
            <button onclick="location.reload()">Actualizar</button>
        </div>
        
        <div id="stats" class="stats" style="display:none;">
            <h2>Estadísticas</h2>
            <div class="stats-grid" id="statsGrid"></div>
        </div>
        
        <div id="logContent"></div>
    </div>
    
    <script>
        function loadLog() {
            const file = document.getElementById('logFile').value;
            const filter = document.getElementById('filter').value;
            
            fetch(`?action=load&file=${file}&filter=${filter}`)
                .then(response => response.json())
                .then(data => {
                    displayLogs(data.logs);
                    displayStats(data.stats);
                })
                .catch(error => {
                    document.getElementById('logContent').innerHTML = 
                        '<div class="log-entry">Error al cargar el log: ' + error + '</div>';
                });
        }
        
        function displayLogs(logs) {
            const container = document.getElementById('logContent');
            container.innerHTML = '';
            
            if (logs.length === 0) {
                container.innerHTML = '<div class="log-entry">No hay entradas que coincidan con el filtro.</div>';
                return;
            }
            
            logs.forEach(log => {
                const entry = document.createElement('div');
                entry.className = 'log-entry';
                if (log.is_webview === 'YES') entry.classList.add('webview');
                if (log.is_mobile === 'YES') entry.classList.add('mobile');
                
                let badges = '';
                if (log.is_webview === 'YES') badges += '<span class="info-badge badge-webview">WEBVIEW</span>';
                if (log.is_mobile === 'YES') badges += '<span class="info-badge badge-mobile">MÓVIL</span>';
                badges += '<span class="info-badge badge-ip">' + log.ip + '</span>';
                
                entry.innerHTML = `
                    <h3>${badges} ${log.method} ${log.url}</h3>
                    <p><strong>Fecha:</strong> ${log.timestamp}</p>
                    <p><strong>User Agent:</strong> ${log.user_agent}</p>
                    <pre>${JSON.stringify(log, null, 2)}</pre>
                `;
                
                container.appendChild(entry);
            });
        }
        
        function displayStats(stats) {
            const grid = document.getElementById('statsGrid');
            grid.innerHTML = `
                <div class="stat-item">
                    <div class="stat-value">${stats.total}</div>
                    <div class="stat-label">Total Conexiones</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.webview}</div>
                    <div class="stat-label">WebView</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.mobile}</div>
                    <div class="stat-label">Móvil</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.unique_ips}</div>
                    <div class="stat-label">IPs Únicas</div>
                </div>
            `;
            document.getElementById('stats').style.display = 'block';
        }
        
        // Cargar automáticamente al cargar la página
        window.onload = function() {
            loadLog();
        };
        
        // Cargar cuando cambie el archivo
        document.getElementById('logFile').addEventListener('change', loadLog);
        document.getElementById('filter').addEventListener('change', loadLog);
    </script>
    
    <?php
    if (isset($_GET['action']) && $_GET['action'] === 'load') {
        header('Content-Type: application/json');
        
        $logDir = __DIR__ . '/logs/connection_logs/';
        $filename = isset($_GET['file']) ? basename($_GET['file']) : '';
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        if (empty($filename) || !preg_match('/^connection_log_\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
            echo json_encode(array('error' => 'Archivo inválido'));
            exit;
        }
        
        $filepath = $logDir . $filename;
        if (!file_exists($filepath)) {
            echo json_encode(array('error' => 'Archivo no encontrado'));
            exit;
        }
        
        $content = file_get_contents($filepath);
        $entries = explode(str_repeat('=', 80), $content);
        $logs = array();
        $stats = array(
            'total' => 0,
            'webview' => 0,
            'mobile' => 0,
            'unique_ips' => array()
        );
        
        foreach ($entries as $entry) {
            $entry = trim($entry);
            if (empty($entry)) continue;
            
            $log = json_decode($entry, true);
            if (!$log) continue;
            
            // Aplicar filtro
            if ($filter === 'webview' && $log['is_webview'] !== 'YES') continue;
            if ($filter === 'mobile' && $log['is_mobile'] !== 'YES') continue;
            
            $logs[] = $log;
            $stats['total']++;
            if ($log['is_webview'] === 'YES') $stats['webview']++;
            if ($log['is_mobile'] === 'YES') $stats['mobile']++;
            if (!empty($log['ip'])) {
                $ip = explode(' ', $log['ip'])[0]; // Solo la IP principal
                if (!in_array($ip, $stats['unique_ips'])) {
                    $stats['unique_ips'][] = $ip;
                }
            }
        }
        
        $stats['unique_ips'] = count($stats['unique_ips']);
        
        // Ordenar por timestamp (más recientes primero)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        echo json_encode(array(
            'logs' => $logs,
            'stats' => $stats
        ));
        exit;
    }
    ?>
</body>
</html>

