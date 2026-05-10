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

$t_id = $_SESSION['t_id'];
$action = $_POST['action'] ?? null;

if ($action === 'save_contract') {
    $mov_id = $_POST['mov_id'] ?? null;
    $one_time_cost = $_POST['one_time_cost'] ?? null;
    $percentage_per_ticket = $_POST['percentage_per_ticket'] ?? null;

    if (!$mov_id || $one_time_cost === null || $percentage_per_ticket === null) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit();
    }

    // Validate inputs
    if (!is_numeric($one_time_cost) || !is_numeric($percentage_per_ticket)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid numeric values']);
        exit();
    }

    if ($percentage_per_ticket < 0 || $percentage_per_ticket > 100) {
        echo json_encode(['status' => 'error', 'message' => 'Percentage must be between 0 and 100']);
        exit();
    }

    // Check if contract already exists
    $check_query = "SELECT contract_id FROM contract WHERE t_id = ? AND mov_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $t_id, $mov_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Update existing contract
        $update_query = "UPDATE contract SET one_time_cost = ?, percentage_per_ticket = ? WHERE t_id = ? AND mov_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ddii", $one_time_cost, $percentage_per_ticket, $t_id, $mov_id);
    } else {
        // Insert new contract
        $insert_query = "INSERT INTO contract (t_id, mov_id, one_time_cost, percentage_per_ticket) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iidd", $t_id, $mov_id, $one_time_cost, $percentage_per_ticket);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Contract saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
