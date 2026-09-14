<?php
/** 
 * facebook_login.php — Login con Facebook en una sola vista (100% PHP)
 * - Subir este archivo a tu hosting y registrar su URL exacta como Redirect URI en Meta Developers.
 * - Requiere cURL habilitado.
 */

declare(strict_types=1);
session_set_cookie_params([
    'lifetime' => 7 * 24 * 60 * 60, // 7 días
    'path'     => '/',
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Lax'
]);
session_start();

/* =======================
   CONFIG BÁSICA (edita aquí)
   ======================= */
$FACEBOOK_APP_ID     = '1166938258817384';
$FACEBOOK_APP_SECRET = 'd582f42f4b37e2094a5be2ee85b1cde7';
$GRAPH_VERSION       = 'v19.0'; // puedes cambiar a v20.0 si tu app ya lo usa

// Calcula la URL actual sin querystring, para usarla como redirect_uri
function current_url_without_query(): string {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    $path   = strtok($uri, '?');
    return $scheme . '://' . $host . $path;
}
$REDIRECT_URI = current_url_without_query();

/* =======================
   UTILIDADES HTTP
   ======================= */
function http_post_form(string $url, array $params, int $timeout = 20): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_TIMEOUT        => $timeout,
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$res, $err, $code];
}

function http_get_json(string $url, array $params = [], int $timeout = 20): array {
    $qs  = $params ? ((str_contains($url, '?') ? '&' : '?') . http_build_query($params)) : '';
    $ch  = curl_init($url . $qs);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$res, $err, $code];
}

/* =======================
   LÓGICA DE SESIÓN / ESTADO
   ======================= */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"] ?? '', $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: ' . $REDIRECT_URI);
    exit;
}

$errorMsg = null;
$userData = null;

// Genera STATE para CSRF en visitas normales (no callback)
if (!isset($_GET['code'])) {
    try {
        $_SESSION['fb_state'] = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $_SESSION['fb_state'] = uniqid('fb_', true);
    }
}
$STATE = $_SESSION['fb_state'] ?? '';

// Si ya hay token en sesión y no venimos de callback, traer perfil
if (isset($_SESSION['fb_access_token']) && !isset($_GET['code'])) {
    $token = $_SESSION['fb_access_token'];
    [$res, $err, $code] = http_get_json("https://graph.facebook.com/{$GRAPH_VERSION}/me", [
        'fields'       => 'id,name,email,picture.type(large)',
        'access_token' => $token,
    ]);
    if ($err) {
        $errorMsg = "Error al consultar perfil: {$err}";
    } else {
        $data = json_decode($res, true);
        if (isset($data['error'])) {
            $errorMsg = 'Facebook Graph: ' . ($data['error']['message'] ?? 'Error desconocido');
        } else {
            $userData = $data;
        }
    }
}

/* =======================
   CALLBACK: code -> access_token -> perfil
   ======================= */
if (isset($_GET['code'])) {
    // Validación de state
    if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['fb_state'] ?? '')) {
        $errorMsg = 'STATE inválido. Por favor, intenta el login otra vez.';
    } elseif (isset($_GET['error'])) {
        // Usuario canceló o hubo error en el diálogo
        $reason = htmlspecialchars($_GET['error_description'] ?? $_GET['error'] ?? 'Error en autorización', ENT_QUOTES, 'UTF-8');
        $errorMsg = "Facebook OAuth: {$reason}";
    } else {
        // Intercambio por access_token
        [$res, $err, $code] = http_post_form("https://graph.facebook.com/{$GRAPH_VERSION}/oauth/access_token", [
            'client_id'     => $FACEBOOK_APP_ID,
            'redirect_uri'  => $REDIRECT_URI,
            'client_secret' => $FACEBOOK_APP_SECRET,
            'code'          => $_GET['code'],
        ]);

        if ($err) {
            $errorMsg = "Error al obtener token: {$err}";
        } else {
            $tokenData = json_decode($res, true);
            if (!isset($tokenData['access_token'])) {
                $msg = $tokenData['error']['message'] ?? 'No se recibió access_token';
                $errorMsg = "Facebook OAuth: {$msg}";
            } else {
                $token = $tokenData['access_token'];
                $_SESSION['fb_access_token'] = $token;

                // (Opcional recomendado) Intercambiar por token de larga duración
                [$resLL, $errLL, $codeLL] = http_get_json("https://graph.facebook.com/{$GRAPH_VERSION}/oauth/access_token", [
                    'grant_type'        => 'fb_exchange_token',
                    'client_id'         => $FACEBOOK_APP_ID,
                    'client_secret'     => $FACEBOOK_APP_SECRET,
                    'fb_exchange_token' => $token,
                ]);
                if (!$errLL) {
                    $llData = json_decode($resLL, true);
                    if (isset($llData['access_token'])) {
                        $token = $llData['access_token'];
                        $_SESSION['fb_access_token'] = $token;
                    }
                }

                // Obtener perfil
                [$resMe, $errMe, $codeMe] = http_get_json("https://graph.facebook.com/{$GRAPH_VERSION}/me", [
                    'fields'       => 'id,name,email,picture.type(large)',
                    'access_token' => $token,
                ]);
                if ($errMe) {
                    $errorMsg = "Error al obtener perfil: {$errMe}";
                } else {
                    $data = json_decode($resMe, true);
                    if (isset($data['error'])) {
                        $errorMsg = 'Facebook Graph: ' . ($data['error']['message'] ?? 'Error desconocido');
                    } else {
                        $userData = $data;
                    }
                }
            }
        }
    }
}

/* =======================
   URL de login (si no hay usuario)
   ======================= */
$loginUrl = null;
if (!$userData) {
    $params = [
        'client_id'    => $FACEBOOK_APP_ID,
        'redirect_uri' => $REDIRECT_URI,
        'state'        => $STATE,
        'scope'        => 'email',
    ];
    $loginUrl = "https://www.facebook.com/{$GRAPH_VERSION}/dialog/oauth?" . http_build_query($params);
}

/* =======================
   HTML / VISTA ÚNICA
   ======================= */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Login con Facebook (PHP puro)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--bg:#0f172a;--card:#111827;--txt:#e5e7eb;--muted:#9ca3af;}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial}
.wrap{min-height:100dvh;display:grid;place-items:center;padding:24px}
.card{width:100%;max-width:560px;background:var(--card);border:1px solid #1f2937;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(0,0,0,.25)}
.title{font-size:22px;font-weight:800;margin:0 0 8px}
.sub{color:var(--muted);margin:0 0 18px;font-size:14px}
.btn{display:inline-block;padding:12px 16px;border-radius:12px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700}
.btn:hover{filter:brightness(1.06)}
.row{display:flex;gap:14px;align-items:center}
.avatar{width:56px;height:56px;border-radius:50%;border:2px solid #374151;object-fit:cover}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace}
.small{font-size:12px}
.muted{color:var(--muted)}
.err{background:#7f1d1d;color:#fee2e2;border:1px solid #ef4444;padding:10px 12px;border-radius:10px;margin:12px 0}
.kv{background:#0b1022;padding:10px 12px;border-radius:10px;border:1px solid #1e293b;overflow:auto}
.sep{height:1px;background:#1f2937;margin:16px 0}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1 class="title">Login con Facebook</h1>
    <p class="sub">Esta página única realiza <span class="mono">login</span> y <span class="mono">callback</span> usando OAuth 2.0 de Facebook.</p>

    <?php if ($errorMsg): ?>
      <div class="err"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($userData): ?>
      <div class="row" style="margin-bottom:12px">
        <?php if (isset($userData['picture']['data']['url'])): ?>
          <img class="avatar" src="<?php echo htmlspecialchars($userData['picture']['data']['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="avatar">
        <?php endif; ?>
        <div>
          <div><strong><?php echo htmlspecialchars($userData['name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?></strong></div>
          <div class="small muted"><?php echo htmlspecialchars($userData['email'] ?? 'sin email público', ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="small muted">ID: <span class="mono"><?php echo htmlspecialchars($userData['id'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
      </div>

      <div class="sep"></div>

      <p class="small muted">
        Ya puedes crear/validar este usuario en tu base de datos (usa el <span class="mono">id</span> como clave estable).
      </p>

      <div style="display:flex;gap:12px;margin-top:10px">
        <a class="btn" href="<?php echo htmlspecialchars($REDIRECT_URI, ENT_QUOTES, 'UTF-8'); ?>?logout=1">Cerrar sesión</a>
      </div>

    <?php else: ?>
      <a class="btn" href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>">Entrar con Facebook</a>
      <p class="small muted" style="margin-top:10px">
        Registra esta URL como <em>Valid OAuth Redirect URI</em>:<br>
        <span class="mono"><?php echo htmlspecialchars($REDIRECT_URI, ENT_QUOTES, 'UTF-8'); ?></span>
      </p>
    <?php endif; ?>

    <details style="margin-top:16px">
      <summary class="muted">Debug / Config efectivos</summary>
      <div class="kv small">
        <div>client_id: <span class="mono"><?php echo htmlspecialchars($FACEBOOK_APP_ID, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div>redirect_uri: <span class="mono"><?php echo htmlspecialchars($REDIRECT_URI, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div>graph_version: <span class="mono"><?php echo htmlspecialchars($GRAPH_VERSION, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div>state (sesión): <span class="mono"><?php echo htmlspecialchars($_SESSION['fb_state'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
        <?php if (isset($_SESSION['fb_access_token'])): ?>
          <div>access_token: <span class="mono">[guardado en sesión]</span></div>
        <?php endif; ?>
      </div>
    </details>

    <p class="small muted" style="margin-top:12px">
      Seguridad: evita exponer este archivo en repos públicos; usa HTTPS en producción.
    </p>
  </div>
</div>
</body>
</html>