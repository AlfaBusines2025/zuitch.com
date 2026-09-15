<?php
/**
 * Minimal DaaS API client (server-side, uses DAAS_PROJECT_SECRET).
 */

require_once dirname(__DIR__) . '/daas_env.php';

class DaasClient
{
    /** @var string */
    private $baseUrl;

    /** @var string */
    private $secret;

    public function __construct()
    {
        daas_env_load();
        $this->baseUrl = rtrim((string) daas_env('DAAS_URL', 'https://daas.alfabusiness.app'), '/');
        $this->secret = (string) daas_env('DAAS_PROJECT_SECRET', '');
    }

    /**
     * @param array $query
     * @return array{ok:bool,status:int,data?:array,meta?:array,message?:string}
     */
    public function listOrders(array $query = array())
    {
        if (empty($query['company_external_id'])) {
            $query['company_external_id'] = daas_env('DAAS_BILLING_EXTERNAL_ID', 'zuitch');
        }
        if (empty($query['per_page'])) {
            $query['per_page'] = 50;
        }
        $path = '/api/v1/orders?' . http_build_query($query);
        return $this->request('GET', $path);
    }

    /**
     * @param int|string $id
     * @return array{ok:bool,status:int,data?:array,message?:string}
     */
    public function getOrder($id)
    {
        return $this->request('GET', '/api/v1/orders/' . rawurlencode((string) $id));
    }

    /**
     * @param int|string $id
     * @return array{ok:bool,status:int,data?:array,message?:string}
     */
    public function cancelOrder($id)
    {
        // Hub rejects body-less POST with HTML 403; empty JSON body is required.
        return $this->request('POST', '/api/v1/orders/' . rawurlencode((string) $id) . '/cancel', array());
    }

    /**
     * Report order done with real agent tokens → hub quotes + settles billing.
     *
     * @param int|string $id
     * @param int $tokens
     * @param string $notes
     * @param string $source
     * @return array{ok:bool,status:int,data?:array,quote?:array,account?:array,message?:string}
     */
    public function completeOrder($id, $tokens, $notes = '', $source = 'zuitch_cursor')
    {
        $tokens = (int) $tokens;
        if ($tokens <= 0) {
            return array('ok' => false, 'status' => 0, 'message' => 'tokens must be a positive integer');
        }

        $body = array(
            'tokens' => $tokens,
            'notes' => (string) $notes,
            'source' => $source !== '' ? (string) $source : 'zuitch_cursor',
        );

        $res = $this->request(
            'POST',
            '/api/v1/orders/' . rawurlencode((string) $id) . '/complete',
            $body
        );

        self::logCompleteQuote($id, $tokens, $res);

        return $res;
    }

    /**
     * Append one JSON line to logs/daas-complete.log (no secrets).
     *
     * @param int|string $orderId
     * @param int $tokens
     * @param array $res
     * @return void
     */
    public static function logCompleteQuote($orderId, $tokens, array $res)
    {
        $httpdocs = dirname(dirname(dirname(__DIR__)));
        $dir = $httpdocs . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $quote = null;
        if (!empty($res['quote']) && is_array($res['quote'])) {
            $q = $res['quote'];
            $quote = array(
                'units' => isset($q['units']) ? $q['units'] : null,
                'subtotal_usd' => isset($q['subtotal_usd']) ? $q['subtotal_usd'] : null,
                'iva_usd' => isset($q['iva_usd']) ? $q['iva_usd'] : null,
                'total_usd' => isset($q['total_usd']) ? $q['total_usd'] : null,
            );
        }

        $line = json_encode(array(
            'ts' => gmdate('c'),
            'order_id' => (int) $orderId,
            'tokens' => (int) $tokens,
            'http_status' => isset($res['status']) ? (int) $res['status'] : 0,
            'ok' => !empty($res['ok']),
            'quote' => $quote,
            'message' => isset($res['message']) ? (string) $res['message'] : '',
        ));

        if ($line === false) {
            return;
        }

        @file_put_contents($dir . '/daas-complete.log', $line . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * @param string $method
     * @param string $path
     * @param array|null $jsonBody
     * @return array{ok:bool,status:int,data?:mixed,meta?:mixed,quote?:mixed,account?:mixed,message?:string,raw?:string}
     */
    private function request($method, $path, $jsonBody = null)
    {
        if ($this->secret === '') {
            return array('ok' => false, 'status' => 0, 'message' => 'DAAS_PROJECT_SECRET not configured');
        }

        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        $headers = array(
            'Authorization: Bearer ' . $this->secret,
            'Accept: application/json',
        );

        $opts = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        );

        if ($jsonBody !== null) {
            $payload = json_encode($jsonBody);
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
            $opts[CURLOPT_POSTFIELDS] = $payload;
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return array('ok' => false, 'status' => 0, 'message' => 'cURL error: ' . $error);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return array(
                'ok' => false,
                'status' => $status,
                'message' => 'Invalid JSON from DaaS (HTTP ' . $status . ')',
                'raw' => substr((string) $raw, 0, 500),
            );
        }

        $ok = $status >= 200 && $status < 300;
        // Prefer hub "ok" when present; otherwise HTTP status.
        if (array_key_exists('ok', $decoded)) {
            $ok = !empty($decoded['ok']) && $ok;
        }

        $out = array(
            'ok' => $ok,
            'status' => $status,
            'message' => isset($decoded['message']) ? (string) $decoded['message'] : '',
        );
        if (array_key_exists('data', $decoded)) {
            $out['data'] = $decoded['data'];
        }
        if (array_key_exists('meta', $decoded)) {
            $out['meta'] = $decoded['meta'];
        }
        if (array_key_exists('quote', $decoded)) {
            $out['quote'] = $decoded['quote'];
        }
        if (array_key_exists('account', $decoded)) {
            $out['account'] = $decoded['account'];
        }
        if (!$ok && $out['message'] === '') {
            $out['message'] = 'DaaS HTTP ' . $status;
        }
        return $out;
    }
}
