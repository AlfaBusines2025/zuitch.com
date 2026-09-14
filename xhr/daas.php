<?php
if ($f == 'daas') {
    header('Content-Type: application/json; charset=utf-8');

    if (!Wo_IsAdmin()) {
        echo json_encode(array('status' => 403, 'message' => 'Admins only'));
        exit();
    }

    if ($s == 'pull') {
        if (Wo_CheckMainSession($hash_id) !== true) {
            echo json_encode(array('status' => 403, 'message' => 'Invalid session hash'));
            exit();
        }

        require_once './assets/includes/daas_env.php';
        require_once './assets/includes/Daas/GitSyncService.php';

        $service = new GitSyncService(dirname(__DIR__));
        $result = $service->pull(null);

        if (!empty($result['ok'])) {
            echo json_encode(array(
                'status' => 200,
                'message' => $result['message'],
                'sha' => isset($result['sha']) ? $result['sha'] : '',
            ));
        } else {
            echo json_encode(array(
                'status' => 422,
                'message' => isset($result['message']) ? $result['message'] : 'Pull failed',
            ));
        }
        exit();
    }

    echo json_encode(array('status' => 400, 'message' => 'Unknown action'));
    exit();
}
