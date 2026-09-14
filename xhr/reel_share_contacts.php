<?php
if ($f == 'reel_share_contacts') {
    header('Content-Type: application/json; charset=UTF-8');
    $out = array(
        'status' => 400,
        'users' => array(),
    );
    if (!empty($wo['loggedin']) && !empty($wo['user']['user_id'])) {
        $out['status'] = 200;
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        if ($q !== '') {
            $rows = Wo_GetFollowingSug(30, $q);
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (empty($row['username'])) {
                        continue;
                    }
                    $uid = !empty($row['id']) ? (int) $row['id'] : 0;
                    if ($uid < 1) {
                        $uid = (int) Wo_UserIdFromUsername($row['username']);
                    }
                    if ($uid < 1) {
                        continue;
                    }
                    $out['users'][] = array(
                        'id' => $uid,
                        'username' => $row['username'],
                        'name' => !empty($row['label']) ? $row['label'] : $row['username'],
                        'avatar' => !empty($row['img']) ? $row['img'] : '',
                    );
                }
            }
        } else {
            $following = Wo_GetFollowing($wo['user']['user_id'], '', 18);
            if (is_array($following)) {
                foreach ($following as $u) {
                    if (empty($u['user_id'])) {
                        continue;
                    }
                    $out['users'][] = array(
                        'id' => (int) $u['user_id'],
                        'username' => $u['username'],
                        'name' => !empty($u['name']) ? $u['name'] : $u['username'],
                        'avatar' => !empty($u['avatar']) ? $u['avatar'] : '',
                    );
                }
            }
        }
    }
    echo json_encode($out);
    exit();
}
