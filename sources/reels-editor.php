<?php
if ($wo['loggedin'] == false) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

if ($wo['config']['reels_upload'] == 0) {
    header("Location: " . Wo_SeoLink('index.php?link1=home'));
    exit();
}

$wo['page_profile'] = array();
if (!empty($_GET['page_id']) && is_numeric($_GET['page_id']) && $_GET['page_id'] > 0) {
    $reel_page_id = Wo_Secure($_GET['page_id']);
    if (Wo_IsPageOnwer($reel_page_id)) {
        $page_data = Wo_PageData($reel_page_id);
        if (!empty($page_data) && !empty($page_data['page_id'])) {
            $wo['page_profile'] = $page_data;
        }
    }
}

$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'reels-editor';
$wo['title']       = $wo['lang']['upload_reels'];
$wo['content']     = Wo_LoadPage('reels/editor');
