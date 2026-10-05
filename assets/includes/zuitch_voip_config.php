<?php
/**
 * Zuitch VoIP push configuration.
 * Path: httpdocs/assets/includes/zuitch_voip_config.php
 *
 * The .p8 itself must live OUTSIDE the web root. Nothing in here is a secret
 * on its own — the key file is the secret.
 */
return array(
    // Master switch. Set false to stop all VoIP pushes without touching code.
    'enabled'       => true,

    'team_id'       => '24W62545MM',
    'key_id'        => '239KY9YHXQ',   // <-- 10 chars, from developer.apple.com > Keys
    'key_path'      => '/var/www/vhosts/zuitch.com/private/AuthKey_239KY9YHXQ.p8',

    'bundle_id'     => 'com.zuitch.app',   // topic sent is this + ".voip"

    // false = try production first. The sender retries the other gateway once on
    // BadDeviceToken, so Xcode debug builds and App Store builds both work.
    'sandbox'       => false,
    'auto_fallback' => true,

    'cache_dir'     => '/var/www/vhosts/zuitch.com/private/voip_cache',
    'log_file'      => '/var/www/vhosts/zuitch.com/private/voip_push.log',

    // Kept deliberately short: this runs inside the caller's call-create
    // request, so a slow APNs response would show up as a laggy call button.
    'timeout'       => 4,
);
