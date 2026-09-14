<?php
declare(strict_types=1);

// ============ CONFIG ============
const DHL_BASE_URL_TEST = 'https://express.api.dhl.com/mydhlapi/test';
const DHL_BASE_URL_PROD = 'https://express.api.dhl.com/mydhlapi';
const DHL_ENDPOINT      = '/rates';

// PON TUS CREDENCIALES
const DHL_API_KEY    = 'apH7nW7mH5oB9x';
const DHL_API_SECRET = 'V@1sB^3sK#7cR!5v';

// Cuentas DHL (primaria + fallback)
const DHL_ACCOUNT_PRIMARY  = '700703270';
const DHL_ACCOUNT_FALLBACK = '697865537';

// CORS para tu dominio (ajusta si quieres restringir)
header('Access-Control-Allow-Origin: https://zuitch.com');
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

header('Content-Type: application/json; charset=utf-8');

function message_reference(): string {
  try { return bin2hex(random_bytes(16)); } catch (Throwable $e) { return dechex(time()) . '-' . mt_rand(); }
}
function dhl_headers(): array {
  $apiKey    = DHL_API_KEY ?: (getenv('DHL_API_KEY') ?: '');
  $apiSecret = DHL_API_SECRET ?: (getenv('DHL_API_SECRET') ?: '');
  return [
    'accept: application/json',
    'Authorization: Basic ' . base64_encode($apiKey . ':' . $apiSecret),
    'Message-Reference: ' . message_reference(),
    'Message-Reference-Date: ' . gmdate('D, d M Y H:i:s') . ' GMT',
    'Shipping-System-Platform-Name: PHP',
    'Shipping-System-Platform-Version: ' . PHP_VERSION,
    'Webstore-Platform-Name: Zuitch.com',
    'Webstore-Platform-Version: 7.0',
    'x-version: 3.0.0',
  ];
}

// ===== Helpers de conversión/selección de precio (igual a tus scripts) =====
function usd_eur_from_product(array $product): array {
  $usd = null; $eur = null;
  if (!empty($product['totalPrice']) && is_array($product['totalPrice'])) {
    foreach ($product['totalPrice'] as $tp) {
      $price = isset($tp['price']) ? (float)$tp['price'] : null;
      $cur   = $tp['priceCurrency'] ?? null;
      if ($price !== null && $price > 0 && $cur) {
        if ($cur === 'USD') $usd = $price;
        if ($cur === 'EUR') $eur = $price;
      }
    }
  }
  return [$usd, $eur];
}
function detect_eur_to_usd_rate(array $data, ?string &$source = null): ?float {
  if (!empty($data['products']) && is_array($data['products'])) {
    foreach ($data['products'] as $p) {
      [$usd, $eur] = usd_eur_from_product($p);
      if ($usd !== null && $eur !== null && $eur > 0) {
        $rate = $usd / $eur; if ($rate > 0) { $source = 'product_ratio'; return $rate; }
      }
    }
  }
  if (!empty($data['exchangeRates']) && is_array($data['exchangeRates'])) {
    foreach ($data['exchangeRates'] as $ex) {
      $c  = $ex['currency'] ?? null;
      $bc = $ex['baseCurrency'] ?? null;
      $r  = (float)($ex['currentExchangeRate'] ?? 0);
      if ($r <= 0) continue;
      if ($c === 'USD' && $bc === 'EUR') { $source = ($r < 1 ? 'exchangeRates_inverted' : 'exchangeRates'); return $r < 1 ? 1/$r : $r; }
      if ($c === 'EUR' && $bc === 'USD') { $source = 'exchangeRates_inverted2'; return 1/$r; }
    }
  }
  $source = null; return null;
}
function total_price_usd_from_product(array $product, ?float $eurToUsd): ?float {
  if (empty($product['totalPrice']) || !is_array($product['totalPrice'])) return null;
  $usd = null; $eur = null;
  foreach ($product['totalPrice'] as $tp) {
    $price = isset($tp['price']) ? (float)$tp['price'] : null;
    $cur   = $tp['priceCurrency'] ?? null;
    if ($price !== null && $price > 0) {
      if ($cur === 'USD') $usd = $price;
      if ($cur === 'EUR') $eur = $price;
    }
  }
  if ($usd !== null) return $usd;
  if ($eur !== null && $eurToUsd) return $eur * $eurToUsd;
  foreach ($product['totalPrice'] as $tp) {
    if (($tp['currencyType'] ?? null) === 'BASEC' && isset($tp['price']) && $eurToUsd) {
      return (float)$tp['price'] * $eurToUsd;
    }
  }
  return null;
}

// ===== Llamada a DHL (un solo intento) =====
function dhl_rate_try(array $params, string $baseUrl): array {
  $url = rtrim($baseUrl, '/') . DHL_ENDPOINT . '?' . http_build_query($params);
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => dhl_headers(),
    CURLOPT_TIMEOUT        => 60,
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($err || $code < 200 || $code >= 300) {
    return ['ok'=>false, 'code'=>$code ?: 500, 'data'=>$body, 'error'=>$err ?: 'HTTP error', 'url'=>$url];
  }
  $data = json_decode($body, true);
  if (!is_array($data)) {
    return ['ok'=>false, 'code'=>$code, 'data'=>$body, 'error'=>'Invalid JSON', 'url'=>$url];
  }
  return ['ok'=>true, 'code'=>$code, 'data'=>$data, 'url'=>$url];
}

// ====== INPUT ======
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  echo json_encode(['status'=>400, 'error'=>'Invalid JSON body']);
  exit;
}

/*
Esperado:
{
  "debug": true,                               // usa endpoint TEST si true
  "destination": { "countryCode":"CZ", "postalCode":"14800", "cityName":"Prague" },
  "plannedShippingDate": "2025-12-26",
  "unitOfMeasurement": "metric",
  "products": [
    {
      "productId":"24",
      "useDhl": true,
      "originCountryCode":"EC",
      "originPostalCode":"170514",
      "originCityName":"Quito",
      "weight":"5","length":"10","width":"20","height":"10",
      "quantity": 7,
      "extraPerUnit": 1.5
    }
  ]
}
*/

$debug         = (bool)($input['debug'] ?? false);
$BASE_URL      = $debug ? DHL_BASE_URL_TEST : DHL_BASE_URL_PROD;
$env           = $debug ? 'test' : 'prod';

$dest          = $input['destination'] ?? [];
$planned       = $input['plannedShippingDate'] ?? date('Y-m-d');
$uom           = $input['unitOfMeasurement'] ?? 'metric';
$items         = is_array($input['products'] ?? null) ? $input['products'] : [];

if (!$dest || !isset($dest['countryCode'],$dest['postalCode'],$dest['cityName'])) {
  echo json_encode(['status'=>422,'error'=>'Destination incomplete']);
  exit;
}

// ====== LOOP POR PRODUCTO ======
$perProduct = [];
$grandDhl   = 0.0;
$anyQuoted  = false;

foreach ($items as $p) {
  $pid   = (string)($p['productId'] ?? '');
  $use   = (bool)($p['useDhl'] ?? false);
  $qty   = max(1, (int)($p['quantity'] ?? 1));
  $extra = 1.8; //(float)($p['extraPerUnit'] ?? 0);

  // Campos mínimos de DHL
  $need = ['originCountryCode','originPostalCode','originCityName','weight','length','width','height'];
  $hasAll = true; foreach ($need as $k) { if (empty($p[$k])) { $hasAll = false; break; } }

  if (!$use || !$hasAll) {
    $perProduct[] = [
      'productId'=>$pid, 'quoted'=>false, 'reason'=> $use ? 'missing_fields' : 'not_selected',
      'unit'=>0.0, 'subtotal'=>0.0, 'extraPerUnit'=>$extra, 'extraSubtotal'=>0.0, 'total'=>0.0,
      'env'=>$env
    ];
    continue;
  }

  // Base de params comunes
  $baseParams = [
    'originCountryCode'            => $p['originCountryCode'],
    'originPostalCode'             => $p['originPostalCode'],
    'originCityName'               => $p['originCityName'],
    'destinationCountryCode'       => $dest['countryCode'],
    'destinationPostalCode'        => $dest['postalCode'],
    'destinationCityName'          => $dest['cityName'],
    'weight'                       => $p['weight'],
    'length'                       => $p['length'],
    'width'                        => $p['width'],
    'height'                       => $p['height'],
    'plannedShippingDate'          => $planned,
    'isCustomsDeclarable'          => 'false',
    'unitOfMeasurement'            => $uom,
    'nextBusinessDay'              => 'false',
    'strictValidation'             => 'false',
    'getAllValueAddedServices'     => 'false',
    'requestEstimatedDeliveryDate' => 'true',
    'estimatedDeliveryDateType'    => 'QDDF',
  ];

  // Intento 1: cuenta primaria
  $attempts = [];
  $params1 = $baseParams;
  $params1['accountNumber'] = DHL_ACCOUNT_PRIMARY;
  $res1 = dhl_rate_try($params1, $BASE_URL);
  $attempts[] = ['account'=>DHL_ACCOUNT_PRIMARY, 'ok'=>$res1['ok'], 'http'=>$res1['code'] ?? 0, 'url'=>$res1['url'] ?? null, 'error'=>$res1['ok'] ? null : ($res1['error'] ?? 'error')];

  $resUsed = $res1;
  $accountUsed = DHL_ACCOUNT_PRIMARY;

  // Si falla, intento 2: cuenta fallback
  if (!$res1['ok']) {
    $params2 = $baseParams;
    $params2['accountNumber'] = DHL_ACCOUNT_FALLBACK;
    $res2 = dhl_rate_try($params2, $BASE_URL);
    $attempts[] = ['account'=>DHL_ACCOUNT_FALLBACK, 'ok'=>$res2['ok'], 'http'=>$res2['code'] ?? 0, 'url'=>$res2['url'] ?? null, 'error'=>$res2['ok'] ? null : ($res2['error'] ?? 'error')];
    if ($res2['ok']) {
      $resUsed = $res2;
      $accountUsed = DHL_ACCOUNT_FALLBACK;
    }
  }

  // Si ambos fallan
  if (!$resUsed['ok']) {
    $itemOut = [
      'productId'=>$pid, 'quoted'=>false, 'reason'=>'dhl_error', 'http'=>$resUsed['code'] ?? 0,
      'unit'=>0.0, 'subtotal'=>0.0, 'extraPerUnit'=>round($extra,2), 'extraSubtotal'=>0.0, 'total'=>0.0,
      'env'=>$env,
      'message'=>'No se pudo cotizar con DHL usando cuentas primaria ni fallback.'
    ];
    if ($debug) { $itemOut['attempts'] = $attempts; $itemOut['raw'] = $resUsed['data'] ?? null; }
    $perProduct[] = $itemOut;
    continue;
  }

  // Éxito: calcular mejor precio
  $data       = $resUsed['data'];
  $rateSource = null;
  $eurToUsd   = detect_eur_to_usd_rate($data, $rateSource);

  $best = null;
  if (!empty($data['products']) && is_array($data['products'])) {
    foreach ($data['products'] as $pp) {
      $price = total_price_usd_from_product($pp, $eurToUsd);
      if ($price !== null) {
        if ($best === null || $price < $best) $best = $price;
      }
    }
  }

  $unit = (float)($best ?? 0.0);
  $subtotal = $unit * $qty;
  $extraSubtotal = $extra * $qty;
  $total = $subtotal + $extraSubtotal;

  $itemOut = [
    'productId'      => $pid,
    'quoted'         => true,
    'unit'           => round($unit, 2),
    'quantity'       => $qty,
    'subtotal'       => round($subtotal, 2),
    'extraPerUnit'   => round($extra, 2),
    'extraSubtotal'  => round($extraSubtotal, 2),
    'total'          => round($total, 2),
    'fx'             => ['eurToUsd'=>$eurToUsd, 'source'=>$rateSource],
    'env'            => $env,
    'accountUsed'    => $accountUsed,
    'message'        => 'Cotizado con DHL (' . strtoupper($env) . ') usando cuenta ' . $accountUsed
  ];
  if ($debug) { $itemOut['attempts'] = $attempts; }

  $perProduct[] = $itemOut;
  $grandDhl += $total;
  $anyQuoted = true;
}

// ====== SALIDA ======
$out = [
  'status'  => 200,
  'ok'      => $anyQuoted,
  'mensaje' => $anyQuoted
    ? 'Cotización DHL completada. Se intentó con la cuenta primaria y, en caso de error, con la cuenta de respaldo.'
    : 'No se pudo obtener ninguna cotización de DHL.',
  'totals'  => ['dhlTotal'=>round($grandDhl, 2), 'currency'=>'USD'],
  'items'   => $perProduct,
  'meta'    => [
    'env'              => $env,
    'baseUrl'          => $BASE_URL,
    'primaryAccount'   => DHL_ACCOUNT_PRIMARY,
    'fallbackAccount'  => DHL_ACCOUNT_FALLBACK,
    'debug'            => $debug
  ]
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

