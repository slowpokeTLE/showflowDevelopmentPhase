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
    if ($action !== 'edit_manager_profile') {
        throw new Exception('Invalid action.');
    }

    $m_id = $_SESSION['m_id'];
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($contact)) {
        throw new Exception('Name and contact are required.');
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_query = "UPDATE manager SET manager_name = ?, contact = ?, password = ? WHERE m_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $name, $contact, $hashed_password, $m_id);
    } else {
        $update_query = "UPDATE manager SET manager_name = ?, contact = ? WHERE m_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $name, $contact, $m_id);
    }

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Manager profile updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
