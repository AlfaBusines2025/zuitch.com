<?php
if ($wo['loggedin'] == false) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

if ($wo['config']['reels_upload'] == 0) {
    header("Location: " . Wo_SeoLink('index.php?link1=home'));
    exit();
}

$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'reels-editor';
$wo['title']       = $wo['lang']['upload_reels'];
$wo['content']     = Wo_LoadPage('reels/editor');


