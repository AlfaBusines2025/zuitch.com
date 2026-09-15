<?php
if ($f == 'daas') {
    header('Content-Type: application/json; charset=utf-8');

    if (!Wo_IsAdmin()) {
        echo json_encode(array('status' => 403, 'message' => 'Admins only'));
        exit();
    }

    require_once './assets/includes/daas_env.php';
    require_once './assets/includes/Daas/GitSyncService.php';
    require_once './assets/includes/Daas/DaasClient.php';

    if ($s == 'pull') {
        if (Wo_CheckMainSession($hash_id) !== true) {
            echo json_encode(array('status' => 403, 'message' => 'Invalid session hash'));
            exit();
        }

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

    if ($s == 'list_orders') {
        if (Wo_CheckMainSession($hash_id) !== true) {
            echo json_encode(array('status' => 403, 'message' => 'Invalid session hash'));
            exit();
        }

        $client = new DaasClient();
        $res = $client->listOrders(array('per_page' => 50));
        if (!empty($res['ok'])) {
            echo json_encode(array(
                'status' => 200,
                'orders' => isset($res['data']) ? $res['data'] : array(),
                'meta' => isset($res['meta']) ? $res['meta'] : array(),
            ));
        } else {
            echo json_encode(array(
                'status' => 422,
                'message' => isset($res['message']) ? $res['message'] : 'Failed to list orders',
            ));
        }
        exit();
    }

    if ($s == 'cancel_order') {
        if (Wo_CheckMainSession($hash_id) !== true) {
            echo json_encode(array('status' => 403, 'message' => 'Invalid session hash'));
            exit();
        }

        $orderId = 0;
        if (!empty($_POST['order_id'])) {
            $orderId = (int) $_POST['order_id'];
        } elseif (!empty($_GET['order_id'])) {
            $orderId = (int) $_GET['order_id'];
        }
        if ($orderId <= 0) {
            echo json_encode(array('status' => 400, 'message' => 'order_id required'));
            exit();
        }

        $client = new DaasClient();
        $res = $client->cancelOrder($orderId);
        if (!empty($res['ok'])) {
            echo json_encode(array(
                'status' => 200,
                'message' => 'Order cancelled',
                'data' => isset($res['data']) ? $res['data'] : null,
            ));
        } else {
            echo json_encode(array(
                'status' => isset($res['status']) && $res['status'] ? (int) $res['status'] : 422,
                'message' => isset($res['message']) ? $res['message'] : 'Cancel failed',
            ));
        }
        exit();
    }

    echo json_encode(array('status' => 400, 'message' => 'Unknown action'));
    exit();
}
