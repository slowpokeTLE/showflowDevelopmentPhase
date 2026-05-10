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

    if ($action === 'place_order') {
        $t_id = intval($_POST['t_id'] ?? 0);
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if ($t_id <= 0 || empty($items)) {
            jsonResponse('error', 'Invalid order data');
        }

        // Validate all items exist and belong to theatre
        $food_ids = array_map(function($item) { return intval($item['food_id']); }, $items);
        $placeholders = implode(',', array_fill(0, count($food_ids), '?'));
        
        $verify_query = "SELECT food_id FROM food_item WHERE t_id = ? AND food_id IN ($placeholders)";
        $stmt = $conn->prepare($verify_query);
        
        $params = array_merge([$t_id], $food_ids);
        $types = 'i' . str_repeat('i', count($food_ids));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $verified_count = $stmt->get_result()->num_rows;
        $stmt->close();

        if ($verified_count != count($items)) {
            jsonResponse('error', 'Invalid food items');
        }

        // Calculate total price
        $total_price = 0;
        foreach ($items as $item) {
            $qty = intval($item['qty']);
            $price = floatval($item['price']);
            $total_price += $qty * $price;
        }

        // Check user's wallet balance
        $balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
        $stmt = $conn->prepare($balance_query);
        $stmt->bind_param("s", $u_id);
        $stmt->execute();
        $balance_result = $stmt->get_result()->fetch_assoc();
        $current_balance = (float)($balance_result['current_balance'] ?? 0);
        $stmt->close();

        // Check if balance is sufficient
        if ($current_balance < $total_price) {
            // Store pending food order in session for auto-confirmation after recharge
            $_SESSION['pending_food_order'] = [
                't_id' => $t_id,
                'items' => $items,
                'total_price' => $total_price,
                'deficit' => $total_price - $current_balance
            ];
            jsonResponse('insufficient_balance', 'Your MFS Wallet balance is too low. Your current balance: ৳ ' . number_format($current_balance, 2) . ', Required: ৳ ' . number_format($total_price, 2) . '. Please recharge your wallet.', [
                'current_balance' => $current_balance,
                'required_amount' => $total_price,
                'deficit' => $total_price - $current_balance
            ]);
        }

        $conn->begin_transaction();

        try {
            $order_date = date('Y-m-d H:i:s');
            $order_id = null;
            
            foreach ($items as $item) {
                $food_id = intval($item['food_id']);
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $item_total = $qty * $price;

                // Insert each food item as a separate order record
                $order_query = "INSERT INTO food_order (u_id, t_id, food_id, quantity, total_price, order_date, status, paid_from_wallet, payment_status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'Pending', 1, 'Completed')";
                $stmt = $conn->prepare($order_query);
                $stmt->bind_param("siiiis", $u_id, $t_id, $food_id, $qty, $item_total, $order_date);
                $stmt->execute();
                
                if ($order_id === null) {
                    $order_id = $conn->insert_id;
                }
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

            jsonResponse('success', 'Order placed successfully', [
                'order_id' => $order_id, 
                'total' => $total_price,
                'items_count' => count($items)
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse('error', 'Failed to place order: ' . $e->getMessage());
        }
    }
}
?>
