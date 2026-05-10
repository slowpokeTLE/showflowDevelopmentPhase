<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Check developer access
if (!hasRole(ROLE_DEVELOPER)) {
    jsonResponse('error', 'Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $theatre_name = trim($_POST['theatre_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    if (empty($theatre_name) || empty($location)) {
        jsonResponse('error', 'All fields are required');
    }
    
    // Check if theatre name already exists
    $check_query = "SELECT t_id FROM theatre WHERE theatre_name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $theatre_name);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse('error', 'Theatre name already exists');
    }
    $stmt->close();
    
    // Insert new theatre
    $insert_query = "INSERT INTO theatre (theatre_name, location) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ss", $theatre_name, $location);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        jsonResponse('success', 'Theatre added successfully', ['t_id' => $new_id]);
    } else {
        jsonResponse('error', 'Failed to add theatre: ' . $conn->error);
    }
    $stmt->close();
}
?>
