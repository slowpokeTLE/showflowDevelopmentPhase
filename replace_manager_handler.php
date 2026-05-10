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
    $t_id = intval($_POST['t_id'] ?? 0);
    $new_manager_id = trim($_POST['new_manager_id'] ?? '');
    $new_name = trim($_POST['new_name'] ?? '');
    $new_contact = trim($_POST['new_contact'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    
    if ($t_id <= 0 || empty($new_manager_id) || empty($new_name) || empty($new_contact) || empty($new_password)) {
        jsonResponse('error', 'All fields are required');
    }
    
    // Check if theatre exists
    $theatre_check = "SELECT t_id FROM theatre WHERE t_id = ?";
    $stmt = $conn->prepare($theatre_check);
    $stmt->bind_param("i", $t_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows == 0) {
        jsonResponse('error', 'Theatre not found');
    }
    $stmt->close();
    
    // Get current manager
    $current_check = "SELECT m_id FROM manager WHERE t_id = ?";
    $stmt = $conn->prepare($current_check);
    $stmt->bind_param("i", $t_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        jsonResponse('error', 'No manager found for this theatre');
    }
    
    $current = $result->fetch_assoc();
    $old_m_id = $current['m_id'];
    $stmt->close();
    
    // Check if new manager ID conflicts with existing one
    if ($new_manager_id != $old_m_id) {
        $conflict_check = "SELECT m_id FROM manager WHERE m_id = ?";
        $stmt = $conn->prepare($conflict_check);
        $stmt->bind_param("s", $new_manager_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            jsonResponse('error', 'New Manager ID already exists');
        }
        $stmt->close();
    }
    
    // Delete old manager and insert new one
    $conn->begin_transaction();
    
    try {
        // Delete old manager
        $delete_query = "DELETE FROM manager WHERE m_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $old_m_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete old manager');
        }
        $stmt->close();
        
        // Insert new manager
        $insert_query = "INSERT INTO manager (m_id, name, contact, password, t_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssssi", $new_manager_id, $new_name, $new_contact, $new_password, $t_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert new manager');
        }
        $stmt->close();
        
        $conn->commit();
        jsonResponse('success', 'Manager replaced successfully', ['m_id' => $new_manager_id]);
        
    } catch (Exception $e) {
        $conn->rollback();
        jsonResponse('error', 'Transaction failed: ' . $e->getMessage());
    }
}
?>
