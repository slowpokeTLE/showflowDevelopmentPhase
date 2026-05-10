<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Check manager access
if (!hasRole(ROLE_MANAGER)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$t_id = $_GET['t_id'] ?? $_SESSION['t_id'];
$mov_id = $_GET['mov_id'] ?? null;

if (!$mov_id) {
    echo json_encode(['status' => 'error', 'message' => 'Movie ID required']);
    exit();
}

// Get total earnings from bookings for this movie in this theatre
$booking_query = "
    SELECT 
        COALESCE(COUNT(DISTINCT b.book_id), 0) as tickets_sold,
        COALESCE(SUM(b.total_amount), 0) as total_amount
    FROM booking b
    JOIN show_schedule ss ON b.s_id = ss.s_id
    WHERE ss.mov_id = ? AND ss.t_id = ?
";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("ii", $mov_id, $t_id);
$stmt->execute();
$booking_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get contract details
$contract_query = "SELECT one_time_cost, percentage_per_ticket FROM contract WHERE t_id = ? AND mov_id = ?";
$stmt = $conn->prepare($contract_query);
$stmt->bind_param("ii", $t_id, $mov_id);
$stmt->execute();
$contract_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$response = [
    'status' => 'success',
    'earnings' => [
        'tickets_sold' => $booking_result['tickets_sold'] ?? 0,
        'total_amount' => $booking_result['total_amount'] ?? 0,
        'one_time_cost' => $contract_result['one_time_cost'] ?? 0,
        'percentage_per_ticket' => $contract_result['percentage_per_ticket'] ?? 0
    ]
];

echo json_encode($response);
?>
