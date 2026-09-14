<?php
if ($f == 'get_next_zuitch_slide') {
    require_once(__DIR__ . '/../assets/includes/zuitch_feed.php');
    $wo['reels_next_chunk_request'] = true;
    $wo['reels_ui_second_post_button'] = $wo['config']['second_post_button'];
    $wo['config']['second_post_button'] = 'disabled';
    $html = '';
    $postsData = array(
        'limit' => 3,
        'filter_by' => 'all',
        'order' => 'desc',
        'is_reel' => 'all',
        'not_monetization' => true,
    );
    $reelOwnerName = '';

    if (!empty($_GET['zuitch_user'])) {
        $reelOwnerName = Wo_Secure($_GET['zuitch_user']);
        $reelOwnerId = Wo_UserIdFromUsername(Wo_Secure($_GET['zuitch_user']));
        if (!empty($reelOwnerId)) {
            $postsData['publisher_id'] = $reelOwnerId;
        }
    }

    if (empty($wo['watched_zuitch']) || !is_array($wo['watched_zuitch'])) {
        $wo['watched_zuitch'] = array();
    }

    if (!empty($wo['watched_zuitch'])) {
        $postsData['not_in'] = $wo['watched_zuitch'];
    }

    $slides = Wo_GetPosts($postsData);
    $slides = Zuitch_Feed_FilterPosts($slides);

    foreach ($slides as $wo['story']) {
        $wo['page'] = 'zuitch';
        $wo['story']['likeCount'] = Wo_CountLikes($wo['story']['id']);
        $wo['story']['commentCount'] = Wo_CountPostComment($wo['story']['id']);
        $wo['reelOwnerName'] = $reelOwnerName;

        $video = Zuitch_Feed_BuildMediaHtml($wo['story']);

        $userUrl = '';
        if (!empty($reelOwnerName)) {
            $userUrl = '/' . $reelOwnerName;
        }

        $html .= loadHTMLPage('zuitch/list', array(
            'ID' => $wo['story']['id'],
            'URL' => $wo['config']['site_url'] . '/zuitch/' . $wo['story']['id'] . $userUrl,
            'CLASS' => 'hidden',
            'STORY_ARRAY' => $wo['story'],
            'PUBLISHER_ARRAY' => $wo['story']['publisher'],
            'VIDEO' => $video,
        ));

        if (!in_array($wo['story']['id'], $wo['watched_zuitch'])) {
            $wo['watched_zuitch'][] = $wo['story']['id'];
        }
    }

    $data = array(
        'status' => 200,
        'html' => $html,
        'post_id' => (!empty($wo['story']) ? $wo['story']['id'] : ''),
        'url' => '',
    );

    if (!empty($wo['story']['id'])) {
        $data['url'] = Wo_SeoLink('index.php?link1=zuitch&id=' . $wo['story']['id']);
    }

    if (!empty($wo['watched_zuitch'])) {
        setcookie('watched_zuitch', json_encode($wo['watched_zuitch']), time() + (60 * 60 * 24), '/');
    }

    unset($wo['reels_next_chunk_request']);

    header('Content-type: application/json');
    echo json_encode($data);
    exit();
}
