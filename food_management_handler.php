<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

if (!hasRole(ROLE_MANAGER)) {
    jsonResponse('error', 'Unauthorized access');
}

$t_id = $_SESSION['t_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ADD FOOD ITEM
    if ($action === 'add_food') {
        $food_name = trim($_POST['food_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        
        if (empty($food_name) || $price <= 0) {
            jsonResponse('error', 'Food name and valid price required');
        }
        
        $insert_query = "INSERT INTO food_item (t_id, food_name, price) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isd", $t_id, $food_name, $price);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Food item added successfully', ['food_id' => $conn->insert_id]);
        } else {
            jsonResponse('error', 'Failed to add food item');
        }
        $stmt->close();
    }
    
    // DELETE FOOD ITEM
    else if ($action === 'delete_food') {
        $food_id = intval($_POST['food_id'] ?? 0);
        
        if ($food_id <= 0) {
            jsonResponse('error', 'Invalid food ID');
        }
        
        // Verify food belongs to this theatre
        $verify_query = "SELECT food_id FROM food_item WHERE food_id = ? AND t_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $food_id, $t_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            jsonResponse('error', 'Food item not found');
        }
        $stmt->close();
        
        $delete_query = "DELETE FROM food_item WHERE food_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $food_id);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Food item deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete food item');
        }
        $stmt->close();
    }
}
?>
