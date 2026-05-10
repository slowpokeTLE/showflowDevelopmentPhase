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
$recharge_data = $_SESSION['recharge_success'] ?? null;

if (!$recharge_data) {
    header('Location: recharge.php');
    exit();
}

// Get updated balance
$balance = getUserWalletBalance($u_id);

// Check for pending booking and auto-confirm if exists
$auto_booking_success = false;
$auto_booking_error = '';
$booking_confirmation = null;

if (isset($_SESSION['pending_booking'])) {
    $pending = $_SESSION['pending_booking'];
    unset($_SESSION['pending_booking']);
    
    try {
        $s_id = $pending['s_id'];
        $seats = $pending['seats'];
        $show = $pending['show'];
        $total_amount = $pending['total_amount'];
        
        // Build seat_numbers string
        $seat_numbers = implode(',', array_map(fn($s) => $s['row'] . '-' . $s['col'], $seats));
        
        // Fetch current balance after recharge
        $current_balance = $balance;
        
        // Process the booking
        $conn->begin_transaction();
        try {
            // Insert booking
            $insert_query = "INSERT INTO booking (u_id, s_id, seat_numbers, total_amount, status, paid_from_wallet, payment_status) VALUES (?, ?, ?, ?, 'Confirmed', 1, 'Completed')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sisd", $u_id, $s_id, $seat_numbers, $total_amount);
            $stmt->execute();
            $book_id = $conn->insert_id;
            $stmt->close();
            
            // Deduct from wallet balance
            $deduct_query = "UPDATE balance SET current_balance = current_balance - ? WHERE u_id = ?";
            $stmt = $conn->prepare($deduct_query);
            $stmt->bind_param("ds", $total_amount, $u_id);
            $stmt->execute();
            $stmt->close();
            
            // Log transaction
            $book_id_str = (string)$book_id;
            $log_query = "INSERT INTO wallet_transaction_log (u_id, transaction_type, reference_id, amount, operation, balance_before, balance_after, status) 
                         VALUES (?, 'booking', ?, ?, 'debit', ?, ?, 'Success')";
            $stmt = $conn->prepare($log_query);
            $new_balance = $current_balance - $total_amount;
            $stmt->bind_param("ssddd", $u_id, $book_id_str, $total_amount, $current_balance, $new_balance);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            // Build seat labels for display
            $seat_labels = [];
            foreach ($seats as $s) {
                $seat_labels[] = [
                    'row' => $s['row'],
                    'col' => $s['col'],
                    'label' => chr(65 + $s['row']) . ($s['col'] + 1)
                ];
            }
            
            // Store confirmation data
            $booking_confirmation = [
                'booking_id' => $book_id,
                'show' => $show,
                'seats' => $seat_labels,
                'total' => $total_amount
            ];
            
            $auto_booking_success = true;
            
            // Update balance after booking
            $balance = getUserWalletBalance($u_id);
            
        } catch (Exception $e) {
            $conn->rollback();
            $auto_booking_error = 'Booking failed: ' . $e->getMessage();
        }
    } catch (Exception $e) {
        $auto_booking_error = 'Error processing booking: ' . $e->getMessage();
    }
}

// Check for pending food order and auto-confirm if exists
$auto_food_order_success = false;
$auto_food_order_error = '';
$food_order_confirmation = null;

if (isset($_SESSION['pending_food_order']) && !$auto_booking_success) {
    $pending_order = $_SESSION['pending_food_order'];
    unset($_SESSION['pending_food_order']);
    
    try {
        $t_id = $pending_order['t_id'];
        $items = $pending_order['items'];
        $total_price = $pending_order['total_price'];
        
        // Fetch current balance after recharge
        $current_balance = $balance;
        
        // Process the food order
        $conn->begin_transaction();
        try {
            $order_date = date('Y-m-d H:i:s');
            $order_id = null;
            $order_items = [];
            
            foreach ($items as $item) {
                $food_id = intval($item['food_id']);
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $item_total = $qty * $price;
                
                // Fetch food item details for display
                $food_query = "SELECT food_name FROM food_item WHERE food_id = ? LIMIT 1";
                $stmt = $conn->prepare($food_query);
                $stmt->bind_param("i", $food_id);
                $stmt->execute();
                $food_result = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $food_name = $food_result['food_name'] ?? 'Item #' . $food_id;
                
                // Insert each food item as a separate order record
                $order_query = "INSERT INTO food_order (u_id, t_id, food_id, quantity, total_price, order_date, status, paid_from_wallet, payment_status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'Pending', 1, 'Completed')";
                $stmt = $conn->prepare($order_query);
                $stmt->bind_param("siiiis", $u_id, $t_id, $food_id, $qty, $item_total, $order_date);
                $stmt->execute();
                
                if ($order_id === null) {
                    $order_id = $conn->insert_id;
                }
                
                $order_items[] = [
                    'name' => $food_name,
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $item_total
                ];
                
                $stmt->close();
            }
            
            // Deduct from wallet balance
            $deduct_query = "UPDATE balance SET current_balance = current_balance - ? WHERE u_id = ?";
            $stmt = $conn->prepare($deduct_query);
            $stmt->bind_param("ds", $total_price, $u_id);
            $stmt->execute();
            $stmt->close();
            
            // Log transaction
            $order_id_str = (string)$order_id;
            $log_query = "INSERT INTO wallet_transaction_log (u_id, transaction_type, reference_id, amount, operation, balance_before, balance_after, status) 
                         VALUES (?, 'food_order', ?, ?, 'debit', ?, ?, 'Success')";
            $stmt = $conn->prepare($log_query);
            $new_balance = $current_balance - $total_price;
            $stmt->bind_param("ssddd", $u_id, $order_id_str, $total_price, $current_balance, $new_balance);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            // Store confirmation data
            $food_order_confirmation = [
                'order_id' => $order_id,
                'items' => $order_items,
                'total' => $total_price
            ];
            
            $auto_food_order_success = true;
            
            // Update balance after order
            $balance = getUserWalletBalance($u_id);
            
        } catch (Exception $e) {
            $conn->rollback();
            $auto_food_order_error = 'Order failed: ' . $e->getMessage();
        }
    } catch (Exception $e) {
        $auto_food_order_error = 'Error processing food order: ' . $e->getMessage();
    }
}

// Determine return URL
$return_to = $recharge_data['return_to'] ?? 'index';
$movie_id = $recharge_data['movie_id'] ?? '';
$return_url = 'index.php';
if ($return_to === 'booking') {
    $return_url = 'user-profile.php';
} elseif ($return_to === 'food') {
    $return_url = 'facilities.php';
}

// Clear the session data
unset($_SESSION['recharge_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recharge Successful - ShowFlow Wallet</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
        }

        .success-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #242424 100%);
            border-radius: 12px;
            padding: 40px 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            animation: successBounce 0.6s ease;
        }

        @keyframes successBounce {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #fff;
        }

        .subtitle {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 30px;
        }

        .details-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: #aaa;
        }

        .detail-value {
            font-weight: 600;
            color: #fff;
        }

        .transaction-id {
            color: #4CAF50;
        }

        .amount {
            color: #FFB300;
            font-size: 16px;
        }

        .balance-display {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(255, 179, 0, 0.1) 100%);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .balance-label {
            font-size: 13px;
            color: #aaa;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .balance-amount {
            font-size: 32px;
            font-weight: bold;
            color: #4CAF50;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #D81B60 0%, #C2185B 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(216, 27, 96, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .timer {
            font-size: 12px;
            color: #aaa;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1>Payment Successful!</h1>
            <p class="subtitle">Your wallet has been recharged</p>

            <div class="details-section">
                <div class="detail-row">
                    <span class="detail-label">Transaction ID</span>
                    <span class="detail-value transaction-id"><?php echo htmlspecialchars($recharge_data['transaction_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Method</span>
                    <span class="detail-value"><?php echo htmlspecialchars($recharge_data['method']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount</span>
                    <span class="detail-value amount">৳ <?php echo number_format($recharge_data['amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo date('M d, Y H:i A'); ?></span>
                </div>
            </div>

            <div class="balance-display">
                <div class="balance-label">Current Wallet Balance</div>
                <div class="balance-amount">৳ <?php echo number_format($balance, 2); ?></div>
            </div>

            <?php if ($auto_booking_success && $booking_confirmation): ?>
                <!-- Auto-Booking Confirmation -->
                <div style="background: rgba(76, 175, 80, 0.1); border: 1px solid #4CAF50; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="font-size: 24px; margin-right: 10px;">✅</span>
                        <div>
                            <h3 style="color: #4CAF50; margin: 0;">Booking Auto-Confirmed!</h3>
                            <p style="color: #aaa; margin: 0; font-size: 12px;">Your seats have been reserved automatically</p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 6px; padding: 15px;">
                        <div style="display: grid; gap: 10px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #aaa;">Booking ID</span>
                                <span style="color: #fff; font-weight: 600;">#<?php echo str_pad($booking_confirmation['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #aaa;">Movie</span>
                                <span style="color: #fff;"><?php echo htmlspecialchars($booking_confirmation['show']['mov_name']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #aaa;">Date & Time</span>
                                <span style="color: #fff;">
                                    <?php echo date('d M Y', strtotime($booking_confirmation['show']['show_date'])); ?> 
                                    <?php echo date('h:i A', strtotime($booking_confirmation['show']['show_time'])); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #aaa;">Seats</span>
                                <span style="color: #FFB300; font-weight: 600;">
                                    <?php echo implode(', ', array_map(fn($s) => $s['label'], $booking_confirmation['seats'])); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 10px; margin-top: 10px;">
                                <span style="color: #aaa;">Amount Charged</span>
                                <span style="color: #4CAF50; font-weight: 600;">৳ <?php echo number_format($booking_confirmation['total'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($auto_food_order_success && $food_order_confirmation): ?>
                <!-- Auto-Food Order Confirmation -->
                <div style="background: rgba(76, 175, 80, 0.1); border: 1px solid #4CAF50; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="font-size: 24px; margin-right: 10px;">✅</span>
                        <div>
                            <h3 style="color: #4CAF50; margin: 0;">Order Auto-Confirmed!</h3>
                            <p style="color: #aaa; margin: 0; font-size: 12px;">Your food order has been placed automatically</p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 6px; padding: 15px;">
                        <div style="display: grid; gap: 10px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #aaa;">Order ID</span>
                                <span style="color: #fff; font-weight: 600;">#<?php echo str_pad($food_order_confirmation['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            
                            <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 10px; margin-top: 10px;">
                                <div style="color: #aaa; margin-bottom: 8px; font-size: 12px; text-transform: uppercase;">Items</div>
                                <?php foreach ($food_order_confirmation['items'] as $item): ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px;">
                                        <span style="color: #fff;">
                                            <?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?>
                                        </span>
                                        <span style="color: #FFB300;">৳ <?php echo number_format($item['total'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 10px; margin-top: 10px;">
                                <span style="color: #aaa;">Total Amount</span>
                                <span style="color: #4CAF50; font-weight: 600;">৳ <?php echo number_format($food_order_confirmation['total'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif (!$auto_booking_success && !empty($auto_booking_error)): ?>
                <!-- Auto-Booking Error -->
                <div style="background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #ff6b6b;">
                    <strong>⚠️ Note:</strong> <?php echo htmlspecialchars($auto_booking_error); ?> Please go back to booking to complete your reservation.
                </div>
            <?php elseif (!$auto_food_order_success && !empty($auto_food_order_error)): ?>
                <!-- Auto-Food Order Error -->
                <div style="background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #ff6b6b;">
                    <strong>⚠️ Note:</strong> <?php echo htmlspecialchars($auto_food_order_error); ?> Please go back to place your order again.
                </div>
            <?php endif; ?>

            <div class="button-group">
                <a href="<?php echo htmlspecialchars($return_url); ?>" class="btn btn-primary">
                    <?php 
                        if ($auto_booking_success) {
                            echo '🎉 View Booking';
                        } elseif ($auto_food_order_success) {
                            echo '✅ View Order';
                        } elseif ($return_to === 'booking') {
                            echo '🎫 Back to Booking';
                        } else {
                            echo '🏠 Go to Home';
                        }
                    ?>
                </a>
                <a href="recharge.php" class="btn btn-secondary">💳 Recharge Again</a>
            </div>

            
            <div class="timer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #aaa;">
                ℹ️ You can close this page or navigate using the buttons above
            </div>
        </div>
    </div>

    <script>
         // Auto-redirect removed - user can manually click button or just close page
    </script>
</body>
</html>
