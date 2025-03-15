<?php

/*
# Live Stream Plugin.
# Copyright 2022 by Vanea Young
*/
 
require_once('assets/init.php');

// api 
if(isset($_POST['source']) && $_POST['source'] == 'api'){
	
 return API_SendNotification();
 exit();
 
}
 


if (!$wo['loggedin']) {
	
		header("location: /welcome?returnto=".urlencode($_SERVER['REQUEST_URI']));
		
}


try {
  
	// build engine
	$core = new VY_LIVESTREAM_CORE;
  
    $live = $core->im_live();
 
 
	// get param
	$cmd = isset($_GET['cmd']) ? $core->test_input($_GET['cmd']) : ( isset($_POST['cmd']) ? $core->test_input($_POST['cmd']) : '');
	$view_as_json = isset($_GET['view_as']) ? $core->test_input($_GET['view_as']) : ( isset($_POST['view_as']) ? $core->test_input($_POST['view_as']) : '');
	$id = isset($_GET['id']) ? $core->test_input($_GET['id']) : ( isset($_POST['id']) ? $core->test_input($_POST['id']) : '');

	switch ($cmd){
	case 'getComments':
	echo $core->jencode($live->getComments($id));
	break;
	case 'getProductDetails':
	echo $core->jencode($live->getTopProduct($id));
	break;
	case 'replayproductbroadcast':
	$live->replayproductbroadcast($id);
	break;	
	case 'validatePlaytubeVideo':
	$live->validatePlaytubeVideo();
	break;
	case 'watchstream':
	header("location: /");
	exit();
	break;
	case 'get-content':
	$live->getContent();
	break;
	case 'popup':
	$live->getPopup();
	break;
	case 'get-prelive-st':
	echo $live->getPreLiveSt();
	break;
	case 'golive':
	$live->goLive();
	break;
	case 'stoplive':
	$live->stopLive();
	break;
	case 'join_live': 
	$live->joinLive();
	break;
	case 'addcomment':
	echo $live->AddComment();
	break;
	case 'showdashboard':
	echo $live->showdashboard();
	break;
	case 'get-viewers':
	echo $live->getViewers();
	break;
	case 'get-available-for-moder':
	echo $live->availableViewersForModerator();
	break;
	case 'remove-moderators':
	echo $live->removeModerators();
	break;
	case 'get-userdetails':
	echo json_encode($live->lv_getUserDetails($id));
	break;
	case 'generate-stream-key':
	echo $live->generateUniqueStreamKey();
	break;
	case 'record':
	$live->recording();
	break;
	case 'rename-obs-file':
	$live->renameObsFile();
	break;
	case 'delete-crashed':
	echo $live->delete_crashed();
	break;
	case 'delete-broadcast':
	$live->deleteShortVideos();
	break;
	case 'remove-files':
	$live->removeFiles();
	break;
	case 'mob_popup':
	$live->mob_popup();
	break;
	case 'get-turn-credentials':
	echo $live->getTurnCredentials();
	break;
	case 'generateCover':
	$live->generateCover();
	break;
	case 'get_rtmp_hls_path':
	echo $live->getRtmpHLS_Path();
	break;
	case 'blueimpupload':
	$live->blueimpupload();
	break;
	case 'get-products-sugg':
	$live->getProductsSugg();
	break;
	case 'openuser':
	$core->openUser();
	break;
	case 'getProductDetailsModal':
	$live->getProductDetailsModal($id);
	break;
	case 'get-fullsize-modal':
	$live->getFullSizeModal();
	break;
	case 'insertocart':
	$live->insertProductToCart($id);
	break;
	case 'deleteFromCart':
	$live->deleteProductFromCart($id);
	break;
	case 'deleteProduct':
	echo $live->deleteProduct($id);
	break;
	case 'setproductdefcover':
	$live->changeProductDefCover();
	break;
	case 'isbroadcastalive':
	echo $live->isLiveNow($id);
	break;
	case 'delete_stream':
	$live->delete_stream($id);
	break;
	default:
 
    ob_start();
    $live->constructPage();
    $page_content = ob_get_contents();
    ob_end_clean();
	
	$wo['description'] = $wo['config']['siteDesc'];
	$wo['keywords']    = $wo['config']['siteKeywords'];
	$wo['page']        = $core->lang['page_title_go_live'];
	$wo['title']       = $core->lang['page_title_go_live'];
	$wo['content']     = $page_content; 
	echo Wo_Loadpage('container');
	
	break;
	
	
	}
	
	} catch (Exception $e) {
	print $e->getMessage();
}
