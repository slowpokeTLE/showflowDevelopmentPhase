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
    echo json_encode(['status' => 'error', 'message' => 'Movie ID required', 'contract' => null]);
    exit();
}

// Fetch contract if exists
$query = "SELECT * FROM contract WHERE t_id = ? AND mov_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $t_id, $mov_id);
$stmt->execute();
$result = $stmt->get_result();
$contract = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'contract' => $contract
]);
?>
