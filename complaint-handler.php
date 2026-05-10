<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

if (!hasRole(ROLE_USER)) {
    jsonResponse('error', 'Unauthorized access');
}

$u_id = $_SESSION['u_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_complaint') {
        $t_id = intval($_POST['theatre_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($t_id <= 0 || empty($message)) {
            jsonResponse('error', 'All fields are required');
        }

        // Verify theatre exists
        $verify_query = "SELECT t_id FROM theatre WHERE t_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("i", $t_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            jsonResponse('error', 'Invalid theatre');
        }
        $stmt->close();

        // Insert complaint
        $comp_date = date('Y-m-d');
        $created_at = date('Y-m-d H:i:s');

        $insert_query = "INSERT INTO complaint (u_id, t_id, comp_date, complaint_text, created_at) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sisss", $u_id, $t_id, $comp_date, $message, $created_at);

        if ($stmt->execute()) {
            $complaint_id = $conn->insert_id;
            $stmt->close();
            jsonResponse('success', 'Complaint submitted successfully', ['complaint_id' => $complaint_id]);
        } else {
            $stmt->close();
            jsonResponse('error', 'Failed to submit complaint');
        }
    }
}
?>
