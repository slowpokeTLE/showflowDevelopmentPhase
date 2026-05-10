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
    if ($action !== 'send_complaint_message') {
        throw new Exception('Invalid action.');
    }

    $u_id = $_POST['u_id'] ?? null;
    $complaint_id = $_POST['complaint_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    $m_id = $_SESSION['m_id'];

    if (empty($u_id) || empty($complaint_id) || empty($message)) {
        throw new Exception('User ID, complaint ID, and message are required.');
    }

    if (strlen($message) < 5) {
        throw new Exception('Message must be at least 5 characters.');
    }

    // Verify complaint exists and belongs to this user
    $verify_query = "SELECT comp_id FROM complaint WHERE comp_id = ? AND u_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("is", $complaint_id, $u_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Complaint not found or does not belong to this user.');
    }
    $stmt->close();

    // Insert notification for user
    $notification_message = "Manager replied to your complaint #" . str_pad($complaint_id, 5, '0', STR_PAD_LEFT) . ": " . $message;
    $insert_notification = "INSERT INTO user_notification (u_id, message, notif_type, is_read) VALUES (?, ?, 'complaint_reply', 0)";
    $stmt = $conn->prepare($insert_notification);
    $stmt->bind_param("ss", $u_id, $notification_message);

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    $stmt->close();

    // Update complaint with manager notes if the column exists
    $check_column = "SHOW COLUMNS FROM complaint LIKE 'manager_notes'";
    $result = $conn->query($check_column);
    if ($result->num_rows > 0) {
        $update_notes = "UPDATE complaint SET manager_notes = CONCAT(COALESCE(manager_notes, ''), '\n[Manager] ', NOW(), ': ', ?) WHERE comp_id = ?";
        $stmt = $conn->prepare($update_notes);
        $stmt->bind_param("si", $message, $complaint_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent to user successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
