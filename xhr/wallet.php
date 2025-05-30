<?php
if ($f == 'wallet') {
    $dollar_to_point_cost = $wo['config']['dollar_to_point_cost'];
    if ($s == 'replenish-user-account') {
        $error = "";
        if (!isset($_GET['amount']) || !is_numeric($_GET['amount']) || $_GET['amount'] < 1) {
            $error = $error_icon . $wo['lang']['please_check_details'];
        }
        if (empty($error)) {
            $data = Wo_ReplenishWallet($_GET['amount']);
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        } else {
            header("Content-type: application/json");
            echo json_encode(array(
                'status' => 500,
                'error' => $error
            ));
            exit();
        }
    }
    if ($s == 'get-paid') {
        if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_GET['token']) && !empty($_GET['token'])) {
            include_once "assets/includes/paypal_config.php";
            $token = Wo_Secure($_GET['token']);
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url . '/v2/checkout/orders/' . $token . '/capture');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $wo['paypal_access_token'];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                header("Location: $site_url/payment-error?reason=invalid-payment");
                exit();
            }
            curl_close($ch);
            if (!empty($result)) {
                $result = json_decode($result);
                if (!empty($result->status) && $result->status == 'COMPLETED') {
                    if (!empty($wo["config"]['currency_array']) && in_array($wo["config"]['paypal_currency'], $wo["config"]['currency_array']) && $wo["config"]['paypal_currency'] != $wo['config']['currency'] && !empty($wo['config']['exchange']) && !empty($wo['config']['exchange'][$wo["config"]['paypal_currency']])) {
                        $_GET['amount'] = (($_GET['amount'] / $wo['config']['exchange'][$wo["config"]['paypal_currency']]));
                        //$sum = round($sum, 2);
                    }
                    if (Wo_ReplenishingUserBalance($_GET['amount'])) {
                        $_GET['amount'] = Wo_Secure($_GET['amount']);
                        $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ('" . $wo['user']['id'] . "', 'WALLET', '" . $_GET['amount'] . "', 'PayPal')");
                        $_SESSION['replenished_amount'] = $_GET['amount'];
                        if (!empty($_COOKIE['redirect_page'])) {
                            $redirect_page = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_COOKIE['redirect_page']);
                            $redirect_page = preg_replace('/\((.*?)\)/m', '', $redirect_page);
                            header("Location: " . $redirect_page);
                        } else {
                            header("Location: " . Wo_SeoLink('index.php?link1=wallet'));
                        }
                        exit();
                    } else {
                        header("Location: " . Wo_SeoLink('index.php?link1=wallet'));
                        exit();
                    }
                }
            } else {
                header("Location: " . Wo_SeoLink('index.php?link1=wallet'));
                exit();
            }
        } else if (isset($_GET['success']) && $_GET['success'] == 0) {
            header("Location: " . Wo_SeoLink('index.php?link1=wallet'));
            exit();
        } else {
            header("Location: " . Wo_SeoLink('index.php?link1=wallet'));
            exit();
        }
    }
    if ($s == 'remove' && isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
        $data['status'] = 304;

        if (Wo_DeleteUserAd($_GET['id'])) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'send' && $wo['loggedin'] === true) {
        $data = array(
            'status' => 400
        );
        $user_id = (!empty($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $_POST['user_id'] : 0;
        $amount = (!empty($_POST['amount']) && is_numeric($_POST['amount'])) ? $_POST['amount'] : 0;
        $userdata = Wo_UserData($user_id);
        $wallet = $wo['user']['wallet'];
        if (empty($user_id) || empty($amount) || empty($userdata) || empty(floatval($wallet)) || $amount < 0) {
            $data['message'] = $wo['lang']['please_check_details'];
        } else if ($wallet < $amount) {
            $data['message'] = $wo['lang']['amount_exceded'];
        } else {
            $amount = ($amount <= $wallet) ? $amount : $wallet;
            $up_data1 = array(
                'wallet' => sprintf('%.2f', $userdata['wallet'] + $amount)
            );
            $up_data2 = array(
                'wallet' => sprintf('%.2f', $wallet - $amount)
            );
            $recipient_name = $userdata['username'];
            $currency = Wo_GetCurrency($wo['config']['ads_currency']);
            $success_msg = $wo['lang']['money_sent_to'];
            $notif_msg = $wo['lang']['sent_you'];
            $data['status'] = 200;
            $data['message'] = "$success_msg@ $recipient_name";
            //$note1           = $success_msg . " " . $userdata['name'];
            $note1 = $userdata['name'];
            //$note2           = $wo['lang']['successfully_received_from'] . " " . $wo['user']['name'];
            $note2 = $wo['user']['name'];
            $db->where('user_id', $user_id)->update(T_USERS, $up_data1);

            mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$user_id}, 'RECEIVED', {$amount}, '{$note2}')");
            $db->where('user_id', $wo['user']['id'])->update(T_USERS, $up_data2);
            mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$wo['user']['user_id']}, 'SENT', {$amount}, '{$note1}')");
            cache($user_id, 'users', 'delete');
            cache($wo['user']['id'], 'users', 'delete');
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'type' => 'sent_u_money',
                'user_id' => $wo['user']['id'],
                'text' => "$notif_msg $amount$currency!",
                'url' => 'index.php?link1=wallet'
            );
            Wo_RegisterNotification($notification_data_array);
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'pay' && $wo['loggedin'] === true) {
        $data = array(
            'status' => 400
        );
        $price = 0;
        if (!empty($_GET['type']) && in_array($_GET['type'], array(
                'pro',
                'fund'
            ))) {
            if ($_GET['type'] == 'pro') {
                $img = "";
                if (!empty($_GET['pro_type']) && in_array($_GET['pro_type'], array_keys($wo["pro_packages"]))) {
                    $_GET['pro_type'] = Wo_Secure($_GET['pro_type']);

                    $img = $wo["pro_packages"][$_GET['pro_type']]['name'];

                    if ($wo["pro_packages"][$_GET['pro_type']]['price'] > $wo['user']['wallet']) {
                        $data['message'] = "<a href='" . $wo['config']['site_url'] . "/wallet'>" . $wo["lang"]["please_top_up_wallet"] . "</a>";
                    } elseif ($wo['user']['pro_type'] == $_GET['pro_type']) {
                        $data['message'] = $error_icon . $wo['lang']['something_wrong'];
                    } else {
                        $price = $wo["pro_packages"][$_GET['pro_type']]['price'];
                    }
                } else {
                    $data['message'] = $error_icon . $wo['lang']['something_wrong'];
                }
            } elseif ($_GET['type'] == 'fund') {
                if (!empty($_GET['price']) && is_numeric($_GET['price']) && $_GET['price'] > 0) {
                    if (!empty($_GET['fund_id']) && is_numeric($_GET['fund_id']) && $_GET['fund_id'] > 0) {
                        $fund_id = Wo_Secure($_GET['fund_id']);
                        $price = Wo_Secure($_GET['price']);
                        $fund = $db->where('id', $fund_id)->getOne(T_FUNDING);
                        if (empty($fund)) {
                            $data['message'] = $error_icon . $wo['lang']['fund_not_found'];
                        }
                    } else {
                        $data['message'] = $error_icon . $wo['lang']['something_wrong'];
                    }
                } else {
                    $data['message'] = $error_icon . $wo['lang']['amount_can_not_empty'];
                }
            }
            if (empty($data['message'])) {
                if ($_GET['type'] == 'pro') {
                    $is_pro = 0;
                    $stop = 0;
                    // $user   = Wo_UserData($wo['user']['user_id']);
                    // if ($user['is_pro'] == 1) {
                    //     $stop = 1;
                    //     if ($user['pro_type'] == 1) {
                    //         $time_ = time() - $star_package_duration;
                    //         if ($user['pro_time'] > $time_) {
                    //             $stop = 1;
                    //         }
                    //     } else if ($user['pro_type'] == 2) {
                    //         $time_ = time() - $hot_package_duration;
                    //         if ($user['pro_time'] > $time_) {
                    //             $stop = 1;
                    //         }
                    //     } else if ($user['pro_type'] == 3) {
                    //         $time_ = time() - $ultima_package_duration;
                    //         if ($user['pro_time'] > $time_) {
                    //             $stop = 1;
                    //         }
                    //     } else if ($user['pro_type'] == 4) {
                    //         if ($vip_package_duration > 0) {
                    //             $time_ = time() - $vip_package_duration;
                    //             if ($user['pro_time'] > $time_) {
                    //                 $stop = 1;
                    //             }
                    //         }
                    //     }
                    // }
                    if ($stop == 0) {
                        $pro_type = $_GET['pro_type'];
                        $is_pro = 1;
                    }
                    if ($stop == 0) {
                        $time = time();
                        if ($is_pro == 1) {
                            $update_array = array(
                                'is_pro' => 1,
                                'pro_time' => time(),
                                'pro_' => 1,
                                'pro_type' => $pro_type
                            );
                            if (in_array($pro_type, array_keys($wo['pro_packages'])) && $wo["pro_packages"][$pro_type]['verified_badge'] == 1) {
                                $update_array['verified'] = 1;
                            }
                            $mysqli = Wo_UpdateUserData($wo['user']['user_id'], $update_array);
                            //$notes              = $wo['lang']['upgrade_to_pro'] . " " . $img . " : Wallet";
                            //$notes              = $img . " : Wallet";
                            //$notes              = str_replace('{text}', $img . " : Wallet", $wo['lang']['trans_upgrade_to_pro']);
                            $notes = json_encode([
                                'pro_type' => $pro_type,
                                'method_type' => 'wallet'
                            ]);

                            $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$wo['user']['user_id']}, 'PRO', {$price}, '{$notes}')");
                            $create_payment = Wo_CreatePayment($pro_type);
                            if ($mysqli) {
                                if ((!empty($_SESSION['ref']) || !empty($wo['user']['ref_user_id'])) && $wo['config']['affiliate_type'] == 1 && $wo['user']['referrer'] == 0) {
                                    if (!empty($_SESSION['ref'])) {
                                        $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                                    } elseif (!empty($wo['user']['ref_user_id'])) {
                                        $ref_user_id = $wo['user']['ref_user_id'];
                                    }
                                    if ($wo['config']['amount_percent_ref'] > 0) {
                                        if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                            $update_user = Wo_UpdateUserData($wo['user']['user_id'], array(
                                                'referrer' => $ref_user_id,
                                                'src' => 'Referrer'
                                            ));
                                            $ref_amount = ($wo['config']['amount_percent_ref'] * $price) / 100;
                                            if ($wo['config']['affiliate_level'] < 2) {
                                                $update_balance = Wo_UpdateBalance($ref_user_id, $ref_amount);
                                            }
                                            if (is_numeric($wo['config']['affiliate_level']) && $wo['config']['affiliate_level'] > 1) {
                                                AddNewRef($ref_user_id, $wo['user']['user_id'], $ref_amount);
                                            }
                                            unset($_SESSION['ref']);
                                        }
                                    } else if ($wo['config']['amount_ref'] > 0) {
                                        if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                            $update_user = Wo_UpdateUserData($wo['user']['user_id'], array(
                                                'referrer' => $ref_user_id,
                                                'src' => 'Referrer'
                                            ));
                                            if ($wo['config']['affiliate_level'] < 2) {
                                                $update_balance = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                                            }
                                            if (is_numeric($wo['config']['affiliate_level']) && $wo['config']['affiliate_level'] > 1) {
                                                AddNewRef($ref_user_id, $wo['user']['user_id'], $wo['config']['amount_ref']);
                                            }
                                            unset($_SESSION['ref']);
                                        }
                                    }
                                }
                                $points = 0;
                                if ($wo['config']['point_level_system'] == 1) {
                                    $points = $price * $dollar_to_point_cost;
                                }
                                $wallet_amount = ($wo["user"]['wallet'] - $price);
                                $points_amount = ($wo['config']['point_allow_withdrawal'] == 0) ? ($wo["user"]['points'] - $points) : $wo["user"]['points'];
                                $query_one = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `points` = '{$points_amount}', `wallet` = '{$wallet_amount}' WHERE `user_id` = {$wo['user']['user_id']} ");
                                cache($wo['user']['id'], 'users', 'delete');
                                $data['status'] = 200;
                                $data['url'] = Wo_SeoLink('index.php?link1=upgraded');
                            }
                        } else {
                            $data['message'] = $error_icon . $wo['lang']['something_wrong'];
                        }
                    } else {
                        $data['message'] = $error_icon . $wo['lang']['something_wrong'];
                    }
                } elseif ($_GET['type'] == 'fund') {
                    $amount = $price;
                    //$notes              = "Doanted to " . mb_substr($fund->title, 0, 100, "UTF-8");
                    $notes = mb_substr($fund->title, 0, 100, "UTF-8");
                    //$notes              = str_replace('{text}', mb_substr($fund->title, 0, 100, "UTF-8"), $wo['lang']['trans_doanted_to']);
                    $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$wo['user']['user_id']}, 'DONATE', {$amount}, '{$notes}')");
                    $wallet_amount = ($wo["user"]['wallet'] - $price);
                    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `wallet` = '{$wallet_amount}' WHERE `user_id` = {$wo['user']['user_id']} ");
                    cache($wo['user']['id'], 'users', 'delete');
                    $admin_com = 0;
                    if (!empty($wo['config']['donate_percentage']) && is_numeric($wo['config']['donate_percentage']) && $wo['config']['donate_percentage'] > 0) {
                        $admin_com = ($wo['config']['donate_percentage'] * $amount) / 100;
                        $amount = $amount - $admin_com;
                    }
                    $user_data = Wo_UserData($fund->user_id);
                    $db->where('user_id', $fund->user_id)->update(T_USERS, array(
                        'balance' => $user_data['balance'] + $amount
                    ));
                    cache($fund->user_id, 'users', 'delete');
                    $fund_raise_id = $db->insert(T_FUNDING_RAISE, array(
                        'user_id' => $wo['user']['user_id'],
                        'funding_id' => $fund_id,
                        'amount' => $amount,
                        'time' => time()
                    ));
                    $post_data = array(
                        'user_id' => Wo_Secure($wo['user']['user_id']),
                        'fund_raise_id' => $fund_raise_id,
                        'time' => time(),
                        'multi_image_post' => 0
                    );
                    $id = Wo_RegisterPost($post_data);
                    $notification_data_array = array(
                        'recipient_id' => $fund->user_id,
                        'type' => 'fund_donate',
                        'url' => 'index.php?link1=show_fund&id=' . $fund->hashed_id
                    );
                    Wo_RegisterNotification($notification_data_array);
                    $data = array(
                        'status' => 200,
                        'url' => $config['site_url'] . "/show_fund/" . $fund->hashed_id
                    );
                }
            }
        } else {
            $data['message'] = $error_icon . $wo['lang']['something_wrong'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }

    if ($s == 'pay_with_qiwi') {
        if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
            $amount   = (int)Wo_Secure($_POST[ 'amount' ]);

            require 'assets/libraries/qiwi/vendor/autoload.php';

            $billPayments = new Qiwi\Api\BillPayments($wo['config']['qiwi_private_key']);


            $billId = $billPayments->generateId();

            $params = [
                'publicKey' => $wo['config']['qiwi_public_key'],
                'amount' => $amount,
                'billId' => $billId,
                'successUrl' => Wo_Link("requests.php/wallet/success_qiwi?user_id=") . $wo['user']['user_id'],
            ];

            $link = $billPayments->createPaymentForm($params);

            $data['status'] = 200;
            $data['url'] = $link;
        }
        else{
            $data = array(
                'status' => 400,
                'message' => "Not enough money"
            );
        }
    }
    if ($s == 'success_qiwi') {
        if (empty($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
            header('Location: ' . Wo_Link('wallet'));
            exit();
        }
        $user = $db->where("id", Wo_Secure($_GET["user_id"]))->getOne(T_USERS);
        if (!empty($user)) {
            $sign   = array_key_exists( 'HTTP_X_API_SIGNATURE_SHA256', $_SERVER ) ? stripslashes_deep( $_SERVER['HTTP_X_API_SIGNATURE_SHA256'] ) : '';
            $notice = json_decode( $_POST, true );

            require 'assets/libraries/qiwi/vendor/autoload.php';

            $billPayments = new Qiwi\Api\BillPayments($wo['config']['qiwi_private_key']);
            $result = $billPayments->checkNotificationSignature( $sign, $notice, $wo['config']['qiwi_private_key'] );

            if (!$result) {
                header('Location: ' . Wo_Link('wallet'));
                exit();
            }



            if ($notice['bill']['status']['value'] == 'PAID') {
                $amount = $notice['bill']['amount']['value'];
                Wo_ReplenishingUserBalance($amount);
                $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ('" . $wo['user']->user_id . "', 'WALLET', '" . $amount . "', 'qiwi')");

            }
        }
        header('Location: ' . Wo_Link('wallet'));
        exit();
    }



    if ($s == 'set' && $wo['loggedin'] === true) {
        if (!empty($_GET['type']) && in_array($_GET['type'], array(
                'pro',
                'fund'
            ))) {
            if ($_GET['type'] == 'pro') {
                setcookie("redirect_page", $wo['config']['site_url'] . '/go-pro', time() + (60 * 60), '/');
            } else if ($_GET['type'] == 'fund' && !empty($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
                $fund_id = Wo_Secure($_GET['id']);
                $fund = $db->where('id', $fund_id)->getOne(T_FUNDING);
                if (!empty($fund) && !empty($fund->id)) {
                    setcookie("redirect_page", $wo['config']['site_url'] . '/show_fund/' . $fund->hashed_id, time() + (60 * 60), '/');
                }
            }
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'check_credit' && $wo['loggedin'] === true) {
        $data['status'] = 400;
        if (!empty($_POST['amount'])) {
            if (($_POST['amount'] / $wo['config']['credit_price']) > $wo['user']['wallet']) {
                $data['message'] = $wo['lang']['not_enough_wallet_to_credits'];
            } else {
                $data['status'] = 200;
            }
        } else {
            $data['message'] = $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'buy_credit' && $wo['loggedin'] === true) {
        $data['status'] = 400;
        if (!empty($_POST['amount'])) {
            if (($_POST['amount'] / $wo['config']['credit_price']) > $wo['user']['wallet']) {
                $data['message'] = $wo['lang']['not_enough_wallet_to_credits'];
            } else {
                $amount = Wo_Secure($_POST['amount']);
                $notes = $wo['lang']['ai_credit_purchase'];
                $dec = ($amount / $wo['config']['credit_price']);
                mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$wo['user']['user_id']}, 'Credits', {$dec}, '{$notes}')");
                $db->where('user_id', $wo['user']['id'])->update(T_USERS, [
                    'wallet' => $db->dec($dec),
                    'credits' => $db->inc($amount)
                ]);
                $data['status'] = 200;
            }
        } else {
            $data['message'] = $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_credits' && $wo['loggedin'] === true) {
        $data['status'] = 200;
        $data['credits'] = $wo['user']['credits'];
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }

    if ($s == 'monetization' && $wo['loggedin'] === true) {
        $data = Wo_SubscriptionPay($_POST['monetization_id']);
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }

    if ($s == 'delete-subscription' && $wo['loggedin'] === true) {
        $data['status'] = 400;
        $data['message'] = $wo['lang']['please_check_details'];
        $type = Wo_Secure($_POST['type']);
        $id = Wo_Secure($_POST['id']);
        if ($type == 'monetization') {
            $monetization = $db->where('id', $id)->where('user_id',$wo['user']['id'])->delete(T_MONETIZATION_SUBSCRIBTION);
            $data['status'] = 200;
            $data['message'] = $wo['lang']['unsubscribe_successful'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
