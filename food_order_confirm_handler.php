<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

if (!hasRole(ROLE_MANAGER)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$manager_t_id = intval($_SESSION['t_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm_food_order') {
        $order_id = intval($_POST['order_id'] ?? 0);

        if ($order_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID']);
            exit();
        }

        // Fetch the order and verify it belongs to this manager's theatre
        $query = "SELECT order_id, t_id, status FROM food_order WHERE order_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            echo json_encode(['status' => 'error', 'message' => 'Order ID ' . $order_id . ' not found']);
            exit();
        }

        $order = $result->fetch_assoc();
        $stmt->close();

        // Security check: order's theatre must match manager's theatre
        if (intval($order['t_id']) !== $manager_t_id) {
            echo json_encode(['status' => 'error', 'message' => 'This order does not belong to your theatre']);
            exit();
        }

        // Check it's still pending
        if ($order['status'] !== 'Pending') {
            echo json_encode(['status' => 'error', 'message' => 'Order is already ' . $order['status']]);
            exit();
        }

        // Mark all rows with this order_id as Delivered
        // (multiple rows share the same order_date batch — update by order_id)
        $update = "UPDATE food_order SET status = 'Delivered' WHERE order_id = ? AND t_id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("ii", $order_id, $manager_t_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $padded = 'ORDER-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            echo json_encode([
                'status'  => 'success',
                'message' => $padded . ' marked as Delivered'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not update order. Please try again.']);
        }

        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>
