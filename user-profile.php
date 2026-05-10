<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';
require 'wallet-utils.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];

// Get user profile
$query = "SELECT u_id, name, contact, created_at FROM user WHERE u_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Handle cancel booking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = $_POST['booking_id'] ?? '';
    
    if (!empty($booking_id)) {
        try {
            $conn->begin_transaction();

            // Get booking details for refund
            $get_booking = "SELECT total_amount, paid_from_wallet FROM booking WHERE book_id = ? AND u_id = ?";
            $stmt = $conn->prepare($get_booking);
            $stmt->bind_param("is", $booking_id, $u_id);
            $stmt->execute();
            $booking_result = $stmt->get_result();
            
            if ($booking_result->num_rows > 0) {
                $booking = $booking_result->fetch_assoc();
                $amount = $booking['total_amount'];
                $paid_from_wallet = $booking['paid_from_wallet'];
                
                // Delete booking
                $delete_booking = "DELETE FROM booking WHERE book_id = ? AND u_id = ?";
                $stmt = $conn->prepare($delete_booking);
                $stmt->bind_param("is", $booking_id, $u_id);
                $stmt->execute();
                $stmt->close();
                
                // Refund to wallet if it was paid from wallet
                if ($paid_from_wallet) {
                    // Get current balance before refund
                    $get_balance = "SELECT current_balance FROM balance WHERE u_id = ?";
                    $stmt = $conn->prepare($get_balance);
                    $stmt->bind_param("s", $u_id);
                    $stmt->execute();
                    $balance_result = $stmt->get_result()->fetch_assoc();
                    $balance_before = $balance_result['current_balance'] ?? 0;
                    $stmt->close();
                    
                    // Add refund back to wallet
                    $refund_query = "UPDATE balance SET current_balance = current_balance + ? WHERE u_id = ?";
                    $stmt = $conn->prepare($refund_query);
                    $stmt->bind_param("ds", $amount, $u_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log refund transaction
                    $booking_id_str = (string)$booking_id;
                    $log_query = "INSERT INTO wallet_transaction_log (u_id, transaction_type, reference_id, amount, operation, balance_before, balance_after, status) 
                                 VALUES (?, 'booking_cancellation', ?, ?, 'credit', ?, ?, 'Success')";
                    $stmt = $conn->prepare($log_query);
                    $balance_after = $balance_before + $amount;
                    $stmt->bind_param("ssddd", $u_id, $booking_id_str, $amount, $balance_before, $balance_after);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $conn->commit();
                $_SESSION['success_message'] = 'Booking cancelled successfully! ' . ($paid_from_wallet ? 'Refund of ৳ ' . number_format($amount, 2) . ' has been added to your wallet.' : '');
            } else {
                $conn->rollback();
                $_SESSION['error_message'] = 'Booking not found.';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = 'Failed to cancel booking: ' . $e->getMessage();
        }
        
        header('Location: user-profile.php');
        exit();
    }
}

// Handle delete review request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    $review_id = $_POST['review_id'] ?? '';
    
    if (!empty($review_id)) {
        $delete_review = "DELETE FROM review WHERE rev_id = ? AND u_id = ?";
        $stmt = $conn->prepare($delete_review);
        $stmt->bind_param("is", $review_id, $u_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success_message'] = 'Review deleted successfully!';
        header('Location: user-profile.php');
        exit();
    }
}

// Handle cancel food order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_food_order') {
    $order_id = $_POST['order_id'] ?? '';
    
    if (!empty($order_id)) {
        try {
            $conn->begin_transaction();

            // Get order details for refund
            $get_order = "SELECT total_price, paid_from_wallet FROM food_order WHERE order_id = ? AND u_id = ?";
            $stmt = $conn->prepare($get_order);
            $stmt->bind_param("is", $order_id, $u_id);
            $stmt->execute();
            $order_result = $stmt->get_result();
            
            if ($order_result->num_rows > 0) {
                $order = $order_result->fetch_assoc();
                $amount = $order['total_price'];
                $paid_from_wallet = $order['paid_from_wallet'];
                
                // Delete order
                $delete_order = "DELETE FROM food_order WHERE order_id = ? AND u_id = ?";
                $stmt = $conn->prepare($delete_order);
                $stmt->bind_param("is", $order_id, $u_id);
                $stmt->execute();
                $stmt->close();
                
                // Refund to wallet if it was paid from wallet
                if ($paid_from_wallet) {
                    // Get current balance before refund
                    $get_balance = "SELECT current_balance FROM balance WHERE u_id = ?";
                    $stmt = $conn->prepare($get_balance);
                    $stmt->bind_param("s", $u_id);
                    $stmt->execute();
                    $balance_result = $stmt->get_result()->fetch_assoc();
                    $balance_before = $balance_result['current_balance'] ?? 0;
                    $stmt->close();
                    
                    // Add refund back to wallet
                    $refund_query = "UPDATE balance SET current_balance = current_balance + ? WHERE u_id = ?";
                    $stmt = $conn->prepare($refund_query);
                    $stmt->bind_param("ds", $amount, $u_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log refund transaction
                    $order_id_str = (string)$order_id;
                    $log_query = "INSERT INTO wallet_transaction_log (u_id, transaction_type, reference_id, amount, operation, balance_before, balance_after, status) 
                                 VALUES (?, 'food_order_cancellation', ?, ?, 'credit', ?, ?, 'Success')";
                    $stmt = $conn->prepare($log_query);
                    $balance_after = $balance_before + $amount;
                    $stmt->bind_param("ssddd", $u_id, $order_id_str, $amount, $balance_before, $balance_after);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $conn->commit();
                $_SESSION['success_message'] = 'Food order cancelled successfully! ' . ($paid_from_wallet ? 'Refund of ৳ ' . number_format($amount, 2) . ' has been added to your wallet.' : '');
            } else {
                $conn->rollback();
                $_SESSION['error_message'] = 'Food order not found.';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = 'Failed to cancel food order: ' . $e->getMessage();
        }
        
        header('Location: user-profile.php');
        exit();
    }
}

// Handle edit profile request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    $new_name    = trim($_POST['name'] ?? '');
    $new_contact = trim($_POST['contact'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_name) || empty($new_contact)) {
        $_SESSION['error_message'] = 'Name and contact cannot be empty.';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match.';
    } else {
        try {
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = "UPDATE user SET name = ?, contact = ?, password = ? WHERE u_id = ?";
                $stmt = $conn->prepare($update);
                $stmt->bind_param("ssss", $new_name, $new_contact, $hashed, $u_id);
            } else {
                $update = "UPDATE user SET name = ?, contact = ? WHERE u_id = ?";
                $stmt = $conn->prepare($update);
                $stmt->bind_param("sss", $new_name, $new_contact, $u_id);
            }
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = 'Profile updated successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Failed to update profile: ' . $e->getMessage();
        }
    }

    header('Location: user-profile.php');
    exit();
}

// Get booking history (ALL bookings)
$booking_query = "
    SELECT b.book_id, b.booking_date, b.total_amount as total_price, m.mov_name, t.theatre_name, h.hall_name,
           s.show_date, s.show_time, b.seat_numbers
    FROM booking b
    JOIN show_schedule s ON b.s_id = s.s_id
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN theatre t ON s.t_id = t.t_id
    JOIN hall h ON s.h_id = h.h_id
    WHERE b.u_id = ?
    ORDER BY s.show_date DESC, s.show_time DESC
";

$stmt = $conn->prepare($booking_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$all_bookings = [];
$upcoming_bookings = [];
$past_bookings = [];
$today = date('Y-m-d H:i:s');

while ($row = $bookings_result->fetch_assoc()) {
    // Count seats from seat_numbers comma-separated list
    $row['seat_count'] = !empty($row['seat_numbers']) ? count(explode(',', $row['seat_numbers'])) : 0;
    
    $show_datetime = $row['show_date'] . ' ' . $row['show_time'];
    if ($show_datetime > $today) {
        $upcoming_bookings[] = $row;
    } else {
        $past_bookings[] = $row;
    }
    $all_bookings[] = $row;
}
$stmt->close();

// Get pending food orders
// NOTE: This requires running wallet_migration.sql first!
$pending_orders = [];

$pending_orders_query = "
    SELECT fo.order_id, fo.order_date, fo.total_price, fo.status, f.food_name, t.theatre_name
    FROM food_order fo
    JOIN food_item f ON fo.food_id = f.food_id
    JOIN theatre t ON fo.t_id = t.t_id
    WHERE fo.u_id = ? AND fo.status = 'Pending'
    ORDER BY fo.order_date DESC
";

$stmt = $conn->prepare($pending_orders_query);
if ($stmt) {
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $pending_orders_result = $stmt->get_result();
    $pending_orders = [];
    while ($row = $pending_orders_result->fetch_assoc()) {
        $pending_orders[] = $row;
    }
    $stmt->close();
}

// Get delivered food orders
$delivered_orders = [];

$delivered_orders_query = "
    SELECT fo.order_id, fo.order_date, fo.total_price, fo.status, f.food_name, t.theatre_name
    FROM food_order fo
    JOIN food_item f ON fo.food_id = f.food_id
    JOIN theatre t ON fo.t_id = t.t_id
    WHERE fo.u_id = ? AND fo.status = 'Delivered'
    ORDER BY fo.order_date DESC
";

$stmt = $conn->prepare($delivered_orders_query);
if ($stmt) {
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $delivered_result = $stmt->get_result();
    while ($row = $delivered_result->fetch_assoc()) {
        $delivered_orders[] = $row;
    }
    $stmt->close();
}

// Get user's wallet balance
$wallet_balance = getUserWalletBalance($u_id);

// Get user reviews
$reviews_query = "
    SELECT r.rev_id, r.mov_id, r.rating, r.comment, r.created_at, m.mov_name, m.mov_poster
    FROM review r
    JOIN movie m ON r.mov_id = m.mov_id
    WHERE r.u_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];
while ($row = $reviews_result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

// Get user messages / notifications
$messages_query = "
    SELECT notif_id, message, notif_type, is_read, created_at
    FROM user_notification
    WHERE u_id = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($messages_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

// Get user complaints
$complaints_query = "
    SELECT c.comp_id, c.u_id, c.complaint_text, c.created_at, COALESCE(c.status, 'Not Seen') as status, t.theatre_name
    FROM complaint c
    LEFT JOIN theatre t ON c.t_id = t.t_id
    WHERE c.u_id = ?
    ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($complaints_query);
if ($stmt) {
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $complaints_result = $stmt->get_result();
    $complaints = [];
    while ($row = $complaints_result->fetch_assoc()) {
        $complaints[] = $row;
    }
    $stmt->close();
} else {
    // Fallback if status column doesn't exist
    $complaints_query = "
        SELECT c.comp_id, c.u_id, c.complaint_text, c.created_at, 'Not Seen' as status, t.theatre_name
        FROM complaint c
        LEFT JOIN theatre t ON c.t_id = t.t_id
        WHERE c.u_id = ?
        ORDER BY c.created_at DESC
    ";
    $stmt = $conn->prepare($complaints_query);
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $complaints_result = $stmt->get_result();
    $complaints = [];
    while ($row = $complaints_result->fetch_assoc()) {
        $complaints[] = $row;
    }
    $stmt->close();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-header {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav-link:hover {
            background: var(--accent-red);
            border-color: var(--accent-red);
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .profile-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--accent-red), #990a1f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 1.5rem;
        }

        .profile-info {
            text-align: center;
        }

        .profile-name {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .profile-meta {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 0.5rem;
        }

        .profile-stat {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent-red);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 0.25rem;
        }

        .btn-logout {
            width: 100%;
            padding: 0.75rem;
            background: #8b0000;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: #660000;
        }

        .success-message {
            background: rgba(45, 90, 45, 0.3);
            border: 1px solid #90EE90;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 2rem;
            color: #90EE90;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-color);
        }

        .tab-btn {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .tab-btn.active {
            color: var(--accent-red);
            border-bottom-color: var(--accent-red);
        }

        .tab-btn:hover {
            color: var(--text-primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .bookings-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-primary);
            border-bottom: 2px solid var(--border-color);
        }

        th {
            padding: 1rem;
            text-align: left;
            color: var(--text-primary);
            font-weight: bold;
            font-size: 14px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 14px;
        }

        tbody tr:hover {
            background: rgba(229, 9, 20, 0.05);
        }

        .booking-status {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            background: rgba(45, 90, 45, 0.3);
            border: 1px solid #90EE90;
            border-radius: 4px;
            color: #90EE90;
            font-size: 12px;
            font-weight: bold;
        }

        .booking-id {
            color: var(--accent-red);
            font-weight: bold;
            font-family: monospace;
        }

        .empty-bookings {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-bookings-icon {
            font-size: 48px;
            margin-bottom: 1rem;
        }

        .btn-book-now {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-book-now:hover {
            background: #d40812;
            transform: scale(1.02);
        }

        .btn-cancel {
            padding: 0.5rem 1rem;
            background: #8b0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #b00000;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .review-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            position: relative;
        }

        .review-movie {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .review-poster {
            width: 60px;
            height: 90px;
            background: #0f0f0f;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .review-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .review-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 14px;
            color: var(--text-primary);
        }

        .review-rating {
            color: var(--accent-red);
            font-size: 16px;
            margin-bottom: 0.5rem;
        }

        .review-comment {
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 1rem;
            max-height: 80px;
            overflow: hidden;
        }

        .review-date {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .btn-delete-review {
            width: 100%;
            padding: 0.5rem;
            background: #8b0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .btn-delete-review:hover {
            background: #b00000;
        }

        .messages-grid {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .message-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
        }

        .message-card.unread {
            border-color: var(--accent-red);
            box-shadow: 0 0 0 1px rgba(229, 9, 20, 0.15);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .message-title {
            font-weight: bold;
            color: var(--text-primary);
        }

        .message-meta {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .message-body {
            color: var(--text-secondary);
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .message-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            background: rgba(229, 9, 20, 0.12);
            color: var(--accent-red);
            border: 1px solid rgba(229, 9, 20, 0.25);
            flex-shrink: 0;
        }

        .upcoming-label {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            background: rgba(45, 90, 45, 0.3);
            border: 1px solid #90EE90;
            border-radius: 3px;
            color: #90EE90;
            font-size: 11px;
            font-weight: bold;
        }

        .past-label {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            background: rgba(90, 45, 45, 0.3);
            border: 1px solid #d4a5a5;
            border-radius: 3px;
            color: #d4a5a5;
            font-size: 11px;
            font-weight: bold;
        }

        @media (max-width: 1024px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-card {
                max-width: 100%;
            }

            .profile-stat {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .reviews-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
            }

            .profile-header {
                font-size: 20px;
                flex-direction: column;
                gap: 1rem;
            }

            .header-nav {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .table-container {
                font-size: 12px;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }

            .profile-stat {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab-btn {
                padding: 0.75rem 1rem;
                font-size: 14px;
            }

            .reviews-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Edit Profile Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 460px;
            margin: 1rem;
            position: relative;
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 22px;
            cursor: pointer;
            line-height: 1;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #0f0f0f;
            border: 1px solid #444;
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: var(--accent-red);
        }

        .form-group .hint {
            font-size: 11px;
            color: #666;
            margin-top: 0.3rem;
        }

        .btn-save-profile {
            width: 100%;
            padding: 0.85rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }

        .btn-save-profile:hover {
            background: #d40812;
        }

        .btn-edit-profile {
            width: 100%;
            padding: 0.75rem;
            background: transparent;
            color: var(--accent-red);
            border: 1px solid var(--accent-red);
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-edit-profile:hover {
            background: rgba(229, 9, 20, 0.1);
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div class="profile-container">
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <span>✓</span>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="success-message" style="background: rgba(90, 45, 45, 0.3); border-color: #d4a5a5; color: #d4a5a5;">
                <span>✕</span>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <span>👤 My Profile</span>
            <div class="header-nav">
                <a href="index.php" class="nav-link">🎬 Home</a>
                <a href="logout.php" class="nav-link" onclick="window.location.href='logout.php'; return false;">🚪 Logout</a>
            </div>
        </div>

        <div class="profile-content">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar"><img src="userIcon.png" alt="User" style="width: 100px; height: 100px; border-radius: 50%;"></div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="profile-meta">ID: <?php echo htmlspecialchars($user['u_id']); ?></div>
                    <div class="profile-meta">📞 <?php echo htmlspecialchars($user['contact']); ?></div>
                    <div class="profile-meta">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>

                    <div class="profile-stat">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($all_bookings); ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($upcoming_bookings); ?></div>
                            <div class="stat-label">Upcoming</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($reviews); ?></div>
                            <div class="stat-label">Reviews</div>
                        </div>
                    </div>

                    <div class="profile-stat">
                        <div class="stat-item" style="grid-column: 1 / -1;">
                            <div class="stat-number">৳<?php 
                                $total_spent = array_sum(array_map(function($b) { return $b['total_price']; }, $all_bookings));
                                echo $total_spent > 0 ? number_format($total_spent, 0) : '0';
                            ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>

                    <div class="profile-stat">
                        <div class="stat-item" style="grid-column: 1 / -1; background: rgba(255, 179, 0, 0.1); border: 1px solid rgba(255, 179, 0, 0.3); border-radius: 4px; padding: 1rem;">
                            <div class="stat-number" style="color: #FFB300;">৳<?php echo number_format($wallet_balance, 2); ?></div>
                            <div class="stat-label">💳 Wallet Balance</div>
                            <a href="recharge.php" style="display: inline-block; margin-top: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #D81B60 0%, #C2185B 100%); color: white; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold;">+ Recharge Wallet</a>
                        </div>
                    </div>

                    <button type="button" class="btn-logout" onclick="window.location.href='logout.php'">🚪 Logout</button>
                </div>
            </div>

            <!-- Bookings & Reviews Section -->
            <div>
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab(event, 'upcoming')">📅 Upcoming (<?php echo count($upcoming_bookings); ?>)</button>
                    <button class="tab-btn" onclick="switchTab(event, 'past')">📺 Past (<?php echo count($past_bookings); ?>)</button>
                    <button class="tab-btn" onclick="switchTab(event, 'pending-orders')">🍕 Pending Orders (<?php echo count($pending_orders); ?>)</button>
                    <button class="tab-btn" onclick="switchTab(event, 'delivered-orders')">✅ Delivered (<?php echo count($delivered_orders); ?>)</button>
                    <button class="tab-btn" onclick="switchTab(event, 'messages')">💬 Messages (<?php echo count($messages); ?>)</button>
                    <button class="tab-btn" onclick="switchTab(event, 'reviews')">⭐ Reviews (<?php echo count($reviews); ?>)</button>
                    <button class="tab-btn" onclick="switchTab(event, 'complaints')">📋 Past Complains</button>
                    <button class="tab-btn" onclick="switchTab(event, 'edit-profile')">✏️ Edit Profile</button>
                </div>

                <!-- Upcoming Bookings Tab -->
                <div id="upcoming" class="tab-content active bookings-section">
                    <div class="section-title">📅 Upcoming Bookings</div>
                    <?php if (count($upcoming_bookings) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Booking No</th>
                                        <th>Movie</th>
                                        <th>Theatre</th>
                                        <th>Date & Time</th>
                                        <th>Seats</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_bookings as $booking): ?>
                                        <tr>
                                            <td class="booking-id">#<?php echo str_pad($booking['book_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($booking['mov_name']); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($booking['theatre_name']); ?></div>
                                                <div style="color: var(--text-secondary); font-size: 12px;"><?php echo htmlspecialchars($booking['hall_name']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d M Y', strtotime($booking['show_date'])); ?></div>
                                                <div style="color: var(--text-secondary); font-size: 12px;"><?php echo date('h:i A', strtotime($booking['show_time'])); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['seat_numbers']); ?></td>
                                            <td>৳<?php echo number_format($booking['total_price'], 0); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure? Seats will be freed.');">
                                                    <input type="hidden" name="action" value="cancel_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['book_id']; ?>">
                                                    <button type="submit" class="btn-cancel">Cancel</button>
                                                </form>
                                                <a href="booking-ticket.php?book_id=<?php echo $booking['book_id']; ?>" target="_blank" style="display: inline-block; margin-left: 8px; padding: 6px 12px; background: #0f172a; color: #fff; border-radius: 4px; text-decoration: none; font-size: 12px;">Print</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">📅</div>
                            <p style="color: var(--text-secondary);">No upcoming bookings</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Past Bookings Tab -->
                <div id="past" class="tab-content bookings-section">
                    <div class="section-title">📺 Past Bookings</div>
                    <?php if (count($past_bookings) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Movie</th>
                                        <th>Theatre</th>
                                        <th>Date & Time</th>
                                        <th>Seats</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($past_bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['mov_name']); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($booking['theatre_name']); ?></div>
                                                <div style="color: var(--text-secondary); font-size: 12px;"><?php echo htmlspecialchars($booking['hall_name']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d M Y', strtotime($booking['show_date'])); ?></div>
                                                <div style="color: var(--text-secondary); font-size: 12px;"><?php echo date('h:i A', strtotime($booking['show_time'])); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['seat_numbers']); ?></td>
                                            <td>৳<?php echo number_format($booking['total_price'], 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">📺</div>
                            <p style="color: var(--text-secondary);">No past bookings</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pending Food Orders Tab -->
                <div id="pending-orders" class="tab-content bookings-section">
                    <div class="section-title">🍕 Pending Food Orders</div>
                    <?php if (count($pending_orders) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order Ref</th>
                                        <th>Food Item</th>
                                        <th>Theatre</th>
                                        <th>Order Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_orders as $order): ?>
                                        <tr>
                                            <td style="font-family: monospace; font-weight: bold; color: var(--accent-red);">
                                                ORDER-<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['food_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['theatre_name']); ?></td>
                                            <td>
                                                <div><?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                                                <div style="color: var(--text-secondary); font-size: 12px;"><?php echo date('h:i A', strtotime($order['order_date'])); ?></div>
                                            </td>
                                            <td>৳<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td>
                                                <span class="upcoming-label"><?php echo htmlspecialchars($order['status']); ?></span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Cancel this food order? Your amount will be refunded to the wallet.');">
                                                    <input type="hidden" name="action" value="cancel_food_order">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <button type="submit" class="btn-cancel">Cancel</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">🍕</div>
                            <p style="color: var(--text-secondary);">No pending food orders</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Delivered Food Orders Tab -->
                <div id="delivered-orders" class="tab-content bookings-section">
                    <div class="section-title">✅ Delivered Food Orders</div>
                    <?php if (count($delivered_orders) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order Ref</th>
                                        <th>Food Item</th>
                                        <th>Theatre</th>
                                        <th>Order Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($delivered_orders as $order): ?>
                                        <tr>
                                            <td style="font-family: monospace; font-weight: bold; color: var(--success-color);">
                                                ORDER-<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['food_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['theatre_name']); ?></td>
                                            <td>
                                                <div><?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                                                <div style="color: var(--text-secondary); font-size: 12px;"><?php echo date('h:i A', strtotime($order['order_date'])); ?></div>
                                            </td>
                                            <td>৳<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td>
                                                <span style="color: var(--success-color); font-weight: bold;">✅ Delivered</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">✅</div>
                            <p style="color: var(--text-secondary);">No delivered orders yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Messages Tab -->
                <div id="messages" class="tab-content bookings-section">
                    <div class="section-title">💬 Messages / Notifications</div>
                    <?php if (count($messages) > 0): ?>
                        <div class="messages-grid">
                            <?php foreach ($messages as $message): ?>
                                <div class="message-card <?php echo !empty($message['is_read']) ? '' : 'unread'; ?>">
                                    <div class="message-header">
                                        <div>
                                            <div class="message-title">
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $message['notif_type'] ?? 'notification'))); ?>
                                            </div>
                                            <div class="message-meta">
                                                <?php echo date('d M Y, h:i A', strtotime($message['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="message-badge"><?php echo !empty($message['is_read']) ? 'Read' : 'New'; ?></div>
                                    </div>
                                    <div class="message-body"><?php echo htmlspecialchars($message['message']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">💬</div>
                            <p style="color: var(--text-secondary);">No messages or notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reviews Tab -->
                <div id="reviews" class="tab-content bookings-section">
                    <div class="section-title">⭐ Your Reviews</div>
                    <?php if (count($reviews) > 0): ?>
                        <div class="reviews-grid">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-movie">
                                        <div class="review-poster">
                                            <?php if ($review['mov_poster']): ?>
                                                <img src="<?php echo htmlspecialchars($review['mov_poster']); ?>" alt="<?php echo htmlspecialchars($review['mov_name']); ?>">
                                            <?php else: ?>
                                                🎬
                                            <?php endif; ?>
                                        </div>
                                        <div class="review-info">
                                            <h4><?php echo htmlspecialchars($review['mov_name']); ?></h4>
                                            <div class="review-rating">
                                                <?php echo str_repeat('⭐', $review['rating']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></div>
                                    <div class="review-date">
                                        Reviewed on <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                                    </div>
                                    <form method="POST" style="display: block;" onsubmit="return confirm('Delete this review?');">
                                        <input type="hidden" name="action" value="delete_review">
                                        <input type="hidden" name="review_id" value="<?php echo $review['rev_id']; ?>">
                                        <button type="submit" class="btn-delete-review">🗑 Delete Review</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">⭐</div>
                            <p style="color: var(--text-secondary);">You haven't written any reviews yet</p>
                            <a href="index.php" class="btn-book-now">🎬 Home</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Past Complaints Tab -->
                <div id="complaints" class="tab-content bookings-section">
                    <div class="section-title">📋 Past Complaints</div>
                    <?php if (count($complaints) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Theatre</th>
                                        <th>Complaint</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($complaint['theatre_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($complaint['complaint_text']); ?></td>
                                            <td>
                                                <?php
                                                $status = $complaint['status'] ?? 'Not Seen';
                                                $statusColor = match($status) {
                                                    'Seen' => '#90EE90',
                                                    'Working' => '#FFB300',
                                                    'Resolved' => '#4CAF50',
                                                    default => '#888'
                                                };
                                                ?>
                                                <span style="color: <?php echo $statusColor; ?>; font-weight: bold;">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($complaint['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-bookings">
                            <div class="empty-bookings-icon">📋</div>
                            <p style="color: var(--text-secondary);">No past complaints</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Edit Profile Tab -->
                <div id="edit-profile" class="tab-content bookings-section">
                    <div class="section-title">✏️ Edit Profile</div>
                    <form method="POST" style="max-width: 480px;">
                        <input type="hidden" name="action" value="edit_profile">
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display:block; font-size:13px; color:#aaa; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                style="width:100%; padding:0.75rem 1rem; background:#0f0f0f; border:1px solid #444; border-radius:6px; color:#fff; font-size:15px; box-sizing:border-box;">
                        </div>
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display:block; font-size:13px; color:#aaa; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Contact Number</label>
                            <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" required
                                style="width:100%; padding:0.75rem 1rem; background:#0f0f0f; border:1px solid #444; border-radius:6px; color:#fff; font-size:15px; box-sizing:border-box;">
                        </div>
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display:block; font-size:13px; color:#aaa; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">New Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current password"
                                style="width:100%; padding:0.75rem 1rem; background:#0f0f0f; border:1px solid #444; border-radius:6px; color:#fff; font-size:15px; box-sizing:border-box;">
                            <div style="font-size:12px; color:#666; margin-top:4px;">Leave blank if you don't want to change your password.</div>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display:block; font-size:13px; color:#aaa; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Re-enter new password"
                                style="width:100%; padding:0.75rem 1rem; background:#0f0f0f; border:1px solid #444; border-radius:6px; color:#fff; font-size:15px; box-sizing:border-box;">
                        </div>
                        <button type="submit"
                            style="width:100%; padding:0.85rem; background:#e50914; color:#fff; border:none; border-radius:6px; font-size:15px; font-weight:bold; cursor:pointer;">
                            💾 Save Changes
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        function switchTab(event, tabName) {
            event.preventDefault();
            
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>