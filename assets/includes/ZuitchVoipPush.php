<?php
/**
 * ZuitchVoipPush — APNs VoIP (PushKit) sender for Zuitch.
 *
 * Sends a high-priority VoIP push straight to Apple over HTTP/2, signed with an
 * ES256 JWT from a .p8 APNs auth key. Independent of OneSignal — OneSignal cannot
 * deliver to the "<bundle>.voip" topic, which is what makes an iPhone ring.
 *
 * Requires: PHP 7.1+, curl built with HTTP/2 (nghttp2), openssl.
 * Verified on this server: PHP 7.4.33 / curl 7.76.1 HTTP/2 / openssl_sign ES256.
 *
 * IMPORTANT (App Store rule): the app MUST call CallKit's reportNewIncomingCall()
 * for every VoIP push it receives. So only ever send a VoIP push for a call that
 * is genuinely ringing right now. Never use this for chat messages or badges.
 */

class ZuitchVoipPush
{
    const HOST_PROD = 'https://api.push.apple.com';
    const HOST_DEV  = 'https://api.sandbox.push.apple.com';

    /** @var array */
    private $cfg;

    /** @var array Raw per-token results from the last send() */
    public $lastResults = array();

    public function __construct(array $cfg)
    {
        $this->cfg = array_merge(array(
            'team_id'       => '',      // Apple Developer Team ID
            'key_id'        => '',      // APNs auth key ID (10 chars)
            'key_path'      => '',      // absolute path to AuthKey_XXXXXXXXXX.p8
            'bundle_id'     => 'com.zuitch.app',
            'sandbox'       => false,   // true to target the sandbox gateway only
            'auto_fallback' => true,    // on BadDeviceToken, retry the other gateway
            'cache_dir'     => sys_get_temp_dir(),
            'log_file'      => '',      // absolute path, or '' to disable logging
            'timeout'       => 5,
        ), $cfg);
    }

    // ---------------------------------------------------------------- public

    /**
     * Send a ringing-call VoIP push.
     *
     * @param string[] $tokens PushKit device tokens (hex) for the callee's devices
     * @param array    $call   see keys below
     * @return array   token => array('http' => int, 'reason' => string)
     */
    public function sendCall($tokens, array $call)
    {
        $payload = array(
            // Kept under one top-level key so the app can version the shape later.
            'zuitch' => array(
                'type'          => 'call',
                'call_id'       => isset($call['call_id'])      ? (string)$call['call_id'] : '',
                'room_name'     => isset($call['room_name'])    ? (string)$call['room_name'] : '',
                'call_type'     => isset($call['call_type'])     ? (string)$call['call_type'] : 'audio',
                'provider'      => isset($call['provider'])      ? (string)$call['provider'] : 'agora',
                'from_id'       => isset($call['from_id'])        ? (string)$call['from_id'] : '',
                'from_name'     => isset($call['from_name'])      ? (string)$call['from_name'] : '',
                'from_username' => isset($call['from_username'])  ? (string)$call['from_username'] : '',
                'from_avatar'   => isset($call['from_avatar'])    ? (string)$call['from_avatar'] : '',
                'agora_app_id'  => isset($call['agora_app_id'])   ? (string)$call['agora_app_id'] : '',
                'agora_token'   => isset($call['agora_token'])    ? (string)$call['agora_token'] : '',
                'sent_at'       => time(),
            ),
        );
        return $this->send($tokens, $payload);
    }

    /**
     * Low-level send. Returns token => array('http','reason','curl_error').
     */
    public function send($tokens, array $payload)
    {
        $this->lastResults = array();
        $tokens = array_values(array_unique(array_filter(array_map('trim', (array)$tokens))));
        if (empty($tokens)) {
            return array();
        }

        try {
            $jwt = $this->jwt();
        } catch (Exception $e) {
            $this->log('JWT ERROR: ' . $e->getMessage());
            return array();
        }

        $body    = json_encode($payload);
        $primary = $this->cfg['sandbox'] ? self::HOST_DEV : self::HOST_PROD;
        $other   = $this->cfg['sandbox'] ? self::HOST_PROD : self::HOST_DEV;

        foreach ($tokens as $token) {
            $res = $this->post($primary, $token, $body, $jwt);

            // A development build's token is only valid on sandbox, and a TestFlight
            // or App Store build's only on production. Rather than making that a
            // config flag you have to remember to flip, try the other gateway once.
            if ($this->cfg['auto_fallback'] && $res['reason'] === 'BadDeviceToken') {
                $retry = $this->post($other, $token, $body, $jwt);
                if ($retry['http'] === 200) {
                    $retry['gateway_fallback'] = true;
                    $res = $retry;
                }
            }

            $this->lastResults[$token] = $res;
            $this->log(sprintf(
                'token=%s… http=%d reason=%s%s%s',
                substr($token, 0, 10),
                $res['http'],
                $res['reason'] !== '' ? $res['reason'] : 'ok',
                $res['curl_error'] !== '' ? ' curl=' . $res['curl_error'] : '',
                !empty($res['gateway_fallback']) ? ' (sandbox/prod fallback)' : ''
            ));
        }

        return $this->lastResults;
    }

    /**
     * Tokens Apple says are dead and should be deleted from the database.
     * Call this after send()/sendCall() and delete what it returns.
     */
    public function deadTokens()
    {
        $dead = array();
        foreach ($this->lastResults as $token => $r) {
            if ($r['http'] === 410) {                       // Unregistered
                $dead[] = $token;
            } elseif ($r['http'] === 400 && in_array($r['reason'], array(
                'BadDeviceToken', 'DeviceTokenNotForTopic',
            ), true)) {
                $dead[] = $token;
            }
        }
        return $dead;
    }

    /** Quick self-test: confirms the key parses and Apple accepts the JWT. */
    public function selfTest()
    {
        $out = array('key_readable' => false, 'jwt' => false, 'apns_reachable' => false, 'error' => '');
        if (!is_readable($this->cfg['key_path'])) {
            $out['error'] = 'key not readable at ' . $this->cfg['key_path'];
            return $out;
        }
        $out['key_readable'] = true;
        try {
            $jwt = $this->jwt();
            $out['jwt'] = (substr_count($jwt, '.') === 2);
        } catch (Exception $e) {
            $out['error'] = $e->getMessage();
            return $out;
        }
        // A deliberately invalid token: a correct JWT gets BadDeviceToken (400),
        // a bad JWT gets InvalidProviderToken / ExpiredProviderToken (403).
        $r = $this->post(self::HOST_PROD, str_repeat('a', 64), '{}', $jwt);
        $out['apns_reachable'] = ($r['http'] > 0);
        $out['http']           = $r['http'];
        $out['reason']         = $r['reason'];
        $out['auth_ok']        = ($r['http'] !== 403);
        if ($r['http'] === 403) {
            $out['error'] = 'Apple rejected the token: ' . $r['reason']
                          . ' — check team_id, key_id and that the .p8 matches key_id';
        }
        return $out;
    }

    // --------------------------------------------------------------- private

    private function post($host, $token, $body, $jwt)
    {
        $headers = array(
            'authorization: bearer ' . $jwt,
            'apns-topic: ' . $this->cfg['bundle_id'] . '.voip',
            'apns-push-type: voip',
            'apns-priority: 10',
            'apns-expiration: 0',           // deliver now or not at all
            'content-type: application/json',
        );

        $ch = curl_init($host . '/3/device/' . $token);
        curl_setopt_array($ch, array(
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int)$this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 3,
        ));
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = (string)curl_error($ch);
        curl_close($ch);

        $reason = '';
        if ($code !== 200 && $resp !== false && $resp !== '') {
            $j = json_decode($resp, true);
            $reason = (is_array($j) && isset($j['reason'])) ? $j['reason'] : substr((string)$resp, 0, 200);
        }

        return array('http' => $code, 'reason' => $reason, 'curl_error' => $err);
    }

    /**
     * ES256 JWT for APNs, cached ~45 min. Apple accepts a token for 1 hour and
     * rate-limits providers that mint a fresh one per push, so this is not
     * optional politeness.
     */
    private function jwt()
    {
        $cacheFile = rtrim($this->cfg['cache_dir'], '/')
                   . '/zuitch_apns_jwt_' . preg_replace('/[^A-Za-z0-9]/', '', $this->cfg['key_id']) . '.json';

        if (is_readable($cacheFile)) {
            $c = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($c) && !empty($c['jwt']) && !empty($c['iat']) && (time() - (int)$c['iat']) < 2700) {
                return $c['jwt'];
            }
        }

        $pem = @file_get_contents($this->cfg['key_path']);
        if ($pem === false || $pem === '') {
            throw new Exception('APNs key unreadable at ' . $this->cfg['key_path']);
        }
        $pkey = openssl_pkey_get_private($pem);
        if ($pkey === false) {
            throw new Exception('APNs .p8 did not parse as a private key: ' . openssl_error_string());
        }

        $iat     = time();
        $signing = $this->b64(json_encode(array('alg' => 'ES256', 'kid' => $this->cfg['key_id'])))
                 . '.'
                 . $this->b64(json_encode(array('iss' => $this->cfg['team_id'], 'iat' => $iat)));

        $der = '';
        if (!openssl_sign($signing, $der, $pkey, OPENSSL_ALGO_SHA256)) {
            throw new Exception('openssl_sign failed: ' . openssl_error_string());
        }
        if (PHP_VERSION_ID < 80000) {
            @openssl_free_key($pkey);
        }

        $jwt = $signing . '.' . $this->b64($this->derToRaw($der));

        @file_put_contents($cacheFile, json_encode(array('jwt' => $jwt, 'iat' => $iat)), LOCK_EX);
        @chmod($cacheFile, 0600);

        return $jwt;
    }

    /**
     * openssl_sign emits an ASN.1 DER SEQUENCE{INTEGER r, INTEGER s}; JWS ES256
     * wants raw r||s, each left-padded to exactly 32 bytes. Skipping this is why
     * most hand-rolled PHP APNs senders get a 403 InvalidProviderToken.
     */
    private function derToRaw($der)
    {
        $i = 0;
        if (!isset($der[$i]) || ord($der[$i++]) !== 0x30) {
            throw new Exception('malformed ECDSA signature: no SEQUENCE');
        }
        $len = ord($der[$i++]);
        if ($len & 0x80) {
            $i += ($len & 0x7F);                 // skip long-form length bytes
        }

        $parts = array();
        for ($n = 0; $n < 2; $n++) {
            if (!isset($der[$i]) || ord($der[$i++]) !== 0x02) {
                throw new Exception('malformed ECDSA signature: no INTEGER');
            }
            $l = ord($der[$i++]);
            $v = substr($der, $i, $l);
            $i += $l;
            $v = ltrim($v, "\x00");              // drop DER's sign-padding byte
            if (strlen($v) > 32) {
                throw new Exception('malformed ECDSA signature: component too long');
            }
            $parts[] = str_pad($v, 32, "\x00", STR_PAD_LEFT);
        }

        return $parts[0] . $parts[1];
    }

    private function b64($raw)
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function log($line)
    {
        if (empty($this->cfg['log_file'])) {
            return;
        }
        // Keep the log from growing without bound on a busy server.
        if (@filesize($this->cfg['log_file']) > 2097152) {
            @rename($this->cfg['log_file'], $this->cfg['log_file'] . '.1');
        }
        @file_put_contents(
            $this->cfg['log_file'],
            '[' . gmdate('Y-m-d H:i:s') . 'Z] ' . $line . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
