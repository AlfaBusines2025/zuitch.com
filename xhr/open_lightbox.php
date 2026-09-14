<?php
if ($f == 'open_lightbox') {
    $redirect = null;
    $html = '';
    if (!empty($_GET['post_id'])) {
        $wo['story'] = Wo_PostData($_GET['post_id']);
        if (!empty($wo['story'])) {
            if ($wo['story']['postPrivacy'] == 6 && $wo['story']['publisher']['user_id'] !== $wo['user']['user_id']) {
                if (!Wo_IsSubscriptionPaidForPublisher($wo['story']['publisher']['user_id'])) {
                    setcookie("redirect_back_after_subscription", $_GET['post_id']);
                    $redirect = $wo['config']['site_url'] . '/monetization/' . $wo['story']['publisher']['username'];
                } else {
                    $html = Wo_LoadPage('lightbox/content');
                }
            } else {
                $html = Wo_LoadPage('lightbox/content');
            }
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html,
        'redirect' => $redirect,
    );
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    echo json_encode($data);
    exit();
}
