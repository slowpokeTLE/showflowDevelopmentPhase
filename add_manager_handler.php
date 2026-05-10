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
    $manager_id = trim($_POST['manager_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $t_id = intval($_POST['t_id'] ?? 0);
    
    if (empty($manager_id) || empty($name) || empty($contact) || empty($password) || $t_id <= 0) {
        jsonResponse('error', 'All fields are required');
    }
    
    // Check if manager ID already exists
    $check_query = "SELECT m_id FROM manager WHERE m_id = ? OR contact = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $manager_id, $contact);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse('error', 'Manager ID or Contact already exists');
    }
    $stmt->close();
    
    // Verify theatre exists
    $theatre_check = "SELECT t_id FROM theatre WHERE t_id = ?";
    $stmt = $conn->prepare($theatre_check);
    $stmt->bind_param("i", $t_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows == 0) {
        jsonResponse('error', 'Theatre not found');
    }
    $stmt->close();
    
    // Insert new manager
    $insert_query = "INSERT INTO manager (m_id, name, contact, password, t_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssi", $manager_id, $name, $contact, $password, $t_id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Manager added successfully', ['m_id' => $manager_id]);
    } else {
        jsonResponse('error', 'Failed to add manager: ' . $conn->error);
    }
    $stmt->close();
}
?>
