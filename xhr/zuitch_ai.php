<?php
require_once 'assets/includes/zuitch_ai.php';

$data = array('status' => 400);
$s = isset($s) ? $s : '';

if ($wo['loggedin'] == false) {
    $data['message'] = 'login_required';
    header('Content-type: application/json');
    echo json_encode($data);
    exit();
}

$userId = (int) $wo['user']['user_id'];

if ($s == 'status') {
    $data = array(
        'status' => 200,
        'enabled' => zuitch_ai_enabled() ? 1 : 0,
        'is_superadmin' => zuitch_ai_is_superadmin() ? 1 : 0,
        'images' => (string) zuitch_ai_cfg('zuitch_ai_images', '0') === '1' ? 1 : 0,
        'files' => (string) zuitch_ai_cfg('zuitch_ai_files', '0') === '1' ? 1 : 0,
        'voice' => (string) zuitch_ai_cfg('zuitch_ai_voice', '0') === '1' ? 1 : 0,
        'tts' => (string) zuitch_ai_cfg('zuitch_ai_tts', '1') === '1' ? 1 : 0,
        'image_max_mb' => 4,
        'file_max_mb' => 2,
        'voice_max_sec' => 60,
    );
} elseif ($s == 'list_chats') {
    if (!zuitch_ai_enabled()) {
        $data['message'] = 'disabled';
    } else {
        $data = array(
            'status' => 200,
            'chats' => zuitch_ai_list_chats($userId, 50),
        );
    }
} elseif ($s == 'new_chat') {
    if (!zuitch_ai_enabled()) {
        $data['message'] = 'disabled';
    } else {
        $id = zuitch_ai_create_chat($userId);
        $data = array(
            'status' => 200,
            'chat' => zuitch_ai_get_chat($id, $userId),
        );
    }
} elseif ($s == 'get_chat') {
    $chatId = !empty($_POST['chat_id']) ? (int) $_POST['chat_id'] : 0;
    $chat = zuitch_ai_get_chat($chatId, $userId);
    if (!$chat && zuitch_ai_is_superadmin() && $chatId > 0) {
        $chat = zuitch_ai_get_chat($chatId, 0);
    }
    if (!$chat) {
        $data['message'] = 'not_found';
    } else {
        $messages = zuitch_ai_get_messages($chatId, 200);
        // attach ratings for assistant messages
        global $sqlConnect;
        foreach ($messages as &$m) {
            $m['rating'] = null;
            if ($m['role'] === 'assistant') {
                $mid = (int) $m['id'];
                $rq = mysqli_query($sqlConnect, "SELECT * FROM " . T_ZUITCH_AI_RATINGS . " WHERE `message_id` = {$mid} LIMIT 1");
                if ($rq && ($rr = mysqli_fetch_assoc($rq))) {
                    $m['rating'] = $rr;
                }
            }
        }
        unset($m);
        $data = array(
            'status' => 200,
            'chat' => $chat,
            'messages' => $messages,
            'is_superadmin' => zuitch_ai_is_superadmin() ? 1 : 0,
        );
    }
} elseif ($s == 'delete_chat') {
    $chatId = !empty($_POST['chat_id']) ? (int) $_POST['chat_id'] : 0;
    if (zuitch_ai_delete_chat($chatId, $userId)) {
        $data = array('status' => 200);
    } else {
        $data['message'] = 'not_found';
    }
} elseif ($s == 'delete_all') {
    zuitch_ai_delete_all_chats($userId);
    $data = array('status' => 200);
} elseif ($s == 'send') {
    if (!zuitch_ai_enabled()) {
        $data['message'] = 'disabled';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    $text = isset($_POST['text']) ? trim((string) $_POST['text']) : '';
    $chatId = !empty($_POST['chat_id']) ? (int) $_POST['chat_id'] : 0;
    $daily = (int) zuitch_ai_cfg('zuitch_ai_daily_limit', 40);
    if ($daily > 0 && zuitch_ai_count_today($userId) >= $daily) {
        $data['message'] = 'daily_limit';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    if ($text === '' && empty($_FILES['image']) && empty($_FILES['file'])) {
        $data['message'] = 'empty';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    if ($chatId < 1) {
        $title = $text !== '' ? mb_substr($text, 0, 60) : 'Nueva conversación';
        $chatId = zuitch_ai_create_chat($userId, $title);
    } else {
        $chat = zuitch_ai_get_chat($chatId, $userId);
        if (!$chat) {
            $data['message'] = 'not_found';
            header('Content-type: application/json');
            echo json_encode($data);
            exit();
        }
        // Update title if still default and first message
        if ($chat['title'] === 'Nueva conversación' && $text !== '') {
            global $sqlConnect;
            $t = mysqli_real_escape_string($sqlConnect, mb_substr($text, 0, 60));
            mysqli_query($sqlConnect, "UPDATE " . T_ZUITCH_AI_CHATS . " SET `title` = '{$t}' WHERE `id` = {$chatId}");
        }
    }

    $imagePayload = null;
    $fileError = '';
    if (!empty($_FILES['image']) && (string) zuitch_ai_cfg('zuitch_ai_images', '0') === '1') {
        $exts = array_map('trim', explode(',', strtolower((string) zuitch_ai_cfg('zuitch_ai_image_ext', 'jpg,jpeg,png,webp'))));
        $name = isset($_FILES['image']['name']) ? (string) $_FILES['image']['name'] : '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, $exts, true) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            if (!empty($_FILES['image']['size']) && (int) $_FILES['image']['size'] > 4 * 1024 * 1024) {
                $fileError = 'La imagen supera 4 MB.';
            } else {
                $mime = 'image/jpeg';
                if ($ext === 'png') {
                    $mime = 'image/png';
                } elseif ($ext === 'webp') {
                    $mime = 'image/webp';
                }
                $bin = file_get_contents($_FILES['image']['tmp_name']);
                if ($bin !== false && strlen($bin) < 4 * 1024 * 1024) {
                    $b64 = base64_encode($bin);
                    $imagePayload = array(
                        'type' => 'image_url',
                        'image_url' => array('url' => 'data:' . $mime . ';base64,' . $b64),
                    );
                    if ($text === '') {
                        $text = 'Describe o ayuda con esta imagen en el contexto de Zuitch.';
                    }
                }
            }
        }
    }
    if ($fileError === '' && !empty($_FILES['file']) && (string) zuitch_ai_cfg('zuitch_ai_files', '0') === '1') {
        $exts = array_map('trim', explode(',', strtolower((string) zuitch_ai_cfg('zuitch_ai_file_ext', 'txt,csv,md,pdf'))));
        $name = isset($_FILES['file']['name']) ? (string) $_FILES['file']['name'] : '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, $exts, true) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            if (!empty($_FILES['file']['size']) && (int) $_FILES['file']['size'] > 2 * 1024 * 1024) {
                $fileError = 'El archivo supera 2 MB.';
            } else {
                $extracted = zuitch_ai_extract_file_text($_FILES['file']['tmp_name'], $ext, 4000);
                if ($extracted === false) {
                    if ($ext === 'pdf') {
                        $fileError = 'No se pudo leer el PDF. Conviértelo a .txt o .md e inténtalo de nuevo.';
                    } else {
                        $fileError = 'No se pudo leer el archivo adjunto.';
                    }
                } else {
                    $text .= "\n\n[Archivo adjunto {$name}]\n" . $extracted;
                    if (trim(str_replace(array("\n", '[Archivo adjunto ' . $name . ']'), '', $text)) === '') {
                        $text = "Resume o ayúdame con este archivo en el contexto de Zuitch:\n\n[Archivo adjunto {$name}]\n" . $extracted;
                    }
                }
            }
        }
    }
    if ($fileError !== '') {
        $data = array('status' => 400, 'message' => $fileError, 'chat_id' => $chatId);
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }

    zuitch_ai_add_message($chatId, $userId, 'user', $text, '', null);

    try {
        $result = zuitch_ai_build_reply($text, $chatId, $imagePayload);
        $msgId = zuitch_ai_add_message(
            $chatId,
            $userId,
            'assistant',
            $result['answer'],
            $result['intent'],
            $result['cards']
        );
        $data = array(
            'status' => 200,
            'chat_id' => $chatId,
            'message' => array(
                'id' => $msgId,
                'role' => 'assistant',
                'content' => $result['answer'],
                'intent' => $result['intent'],
                'cards' => $result['cards'],
                'corrections_note' => $result['corrections_note'],
            ),
        );
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Incorrect API key') !== false || stripos($msg, 'invalid_api_key') !== false) {
            $msg = 'La API key de OpenAI no es válida. Revísala en admin-cp → Zuitch AI / AI Settings.';
        } elseif (stripos($msg, 'insufficient_quota') !== false || stripos($msg, 'billing') !== false) {
            $msg = 'Sin crédito en OpenAI. Recarga la cuenta de Zuitch y vuelve a intentar.';
        } elseif (stripos($msg, 'rate_limit') !== false) {
            $msg = 'Demasiadas solicitudes a OpenAI. Espera unos segundos e intenta de nuevo.';
        } elseif ($msg === '' || stripos($msg, 'empty') !== false) {
            $msg = 'No se recibió respuesta del modelo. Intenta de nuevo.';
        }
        $data = array(
            'status' => 400,
            'message' => $msg,
            'chat_id' => $chatId,
        );
    }
} elseif ($s == 'voice') {
    if (!zuitch_ai_enabled()) {
        $data['message'] = 'disabled';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    if ((string) zuitch_ai_cfg('zuitch_ai_voice', '0') !== '1') {
        $data['message'] = 'Las notas de voz están desactivadas. Actívalas en admin-cp → Zuitch AI.';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    $chatId = !empty($_POST['chat_id']) ? (int) $_POST['chat_id'] : 0;
    $daily = (int) zuitch_ai_cfg('zuitch_ai_daily_limit', 40);
    if ($daily > 0 && zuitch_ai_count_today($userId) >= $daily) {
        $data['message'] = 'daily_limit';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    if (empty($_FILES['audio']) || !is_uploaded_file($_FILES['audio']['tmp_name'])) {
        $data['message'] = 'No se recibió audio. Permite el micrófono e inténtalo de nuevo.';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    if (!empty($_FILES['audio']['size']) && (int) $_FILES['audio']['size'] > 5 * 1024 * 1024) {
        $data['message'] = 'El audio supera 5 MB. Graba una nota más corta (máx. ~60 s).';
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
    $origName = isset($_FILES['audio']['name']) ? (string) $_FILES['audio']['name'] : 'audio.webm';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if ($ext === '' || $ext === 'blob') {
        $mime = isset($_FILES['audio']['type']) ? (string) $_FILES['audio']['type'] : '';
        if (strpos($mime, 'mp4') !== false || strpos($mime, 'm4a') !== false) {
            $origName = 'audio.m4a';
        } elseif (strpos($mime, 'ogg') !== false) {
            $origName = 'audio.ogg';
        } elseif (strpos($mime, 'mpeg') !== false || strpos($mime, 'mp3') !== false) {
            $origName = 'audio.mp3';
        } else {
            $origName = 'audio.webm';
        }
    }
    if ($chatId < 1) {
        $chatId = zuitch_ai_create_chat($userId, 'Nota de voz');
    } else {
        $chat = zuitch_ai_get_chat($chatId, $userId);
        if (!$chat) {
            $data['message'] = 'not_found';
            header('Content-type: application/json');
            echo json_encode($data);
            exit();
        }
    }
    try {
        $extSave = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($extSave === '' || $extSave === 'blob') {
            $extSave = 'webm';
        }
        $tmpAudio = $_FILES['audio']['tmp_name'];
        $tr = zuitch_ai_transcribe($tmpAudio, $origName);
        $transcript = is_array($tr) ? (string) $tr['text'] : (string) $tr;
        $lang = is_array($tr) && !empty($tr['language']) ? (string) $tr['language'] : '';
        $audioUrl = zuitch_ai_store_voice_file($tmpAudio, $userId, $extSave);
        $userText = '[Nota de voz] ' . $transcript;
        $voiceMeta = array(
            'voice' => array(
                'url' => $audioUrl,
                'transcript' => $transcript,
                'lang' => $lang,
            ),
        );
        zuitch_ai_add_message($chatId, $userId, 'user', $userText, 'voice', $voiceMeta);
        global $sqlConnect;
        $chatRow = zuitch_ai_get_chat($chatId, $userId);
        if ($chatRow && $chatRow['title'] === 'Nueva conversación') {
            $t = mysqli_real_escape_string($sqlConnect, mb_substr($transcript, 0, 60));
            mysqli_query($sqlConnect, "UPDATE " . T_ZUITCH_AI_CHATS . " SET `title` = '{$t}' WHERE `id` = {$chatId}");
        }
        $result = zuitch_ai_build_reply($transcript, $chatId, null);
        $msgId = zuitch_ai_add_message(
            $chatId,
            $userId,
            'assistant',
            $result['answer'],
            $result['intent'],
            $result['cards']
        );
        $data = array(
            'status' => 200,
            'chat_id' => $chatId,
            'transcript' => $transcript,
            'audio_url' => $audioUrl,
            'lang' => $lang,
            'message' => array(
                'id' => $msgId,
                'role' => 'assistant',
                'content' => $result['answer'],
                'intent' => $result['intent'],
                'cards' => $result['cards'],
                'corrections_note' => $result['corrections_note'],
                'lang' => $lang,
            ),
        );
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Incorrect API key') !== false || stripos($msg, 'invalid_api_key') !== false) {
            $msg = 'La API key de OpenAI no es válida. Revísala en admin-cp → Zuitch AI.';
        } elseif (stripos($msg, 'insufficient_quota') !== false || stripos($msg, 'billing') !== false) {
            $msg = 'Sin crédito en OpenAI para transcribir audio.';
        } elseif (stripos($msg, 'rate_limit') !== false) {
            $msg = 'Demasiadas solicitudes. Espera unos segundos e intenta de nuevo.';
        }
        $data = array(
            'status' => 400,
            'message' => $msg,
            'chat_id' => $chatId,
        );
    }
} elseif ($s == 'rate') {
    $messageId = !empty($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
    $rating = isset($_POST['rating']) ? Wo_Secure($_POST['rating']) : '';
    $comment = isset($_POST['comment']) ? trim((string) $_POST['comment']) : '';
    if (!in_array($rating, array('up', 'down', ''), true)) {
        $rating = '';
    }
    global $sqlConnect;
    $mq = mysqli_query($sqlConnect, "SELECT * FROM " . T_ZUITCH_AI_MESSAGES . " WHERE `id` = {$messageId} AND `role` = 'assistant' LIMIT 1");
    $msg = ($mq && ($row = mysqli_fetch_assoc($mq))) ? $row : null;
    if (!$msg) {
        $data['message'] = 'not_found';
    } else {
        // user may rate own chat messages; superadmin can also from follow-up
        $chat = zuitch_ai_get_chat((int) $msg['chat_id'], 0);
        if (!$chat || ((int) $chat['user_id'] !== $userId && !zuitch_ai_is_superadmin())) {
            $data['message'] = 'forbidden';
        } else {
            $commentEsc = mysqli_real_escape_string($sqlConnect, mb_substr($comment, 0, 2000));
            $ratingEsc = mysqli_real_escape_string($sqlConnect, $rating);
            $t = time();
            $exists = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ZUITCH_AI_RATINGS . " WHERE `message_id` = {$messageId} LIMIT 1");
            if ($exists && mysqli_num_rows($exists)) {
                mysqli_query($sqlConnect, "UPDATE " . T_ZUITCH_AI_RATINGS
                    . " SET `rating` = '{$ratingEsc}', `comment` = '{$commentEsc}', `time` = {$t} WHERE `message_id` = {$messageId}");
            } else {
                mysqli_query($sqlConnect, "INSERT INTO " . T_ZUITCH_AI_RATINGS
                    . " (`message_id`,`user_id`,`rating`,`comment`,`admin_ok`,`admin_correction`,`time`) VALUES "
                    . "({$messageId},{$userId},'{$ratingEsc}','{$commentEsc}',0,NULL,{$t})");
            }
            $data = array('status' => 200);
        }
    }
} elseif ($s == 'admin_correct') {
    if (!zuitch_ai_is_superadmin()) {
        $data['message'] = 'forbidden';
    } else {
        $messageId = !empty($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
        $adminOk = !empty($_POST['admin_ok']) ? 1 : 0;
        $correction = isset($_POST['admin_correction']) ? trim((string) $_POST['admin_correction']) : '';
        global $sqlConnect;
        $mq = mysqli_query($sqlConnect, "SELECT * FROM " . T_ZUITCH_AI_MESSAGES . " WHERE `id` = {$messageId} AND `role` = 'assistant' LIMIT 1");
        if (!$mq || !mysqli_num_rows($mq)) {
            $data['message'] = 'not_found';
        } else {
            $corrEsc = mysqli_real_escape_string($sqlConnect, $correction);
            $t = time();
            $exists = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ZUITCH_AI_RATINGS . " WHERE `message_id` = {$messageId} LIMIT 1");
            if ($exists && mysqli_num_rows($exists)) {
                mysqli_query($sqlConnect, "UPDATE " . T_ZUITCH_AI_RATINGS
                    . " SET `admin_ok` = {$adminOk}, `admin_correction` = '{$corrEsc}', `time` = {$t} WHERE `message_id` = {$messageId}");
            } else {
                $owner = (int) $userId;
                mysqli_query($sqlConnect, "INSERT INTO " . T_ZUITCH_AI_RATINGS
                    . " (`message_id`,`user_id`,`rating`,`comment`,`admin_ok`,`admin_correction`,`time`) VALUES "
                    . "({$messageId},{$owner},'','',{$adminOk},'{$corrEsc}',{$t})");
            }
            $data = array('status' => 200);
        }
    }
} elseif ($s == 'admin_list') {
    if (!zuitch_ai_is_superadmin()) {
        $data['message'] = 'forbidden';
    } else {
        $q = isset($_POST['q']) ? trim((string) $_POST['q']) : '';
        $data = array(
            'status' => 200,
            'chats' => zuitch_ai_admin_list_chats($q, 50, 0),
        );
    }
} elseif ($s == 'tts') {
    if (!zuitch_ai_enabled()) {
        $data['message'] = 'disabled';
    } elseif ((string) zuitch_ai_cfg('zuitch_ai_tts', '1') !== '1') {
        $data['message'] = 'TTS desactivado en admin-cp → Zuitch AI.';
    } else {
        $text = isset($_POST['text']) ? trim((string) $_POST['text']) : '';
        $lang = isset($_POST['lang']) ? trim((string) $_POST['lang']) : 'es';
        if ($text === '') {
            $data['message'] = 'Sin texto.';
        } else {
            try {
                $url = zuitch_ai_tts($text, $lang);
                $data = array(
                    'status' => 200,
                    'audio_url' => $url,
                );
            } catch (Exception $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'Incorrect API key') !== false || stripos($msg, 'invalid_api_key') !== false) {
                    $msg = 'La API key de OpenAI no es válida. Revísala en admin-cp → Zuitch AI.';
                } elseif (stripos($msg, 'insufficient_quota') !== false || stripos($msg, 'billing') !== false) {
                    $msg = 'Sin crédito en OpenAI para TTS.';
                }
                $data = array('status' => 400, 'message' => $msg);
            }
        }
    }
} else {
    $data['message'] = 'unknown_action';
}

header('Content-type: application/json');
echo json_encode($data);
exit();
