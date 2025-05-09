<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.wowonder.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Copyright (c) 2018 WoWonder. All rights reserved.
// +------------------------------------------------------------------------+
$response_data   = array(
    'api_status' => 400
);
$required_fields = array(
    'username',
    'password',
    'email',
    'confirm_password'
);
if ($wo['config']['auto_username'] == 1) {
    $_POST['username'] = time() . rand(111111, 999999);
    if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
        $error_code    = 3;
        $error_message = $wo['lang']['first_name_last_name_empty'];
    }
    elseif (preg_match('/[^\w\s]+/u', $_POST['first_name']) || preg_match('/[^\w\s]+/u', $_POST['last_name'])) {
        $error_code    = 3;
        $error_message = $wo['lang']['username_invalid_characters'];
    }
}
foreach ($required_fields as $key => $value) {
    if (empty($_POST[$value]) && empty($error_code)) {
        $error_code    = 3;
        $error_message = $value . ' (POST) is missing';
    }
}

if (empty($error_code)) {
    $username         = $_POST['username'];
    $password         = $_POST['password'];
    $email            = $_POST['email'];
    $confirm_password = $_POST['confirm_password'];
    if (in_array(true, Wo_IsNameExist($username, 0))) {
        $error_code    = 4;
        $error_message = 'Username is already taken';
    } else if (in_array($username, $wo['site_pages']) || !preg_match('/^[\w]+$/', $username)) {
        $error_code    = 5;
        $error_message = 'Invalid username characters, please choose another username';
    } else if (strlen($username) < 5 OR strlen($username) > 32) {
        $error_code    = 6;
        $error_message = 'Username must be between 5 / 32 letters';
    } else if (Wo_EmailExists($email) === true) {
        $error_code    = 7;
        $error_message = 'E-mail is already taken';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_code    = 8;
        $error_message = 'E-mail is invalid';
    } else if (strlen($password) < 6) {
        $error_code    = 9;
        $error_message = 'Password is too short';
    } else if ($password != $confirm_password) {
        $error_code    = 10;
        $error_message = 'Passwords don\'t match';
    }
    if (empty($error_code)) {
        $activate  = ($wo['config']['emailValidation'] == '1') ? 0 : 1;
        //$device_id = (!empty($_POST['device_id'])) ? $_POST['device_id'] : '';
        $gender = 'male';
        if (in_array($_POST['gender'], array_keys($wo['genders']))) {
            $gender = $_POST['gender'];
        }
        $code = md5(rand(1111, 9999) . time());
        $account_data = array(
            'email' => Wo_Secure($email, 0),
            'username' => Wo_Secure($username, 0),
            'password' => $password,
            'email_code' => $code,
            'src' => 'Phone',
            'timezone' => 'UTC',
            'gender' => Wo_Secure($gender),
            'lastseen' => time(),
            'active' => Wo_Secure($activate)
        );
        if (!empty($_POST['android_m_device_id'])) {
            $account_data['android_m_device_id']  = Wo_Secure($_POST['android_m_device_id']);
        }
        if (!empty($_POST['ios_m_device_id'])) {
            $account_data['ios_m_device_id']  = Wo_Secure($_POST['ios_m_device_id']);
        }
        if (!empty($_POST['android_n_device_id'])) {
            $account_data['android_n_device_id']  = Wo_Secure($_POST['android_n_device_id']);
        }
        if (!empty($_POST['ios_n_device_id'])) {
            $account_data['ios_n_device_id']  = Wo_Secure($_POST['ios_n_device_id']);
        }
        if ($gender == 'female') {
            $account_data['avatar'] = 'upload/photos/f-avatar.jpg';
        }
        if (!empty($_POST['ref'])) {
            $get_ip = get_ip_address();
            if (!empty($get_ip)) {
                $_POST['ref'] = Wo_Secure($_POST['ref']);
                $ref_user_id = Wo_UserIdFromUsername($_POST['ref']);
                $user_date = Wo_UserData($ref_user_id);
                if (!empty($user_date)) {
                    if (ip_in_range($user_date['ip_address'], '/24') === false && $user_date['ip_address'] != $get_ip) {
                        $_SESSION['ref'] = $user_date['username'];
                        if (!empty($_SESSION['ref']) && $wo['config']['affiliate_type'] == 0) {
                            $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                            if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                $account_data['referrer'] = Wo_Secure($ref_user_id);
                                $account_data['src']      = Wo_Secure('Referrer');
                                if ($wo['config']['affiliate_level'] < 2) {
                                    $update_balance      = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                                }
                                unset($_SESSION['ref']);
                            }
                        }
                        elseif (!empty($_SESSION['ref']) && $wo['config']['affiliate_type'] == 1) {
                            $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                            if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                $account_data['ref_user_id']      = Wo_Secure($ref_user_id);
                            }
                        }
                    }
                }
            }
        }

        if ($wo['config']['auto_username'] == 1) {
            $account_data['first_name'] = Wo_Secure($_POST['first_name']);
            $account_data['last_name'] = Wo_Secure($_POST['last_name']);
        }

        $register     = Wo_RegisterUser($account_data);
        if ($register === true) {
            if (!empty($account_data['referrer']) && is_numeric($wo['config']['affiliate_level']) && $wo['config']['affiliate_level'] > 1) {
                $user_id = Wo_UserIdFromUsername($username);
                AddNewRef($account_data['referrer'],$user_id,$wo['config']['amount_ref']);
            }
            if (!empty($wo['config']['auto_friend_users'])) {
                $autoFollow = Wo_AutoFollow(Wo_UserIdFromUsername($_POST['username']));
            }
            if (!empty($wo['config']['auto_page_like'])) {
                Wo_AutoPageLike(Wo_UserIdFromUsername($_POST['username']));
            }
            if (!empty($wo['config']['auto_group_join'])) {
                Wo_AutoGroupJoin(Wo_UserIdFromUsername($_POST['username']));
            }
            
            if ($activate == 1) {
                $access_token        = sha1(rand(111111111, 999999999)) . md5(microtime()) . rand(11111111, 99999999) . md5(rand(5555, 9999));
                $time                = time();
                $user_id             = Wo_UserIdFromUsername($username);
                $device_type = 'phone';
                if (!empty($_POST['device_type']) && in_array($_POST['device_type'], array('phone','windows'))) {
                    $device_type = Wo_Secure($_POST['device_type']);
                }
                $create_access_token = mysqli_query($sqlConnect, "INSERT INTO " . T_APP_SESSIONS . " (`user_id`, `session_id`, `platform`, `time`) VALUES ('{$user_id}', '{$access_token}', '{$device_type}', '{$time}')");
                if ($create_access_token) {
                    $response_data = array(
                        'api_status' => 200,
                        'access_token' => $access_token,
                        'user_id' => $user_id,
                        'user_platform' => $device_type,
                    );
                }
            } elseif ($wo['config']['sms_or_email'] == 'mail') {
                $user_id             = Wo_UserIdFromUsername($username);
                $wo['user']        = $_POST;
                $wo['code']        = $code;
                $body              = Wo_LoadPage('emails/activate');
                $send_message_data = array(
                    'from_email' => $wo['config']['siteEmail'],
                    'from_name' => $wo['config']['siteName'],
                    'to_email' => $email,
                    'to_name' => $username,
                    'subject' => $wo['lang']['account_activation'],
                    'charSet' => 'utf-8',
                    'message_body' => $body,
                    'is_html' => true
                );
                $send              = Wo_SendMessage($send_message_data);
                if ($send) {
                    $response_data = array(
                        'api_status' => 220,
                        'message' => 'Registration successful! We have sent you an email, Please check your inbox/spam to verify your email.',
                        'user_id' => $user_id
                    );
                } else {
                    $error_code    = 11;
                    $error_message = 'Error found while sending the verification email, please try again later.';
                }
            }
            elseif ($wo['config']['sms_or_email'] == 'sms' && !empty($_POST['phone_num'])) {
                $random_activation = Wo_Secure(rand(11111, 99999));
                $message           = "Your confirmation code is: {$random_activation}";

                if (Wo_SendSMSMessage($_POST['phone_num'], $message) === true) {
                    $user_id             = Wo_UserIdFromUsername($username);
                    $query             = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `sms_code` = '{$random_activation}' WHERE `user_id` = {$user_id}");
                    $response_data = array(
                        'api_status' => 220,
                        'message' => 'Registration successful! We have sent you an sms, Please check your phone to verify your account.',
                        'user_id' => $user_id
                    );
                    cache($user_id, 'users', 'delete');
                } else {
                    $error_code    = 11;
                    $error_message = 'Error found while sending the verification sms, please try again later.';
                }
            }
            elseif ($wo['config']['sms_or_email'] == 'sms' && empty($_POST['phone_num'])) {
                $error_code    = 12;
                $error_message = 'phone_num can not be empty.';
            }
            if (!empty($response_data)) {
                $response_data['membership'] = false;
                if ($wo['config']['membership_system'] == 1) {
                    $response_data['membership'] = true;
                }
            }
        }
    }
}