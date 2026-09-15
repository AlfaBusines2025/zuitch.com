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
     * @param string $method
     * @param string $path
     * @param array|null $jsonBody
     * @return array{ok:bool,status:int,data?:mixed,meta?:mixed,message?:string,raw?:string}
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
        if (!$ok && $out['message'] === '') {
            $out['message'] = 'DaaS HTTP ' . $status;
        }
        return $out;
    }
}
