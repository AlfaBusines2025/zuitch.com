<?php 

 
function API_SendNotification(){
	global $wo,$sqlConnect;
	
	
    $notif_text = '';
	$type = 'admin_notification';
	$url = 'index.php';
	$time = time();
	$notif = isset($_POST['notification']) ? mysqli_real_escape_string($sqlConnect, $_POST['notification']) : 0;
	$recipient_id = isset($_POST['id']) ? mysqli_real_escape_string($sqlConnect, $_POST['id']) : 0;
	$post_id = isset($_POST['post_id']) ? mysqli_real_escape_string($sqlConnect, $_POST['post_id']) : 0;
	$storage = isset($_POST['storage']) ? mysqli_real_escape_string($sqlConnect, $_POST['storage']) : 'default';
	$notifier_id = API_GetAnyNotifierId($recipient_id);
	
	 
	if(is_numeric($post_id) && is_numeric($recipient_id) && $post_id > 0  && $recipient_id > 0){
 
		switch($notif){
			
			case 'processing_stream':
	 
							
				$notif_text = 'Your live stream its generating now by our system. You will be notified when it is ready.';
				mysqli_query($sqlConnect, "insert into ".T_NOTIFICATION." set `time`='{$time}',`notifier_id`='{$notifier_id}',`recipient_id`='{$recipient_id}',`type`='{$type}',`url`='{$url}',`text`='{$notif_text}'");
				
				// make the post invisible untill the stream its processed
				mysqli_query($sqlConnect, "update ".T_POSTS." set `active`='0',`postType`='post' where `id`='{$post_id}'");

			break;
			
			case 'stream_processed':
				$notif_text = 'Your live stream its ready! Now you can see it on your timeline.';
				mysqli_query($sqlConnect, "insert into ".T_NOTIFICATION." set `time`='{$time}',`notifier_id`='{$notifier_id}',`recipient_id`='{$recipient_id}',`type`='{$type}',`url`='{$url}',`text`='{$notif_text}'");
				
				// set the post available
				mysqli_query($sqlConnect, "update ".T_POSTS." set `active`='1' where `id`='{$post_id}'");
				mysqli_query($sqlConnect, "update `vy_live_broadcasts` set `storage`='{$storage}' where `post_id`='{$post_id}'");
			
			break;
			
			
			
		}
	}
	return true;
	
}
 
function API_GetAnyNotifierId($id = 0){
	global $wo,$sqlConnect;
	
	$id = $id > 0 ? $id : 2;
    $q = mysqli_query($sqlConnect, "select `user_id` from ".T_USERS." where `user_id`<>'{$id}' limit 1");
    $r = mysqli_fetch_assoc($q);
 	
	if(isset($r['user_id']))
		$id = $r['user_id'];
 
	
	return $id;
	
}