<?php
require_once(__DIR__ . '/../assets/includes/zuitch_feed.php');

if ($wo['loggedin'] == false) {
    header('Location: ' . $wo['config']['site_url']);
    exit();
}

$wo['reels_ui_second_post_button'] = $wo['config']['second_post_button'];
$wo['config']['second_post_button'] = 'disabled';

if (empty($wo['watched_zuitch']) || !is_array($wo['watched_zuitch'])) {
    $wo['watched_zuitch'] = array();
}

$html = '';
$main_item = 0;
$reelOwnerName = '';
$getPosts = false;
$postsData = array(
    'limit' => 5,
    'filter_by' => 'all',
    'order' => 'desc',
    'is_reel' => 'all',
    'not_monetization' => true,
);

if (!empty($_GET['user'])) {
    $reelOwnerName = Wo_Secure($_GET['user']);
    $reelOwnerId = Wo_UserIdFromUsername(Wo_Secure($_GET['user']));
    if (!empty($reelOwnerId)) {
        $postsData['publisher_id'] = $reelOwnerId;
        $getPosts = true;
    }
}

if (empty($_GET['id'])) {
    $getPosts = true;
}

if (!empty($wo['watched_zuitch']) && empty($_GET['id'])) {
    $postsData['not_in'] = $wo['watched_zuitch'];
}

$items = array();
$id = 0;

if ($getPosts) {
    $items = Wo_GetPosts($postsData);
    $items = Zuitch_Feed_FilterPosts($items);
    if (empty($items)) {
        setcookie('watched_zuitch', json_encode(array()), time() + (60 * 60 * 24), '/');
        $wo['watched_zuitch'] = array();
        $postsData['not_in'] = $wo['watched_zuitch'];
        $items = Wo_GetPosts($postsData);
        $items = Zuitch_Feed_FilterPosts($items);
    }
    if (!empty($items)) {
        $id = $items[0]['id'];
    }
}

if (!empty($_GET['id'])) {
    $id = Wo_Secure($_GET['id']);
    $wo['story'] = Wo_PostData($id);
    if (empty($wo['story'])) {
        header('Location: ' . Wo_SeoLink('index.php?link1=zuitch'));
        exit();
    }
    $items = array($wo['story']);
    setcookie('watched_zuitch', json_encode(array()), time() + (60 * 60 * 24), '/');
    $wo['watched_zuitch'] = array($wo['story']['id']);
    $postsData['not_in'] = $wo['watched_zuitch'];
    $next = Wo_GetPosts($postsData);
    $next = Zuitch_Feed_FilterPosts($next);
    if (!empty($next)) {
        $items = array_merge($items, $next);
    }
}

$wo['reelOwnerName'] = $reelOwnerName;
$wo['page'] = 'zuitch';
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords'] = $wo['config']['siteKeywords'];
$wo['title'] = 'Zuitch';

if (empty($items)) {
    $wo['page_url'] = $wo['config']['site_url'] . '/zuitch';
    if (!empty($reelOwnerName)) {
        $wo['page_url'] .= '/' . $reelOwnerName;
    }
    $wo['content'] = loadHTMLPage('zuitch/empty', array('owner' => $reelOwnerName));
} else {
    $wo['page_url'] = $wo['config']['site_url'] . '/zuitch/' . $id;
    if (!empty($reelOwnerName)) {
        $wo['page_url'] .= '/' . $reelOwnerName;
    }

    foreach ($items as $key => $wo['story']) {
        if (!in_array($wo['story']['id'], $wo['watched_zuitch'])) {
            $wo['watched_zuitch'][] = $wo['story']['id'];
        }
        $wo['story']['likeCount'] = Wo_CountLikes($wo['story']['id']);
        $wo['story']['commentCount'] = Wo_CountPostComment($wo['story']['id']);

        $video = Zuitch_Feed_BuildMediaHtml($wo['story']);

        $userUrl = '';
        if (!empty($reelOwnerName)) {
            $userUrl = '/' . $reelOwnerName;
        }

        $html .= loadHTMLPage('zuitch/list', array(
            'ID' => $wo['story']['id'],
            'URL' => $wo['config']['site_url'] . '/zuitch/' . $wo['story']['id'] . $userUrl,
            'CLASS' => ($main_item == 0 ? '' : 'hidden'),
            'STORY_ARRAY' => $wo['story'],
            'PUBLISHER_ARRAY' => $wo['story']['publisher'],
            'VIDEO' => $video,
        ));

        $main_item = 1;
    }

    if (!empty($wo['watched_zuitch'])) {
        setcookie('watched_zuitch', json_encode($wo['watched_zuitch']), time() + (60 * 60 * 24), '/');
    }

    $wo['content'] = loadHTMLPage('zuitch/content', array(
        'html' => $html,
    ));
}
