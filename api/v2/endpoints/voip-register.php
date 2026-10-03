<?php
/**
 * Zuitch — PushKit token registration.
 * Path: httpdocs/api/v2/endpoints/voip-register.php
 *
 * Reached as:  POST /api/voip-register?access_token=<session>
 *   server_key  (required by api-v2.php)
 *   action      register | unregister | unregister_all   (default: register)
 *   voip_token  hex PushKit token (required for register/unregister)
 *   platform    ios                 (optional)
 *   bundle_id   com.zuitch.app      (optional)
 *   device_name iPhone 14 Pro       (optional)
 *   app_version 1.0.1 (20)          (optional)
 *
 * api-v2.php has already validated the server key and the access token and
 * populated $wo['user'], so this file only has to trust $wo['user']['user_id'].
 * A client can therefore never register a token against someone else's account.
 */

$response_data = array('api_status' => 400);

require_once 'assets/includes/zuitch_voip.php';

$user_id = (int)$wo['user']['user_id'];
$action  = !empty($_POST['action']) ? Wo_Secure($_POST['action'], 0) : 'register';

if (!in_array($action, array('register', 'unregister', 'unregister_all'), true)) {
    $error_code    = 4;
    $error_message = 'action must be register, unregister or unregister_all';
    return;
}

if ($action === 'unregister_all') {
    Zuitch_DeleteVoipToken($user_id);
    $response_data = array('api_status' => 200, 'action' => 'unregister_all');
    return;
}

$voip_token = !empty($_POST['voip_token'])
    ? preg_replace('/[^a-fA-F0-9]/', '', (string)$_POST['voip_token'])
    : '';

if (strlen($voip_token) < 32 || strlen($voip_token) > 200) {
    $error_code    = 5;
    $error_message = 'voip_token is missing or malformed';
    return;
}

if ($action === 'unregister') {
    Zuitch_DeleteVoipToken($user_id, $voip_token);
    $response_data = array('api_status' => 200, 'action' => 'unregister');
    return;
}

$ok = Zuitch_RegisterVoipToken($user_id, $voip_token, array(
    'platform'    => !empty($_POST['platform'])    ? Wo_Secure($_POST['platform'], 0)    : 'ios',
    'bundle_id'   => !empty($_POST['bundle_id'])   ? Wo_Secure($_POST['bundle_id'], 0)   : '',
    'device_name' => !empty($_POST['device_name']) ? Wo_Secure($_POST['device_name'], 0) : '',
    'app_version' => !empty($_POST['app_version']) ? Wo_Secure($_POST['app_version'], 0) : '',
));

if ($ok) {
    $response_data = array(
        'api_status'  => 200,
        'action'      => 'register',
        // Lets the app warn during development if the server half is not set up
        // yet, instead of silently never ringing.
        'voip_ready'  => Zuitch_VoipEnabled() ? 1 : 0,
    );
} else {
    $error_code    = 6;
    $error_message = 'could not store voip token';
}
