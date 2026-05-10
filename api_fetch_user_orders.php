<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$u_id = $_SESSION['u_id'];
$t_id = $_GET['t_id'] ?? null;

if (!$t_id) {
    echo json_encode(['status' => 'error', 'message' => 'Theatre ID required', 'orders' => []]);
    exit();
}

// Fetch recent orders for user at this theatre
$query = "
    SELECT 
        fo.order_id,
        fo.total_price,
        fo.order_date,
        fo.status as order_status
    FROM food_order fo
    WHERE fo.u_id = ? AND fo.t_id = ?
    ORDER BY fo.order_date DESC
    LIMIT 10
";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $u_id, $t_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'orders' => $orders
]);
?>
