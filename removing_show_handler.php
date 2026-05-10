<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Check manager access
if (!hasRole(ROLE_MANAGER)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'delete_show') {
    $s_id = $_POST['s_id'] ?? null;
    
    if (!$s_id) {
        echo json_encode(['status' => 'error', 'message' => 'Show ID is required']);
        exit();
    }
    
    // Get show details first
    $show_query = "SELECT * FROM show_schedule WHERE s_id = ?";
    $stmt = $conn->prepare($show_query);
    $stmt->bind_param("i", $s_id);
    $stmt->execute();
    $show = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$show) {
        echo json_encode(['status' => 'error', 'message' => 'Show not found']);
        exit();
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // 1. Get all bookings for this show
        $booking_query = "SELECT u_id, total_amount FROM booking WHERE s_id = ?";
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param("i", $s_id);
        $stmt->execute();
        $bookings_result = $stmt->get_result();
        $bookings = [];
        while ($row = $bookings_result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();
        
        $refunded_users = [];
        $total_refunded = 0;
        
        // 2. Refund each user
        foreach ($bookings as $booking) {
            $u_id = $booking['u_id'];
            $refund_amount = $booking['total_amount'];
            
            // Update user balance
            $update_query = "UPDATE balance SET current_balance = current_balance + ? WHERE u_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("di", $refund_amount, $u_id);
            $stmt->execute();
            $stmt->close();
            
            // Create notification for user
            $notif_message = "Your booking for show ID #" . $s_id . " has been cancelled. ₹" . number_format($refund_amount, 2) . " has been refunded to your wallet.";
            $notif_type = 'show_deleted'; // Define it outside
            $notif_query = "INSERT INTO user_notification (u_id, m_id, message, notif_type, created_at) VALUES (?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($notif_query);
            // Changed "sss" to "ssss" to match the 4 variables provided
            $stmt->bind_param("ssss", $u_id, $_SESSION['m_id'], $notif_message, $notif_type); 
            $stmt->execute();
            $stmt->close();
            
            $refunded_users[] = $u_id;
            $total_refunded += $refund_amount;
        }
        
        // 3. Delete all bookings for this show
        $delete_booking_query = "DELETE FROM booking WHERE s_id = ?";
        $stmt = $conn->prepare($delete_booking_query);
        $stmt->bind_param("i", $s_id);
        $stmt->execute();
        $stmt->close();
        
        // 4. Delete the show from show_schedule
        $delete_show_query = "DELETE FROM show_schedule WHERE s_id = ?";
        $stmt = $conn->prepare($delete_show_query);
        $stmt->bind_param("i", $s_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Show deleted successfully',
            'users_refunded' => count($refunded_users),
            'total_refunded' => number_format($total_refunded, 2)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'edit_show') {
    $s_id = $_POST['s_id'] ?? null;
    $new_price = $_POST['ticket_price'] ?? null;
    $new_time = $_POST['show_time'] ?? null;
    $new_h_id = $_POST['h_id'] ?? null;
    
    if (!$s_id) {
        echo json_encode(['status' => 'error', 'message' => 'Show ID is required']);
        exit();
    }
    
    // Get current show details
    $show_query = "SELECT * FROM show_schedule WHERE s_id = ?";
    $stmt = $conn->prepare($show_query);
    $stmt->bind_param("i", $s_id);
    $stmt->execute();
    $show = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$show) {
        echo json_encode(['status' => 'error', 'message' => 'Show not found']);
        exit();
    }
    
    try {
        $conn->begin_transaction();
        
        // Get affected users (those who have booked this show)
        $booking_query = "SELECT DISTINCT u_id FROM booking WHERE s_id = ?";
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param("i", $s_id);
        $stmt->execute();
        $bookings_result = $stmt->get_result();
        $affected_users = [];
        while ($row = $bookings_result->fetch_assoc()) {
            $affected_users[] = $row['u_id'];
        }
        $stmt->close();
        
        // Update show details
        $update_fields = [];
        $params = [];
        $types = '';
        
        if ($new_price !== null && $new_price !== $show['ticket_price']) {
            $update_fields[] = "ticket_price = ?";
            $params[] = $new_price;
            $types .= 'd';
        }
        if ($new_time !== null && $new_time !== $show['show_time']) {
            $update_fields[] = "show_time = ?";
            $params[] = $new_time;
            $types .= 's';
        }
        if ($new_h_id !== null && $new_h_id !== $show['h_id']) {
            $update_fields[] = "h_id = ?";
            $params[] = $new_h_id;
            $types .= 'i';
        }
        
        if (empty($update_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'No changes detected']);
            exit();
        }
        
        $update_query = "UPDATE show_schedule SET " . implode(", ", $update_fields) . " WHERE s_id = ?";
        $params[] = $s_id;
        $types .= 'i';
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        
        // Create notifications for affected users
        $changes = [];
        if ($new_price !== null && $new_price !== $show['ticket_price']) {
            $changes[] = "Ticket price changed from ₹" . number_format($show['ticket_price'], 2) . " to ₹" . number_format($new_price, 2);
        }
        if ($new_time !== null && $new_time !== $show['show_time']) {
            $changes[] = "Show time changed from " . date('h:i A', strtotime($show['show_time'])) . " to " . date('h:i A', strtotime($new_time));
        }
        if ($new_h_id !== null && $new_h_id !== $show['h_id']) {
            $changes[] = "Show hall has been changed";
        }
        
        $notif_message = "Your booking for show #" . $s_id . " has been updated:\n" . implode("\n", $changes);
        $notif_type = 'show_changed';
        
        foreach ($affected_users as $u_id) {
            $notif_query = "INSERT INTO user_notification (u_id, m_id, message, notif_type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($notif_query);
            $stmt->bind_param("ssss", $u_id, $_SESSION['m_id'], $notif_message, $notif_type);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Show updated successfully',
            'users_notified' => count($affected_users)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
