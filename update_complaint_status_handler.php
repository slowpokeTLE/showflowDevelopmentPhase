<?php
require 'db.php';
require 'session_handler.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid Request Method. POST required.');
    }

    if (!isset($_SESSION['m_id'])) {
        throw new Exception('Manager not logged in.');
    }

    $action = $_POST['action'] ?? null;
    if ($action !== 'update_complaint_status') {
        throw new Exception('Invalid action.');
    }

    $complaint_id = $_POST['complaint_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (empty($complaint_id) || empty($status)) {
        throw new Exception('Complaint ID and status are required.');
    }

    $valid_statuses = ['Not Seen', 'Seen', 'Working', 'Resolved'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status value.');
    }

    // Check if status column exists, if not, add it
    $check_column = "SHOW COLUMNS FROM complaint LIKE 'status'";
    $result = $conn->query($check_column);
    if ($result->num_rows == 0) {
        $alter_table = "ALTER TABLE complaint ADD COLUMN status VARCHAR(20) DEFAULT 'Not Seen'";
        if (!$conn->query($alter_table)) {
            throw new Exception('Could not add status column: ' . $conn->error);
        }
    }

    $update_query = "UPDATE complaint SET status = ?, last_updated = NOW() WHERE comp_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $complaint_id);

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Complaint status updated to: ' . $status
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
