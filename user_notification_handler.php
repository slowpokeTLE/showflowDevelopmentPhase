<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Check user access
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_notifications') {
    if (hasRole(ROLE_USER)) {
        $u_id = $_SESSION['u_id'];
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $query = "SELECT * FROM user_notification WHERE u_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $u_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        
        // Get unread count
        $count_query = "SELECT COUNT(*) as unread_count FROM user_notification WHERE u_id = ? AND is_read = FALSE";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param("s", $u_id);
        $stmt->execute();
        $count_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'notifications' => $notifications,
            'unread_count' => $count_result['unread_count'] ?? 0
        ]);
    } elseif (hasRole(ROLE_MANAGER)) {
        // Managers can get complaints
        $m_id = $_SESSION['m_id'];
        $t_id = $_SESSION['t_id'];
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $query = "SELECT c.comp_id, c.u_id, c.complaint_text, c.comp_date, c.created_at, u.name as user_name 
                  FROM complaint c 
                  JOIN user u ON c.u_id = u.u_id 
                  WHERE c.t_id = ? 
                  ORDER BY c.created_at DESC 
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $t_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $complaints = [];
        while ($row = $result->fetch_assoc()) {
            $complaints[] = $row;
        }
        $stmt->close();
        
        // Get new complaints count (created in last 7 days)
        $count_query = "SELECT COUNT(*) as new_count FROM complaint WHERE t_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param("i", $t_id);
        $stmt->execute();
        $count_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'complaints' => $complaints,
            'new_count' => $count_result['new_count'] ?? 0
        ]);
    }
    
} elseif ($action === 'mark_as_read') {
    if (hasRole(ROLE_USER)) {
        $u_id = $_SESSION['u_id'];
        $notif_id = $_POST['notif_id'] ?? null;
        
        if (!$notif_id) {
            echo json_encode(['status' => 'error', 'message' => 'Notification ID required']);
            exit();
        }
        
        $query = "UPDATE user_notification SET is_read = TRUE WHERE notif_id = ? AND u_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $notif_id, $u_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['status' => 'success', 'message' => 'Marked as read']);
    }
    
} elseif ($action === 'delete_notification') {
    if (hasRole(ROLE_USER)) {
        $u_id = $_SESSION['u_id'];
        $notif_id = $_POST['notif_id'] ?? null;
        
        if (!$notif_id) {
            echo json_encode(['status' => 'error', 'message' => 'Notification ID required']);
            exit();
        }
        
        $query = "DELETE FROM user_notification WHERE notif_id = ? AND u_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $notif_id, $u_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['status' => 'success', 'message' => 'Notification deleted']);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
