<?php
/**
 * Zuitch VoIP push helper — token storage + the one call agora.php makes.
 * Path: httpdocs/assets/includes/zuitch_voip.php
 *
 * Deliberately self-contained: it touches only its own table and reads its own
 * config file, so a WoWonder upgrade cannot silently break it.
 */

if (!defined('T_VOIP_TOKENS')) {
    define('T_VOIP_TOKENS', 'Wo_VoipTokens');
}

/** Loads and caches the config array. Returns array() if unusable. */
function Zuitch_VoipConfig()
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg  = array();
    $file = dirname(__FILE__) . '/zuitch_voip_config.php';
    if (is_readable($file)) {
        $loaded = include $file;
        if (is_array($loaded)) {
            $cfg = $loaded;
        }
    }
    return $cfg;
}

/** True when VoIP pushes are configured well enough to attempt. */
function Zuitch_VoipEnabled()
{
    $cfg = Zuitch_VoipConfig();
    return !empty($cfg['enabled'])
        && !empty($cfg['team_id'])
        && !empty($cfg['key_id'])
        && $cfg['key_id'] !== 'REPLACE_KEY_ID'
        && !empty($cfg['key_path'])
        && is_readable($cfg['key_path']);
}

/**
 * Store (or move) a PushKit token for a user.
 * The UNIQUE key on voip_token means re-registering the same handset under a
 * different account reassigns it instead of ringing the old account too.
 */
function Zuitch_RegisterVoipToken($user_id, $voip_token, $meta = array())
{
    global $sqlConnect;

    $user_id = (int)$user_id;
    $token   = preg_replace('/[^a-fA-F0-9]/', '', (string)$voip_token);
    if ($user_id <= 0 || strlen($token) < 32 || strlen($token) > 200) {
        return false;
    }

    $esc = function ($v) use ($sqlConnect) {
        return mysqli_real_escape_string($sqlConnect, (string)$v);
    };

    $token_e   = $esc($token);
    $platform  = $esc(isset($meta['platform'])    ? $meta['platform']    : 'ios');
    $bundle    = $esc(isset($meta['bundle_id'])   ? $meta['bundle_id']   : '');
    $device    = $esc(mb_substr(isset($meta['device_name']) ? $meta['device_name'] : '', 0, 120));
    $appver    = $esc(mb_substr(isset($meta['app_version']) ? $meta['app_version'] : '', 0, 32));
    $now       = time();

    $sql = "INSERT INTO `" . T_VOIP_TOKENS . "`
              (`user_id`,`voip_token`,`platform`,`bundle_id`,`device_name`,`app_version`,`time`)
            VALUES
              ({$user_id},'{$token_e}','{$platform}','{$bundle}','{$device}','{$appver}',{$now})
            ON DUPLICATE KEY UPDATE
              `user_id`     = {$user_id},
              `platform`    = '{$platform}',
              `bundle_id`   = '{$bundle}',
              `device_name` = '{$device}',
              `app_version` = '{$appver}',
              `time`        = {$now}";

    return (bool)mysqli_query($sqlConnect, $sql);
}

/** Remove one token (called on sign-out) — or every token for a user. */
function Zuitch_DeleteVoipToken($user_id, $voip_token = '')
{
    global $sqlConnect;

    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return false;
    }
    if ($voip_token !== '') {
        $token = mysqli_real_escape_string($sqlConnect, preg_replace('/[^a-fA-F0-9]/', '', (string)$voip_token));
        if ($token === '') {
            return false;
        }
        return (bool)mysqli_query($sqlConnect,
            "DELETE FROM `" . T_VOIP_TOKENS . "` WHERE `voip_token` = '{$token}' AND `user_id` = {$user_id}");
    }
    return (bool)mysqli_query($sqlConnect,
        "DELETE FROM `" . T_VOIP_TOKENS . "` WHERE `user_id` = {$user_id}");
}

/** All VoIP tokens for a user, newest first. */
function Zuitch_VoipTokensFor($user_id)
{
    global $sqlConnect;

    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return array();
    }
    $tokens = array();
    $q = mysqli_query($sqlConnect,
        "SELECT `voip_token` FROM `" . T_VOIP_TOKENS . "`
          WHERE `user_id` = {$user_id} ORDER BY `time` DESC LIMIT 10");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $tokens[] = $row['voip_token'];
        }
    }
    return $tokens;
}

/**
 * Ring a user's iOS devices for a call that is starting RIGHT NOW.
 *
 * This is the only function agora.php calls. It must never throw and never
 * break call creation — a failed push is logged and swallowed.
 *
 * Do not call this for anything other than a live ringing call: iOS requires
 * the app to raise a CallKit call for every VoIP push it receives, and Apple
 * revokes VoIP delivery for apps that break that rule.
 *
 * @return int number of devices Apple accepted
 */
function Zuitch_SendVoipCall($to_user_id, array $call)
{
    if (!Zuitch_VoipEnabled()) {
        return 0;
    }

    $tokens = Zuitch_VoipTokensFor($to_user_id);
    if (empty($tokens)) {
        return 0;   // no iOS device registered — the OneSignal alert still fires
    }

    $cfg = Zuitch_VoipConfig();

    if (!empty($cfg['cache_dir']) && !is_dir($cfg['cache_dir'])) {
        @mkdir($cfg['cache_dir'], 0700, true);
    }

    require_once dirname(__FILE__) . '/ZuitchVoipPush.php';

    $sent = 0;
    try {
        $push    = new ZuitchVoipPush($cfg);
        $results = $push->sendCall($tokens, $call);

        foreach ($results as $r) {
            if (isset($r['http']) && $r['http'] === 200) {
                $sent++;
            }
        }

        // Apple told us these devices are gone. Dropping them now keeps the
        // table from accumulating dead rows that slow every future call.
        foreach ($push->deadTokens() as $dead) {
            Zuitch_DeleteVoipToken($to_user_id, $dead);
        }
    } catch (Exception $e) {
        if (!empty($cfg['log_file'])) {
            @file_put_contents(
                $cfg['log_file'],
                '[' . gmdate('Y-m-d H:i:s') . "Z] EXCEPTION: " . $e->getMessage() . "\n",
                FILE_APPEND | LOCK_EX
            );
        }
        return 0;
    }

    return $sent;
}
