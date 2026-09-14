<?php 
if ($f == 'get_notifications') {
    $data          = array(
        'status' => 200,
        'html' => ''
    );
    $notifications = Wo_GetNotifications();
    if (!is_array($notifications)) {
        $notifications = array();
    }
    $notification_ids = array();
    $user_id          = Wo_Secure($wo['user']['user_id']);

    $follow_rows = array();
    $fq          = mysqli_query($sqlConnect, "SELECT f.`id`, f.`follower_id`, f.`time` FROM " . T_FOLLOWERS . " f INNER JOIN " . T_USERS . " u ON u.user_id = f.follower_id AND u.active = '1' WHERE f.following_id = {$user_id} AND f.follower_id <> {$user_id} AND f.active = '0'");
    if ($fq && mysqli_num_rows($fq)) {
        while ($row = mysqli_fetch_assoc($fq)) {
            $follow_rows[] = $row;
        }
    }

    $groups = GetGroupChatRequests();
    if (empty($groups) || !is_array($groups)) {
        $groups = array();
    }

    $items = array();
    foreach ($notifications as $n) {
        $items[] = array(
            'sort' => (int) $n['time'],
            'tie' => (int) $n['id'],
            'kind' => 'notif',
            'notif' => $n
        );
    }
    foreach ($follow_rows as $r) {
        $t = (int) $r['time'];
        $items[] = array(
            'sort' => $t > 0 ? $t : 0,
            'tie' => (int) $r['id'],
            'kind' => 'follow',
            'row' => $r
        );
    }
    foreach ($groups as $g) {
        $gid = is_object($g) ? (int) $g->id : (int) $g['id'];
        $items[] = array(
            'sort' => 0,
            'tie' => $gid,
            'kind' => 'group',
            'group' => $g
        );
    }

    usort($items, function ($a, $b) {
        if ($a['sort'] != $b['sort']) {
            return $b['sort'] - $a['sort'];
        }
        return $b['tie'] - $a['tie'];
    });

    if (count($items) > 0) {
        foreach ($items as $it) {
            if ($it['kind'] == 'notif') {
                $wo['notification'] = $it['notif'];
                $data['html'] .= Wo_LoadPage('header/notifecation');
                if ($wo['notification']['seen'] == 0) {
                    $notification_ids[] = $wo['notification']['id'];
                }
            } elseif ($it['kind'] == 'follow') {
                $wo['request'] = Wo_UserData($it['row']['follower_id']);
                if (!empty($wo['request']['user_id'])) {
                    $data['html'] .= Wo_LoadPage('header/follow-requests');
                    $data['html'] .= '<li class="divider"></li>';
                }
            } else {
                $gid = is_object($it['group']) ? $it['group']->group_id : $it['group']['group_id'];
                $wo['group_chat'] = Wo_GroupTabData($gid, false);
                if (!empty($wo['group_chat']['group_id'])) {
                    $data['html'] .= Wo_LoadPage('header/group-requests');
                    $data['html'] .= '<li class="divider"></li>';
                }
            }
        }
        if (!empty($notification_ids)) {
            $query_where = '\'' . implode('\', \'', $notification_ids) . '\'';
            $query       = "UPDATE " . T_NOTIFICATION . " SET `seen` = " . time() . " WHERE `id` IN ($query_where)";
            mysqli_query($sqlConnect, $query);
        }
    } else {
        $data['message'] = $wo['lang']['no_new_notification'];
    }
    $data['pending_follow_requests'] = (int) Wo_CountFollowRequests() + (int) Wo_CountGroupChatRequests();
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
