<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];
$amount = floatval($_GET['amount'] ?? 0);
$method = trim($_GET['method'] ?? '');
$return_to = $_GET['return_to'] ?? 'index';
$movie_id = $_GET['movie_id'] ?? '';

if ($amount <= 0 || !in_array($method, ['bKash', 'Nagad'])) {
    header('Location: recharge.php?error=Invalid amount or method');
    exit();
}

// Brand theming based on method
$is_nagad = ($method === 'Nagad');
$brand_color     = $is_nagad ? '#F26522' : '#e2136e';
$brand_hover     = $is_nagad ? '#d45510' : '#c20e5c';
$brand_light_bg  = $is_nagad ? '#FFD7AD' : '#fce4ec';
$brand_logo_src  = $is_nagad ? 'nagad.png'  : 'bkash.png';
$brand_logo_href = $is_nagad ? 'https://nagad.com.bd/' : 'https://bka.sh/';
$brand_helpline  = $is_nagad ? '16167' : '16247';
$brand_copyright = $is_nagad ? 'Nagad' : 'bKash';

$step = $_POST['step'] ?? 1;
$account_number = $_POST['account_number'] ?? '';
$otp = $_POST['otp'] ?? '';
$pin = $_POST['pin'] ?? '';
$verification_error = '';
$verification_success = false;

// Get user info
$user_query = "SELECT name, contact FROM user WHERE u_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle OTP verification (any 6-digit number works)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    if (empty($account_number)) {
        $verification_error = 'Account number is required';
        $step = 1;
    } elseif (!preg_match('/^\d{10,11}$/', $account_number)) {
        $verification_error = 'Invalid account number format (must be 10-11 digits)';
        $step = 1;
    } else {
        // OTP is generated (in real system, would be sent via SMS)
        // For simulation, any 6-digit input works
        $step = 3;
    }
}

// Handle PIN verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '3') {
    if (empty($otp)) {
        $verification_error = 'OTP is required';
        $step = 2;
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $verification_error = 'OTP must be 6 digits';
        $step = 2;
    } else {
        $step = 4;
    }
}

// Handle final verification (PIN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '4') {
    if (empty($pin)) {
        $verification_error = 'PIN is required';
        $step = 3;
    } elseif ($pin !== '1234') {
        $verification_error = 'Incorrect PIN. Use PIN: 1234 for testing.';
        $step = 3;
    } else {
        // Payment verified - process recharge
        $verification_success = true;
        
        // Generate transaction ID
        $transaction_id = 'TRX' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 7);
        $now = date('Y-m-d H:i:s');
        
        try {
            $conn->begin_transaction();
            
            // Insert recharge history
            $insert_recharge = "INSERT INTO recharge_history (u_id, amount, transaction_id, method, status, date) 
                               VALUES (?, ?, ?, ?, 'Success', ?)";
            $stmt = $conn->prepare($insert_recharge);
            $stmt->bind_param("sdsss", $u_id, $amount, $transaction_id, $method, $now);
            $stmt->execute();
            $stmt->close();
            
            // Update balance using ON DUPLICATE KEY UPDATE
            $update_balance = "INSERT INTO balance (u_id, current_balance) 
                              VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE current_balance = current_balance + ?";
            $stmt = $conn->prepare($update_balance);
            $stmt->bind_param("sdd", $u_id, $amount, $amount);
            $stmt->execute();
            $stmt->close();
            
            // Log transaction
            $log_query = "INSERT INTO wallet_transaction_log (u_id, transaction_type, reference_id, amount, operation, status) 
                         VALUES (?, 'recharge', ?, ?, 'credit', 'Success')";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("ssd", $u_id, $transaction_id, $amount);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            // Redirect to success page with return info
            $_SESSION['recharge_success'] = [
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'method' => $method,
                'return_to' => $return_to,
                'movie_id' => $movie_id
            ];
            header('Location: recharge-success.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $verification_error = 'Transaction failed. Please try again. Error: ' . $e->getMessage();
            $step = 4;
        }
    }
}

// Get current balance
$balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
$stmt = $conn->prepare($balance_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$balance_result = $stmt->get_result()->fetch_assoc();
$current_balance = $balance_result['current_balance'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($method); ?> Payment - ShowFlow Wallet</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .payment-container {
            width: 100%;
            max-width: 500px;
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        /* Header Section */
        .header-logo {
            text-align: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .header-logo img {
            height: 50px;
        }

        /* Merchant Section */
        .merchant-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
        }

        .merchant-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .merchant-logo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: <?php echo $brand_color; ?>;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 24px;
            border: 1px solid #eee;
        }

        .merchant-details h3 {
            font-size: 16px;
            color: #333;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .merchant-details p {
            font-size: 12px;
            color: #666;
        }

        .amount {
            font-size: 24px;
            color: #333;
        }

        /* Form Section */
        .bkash-body {
            background-color: <?php echo $brand_color; ?>;
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .bkash-body h2 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .test-credentials {
            background: <?php echo $brand_light_bg; ?>;
            color: <?php echo $brand_color; ?>;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .error-message {
            background: #fff;
            color: <?php echo $brand_color; ?>;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .account-input {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            text-align: center;
            border: none;
            border-radius: 3px;
            outline: none;
            color: #555;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .account-input::placeholder {
            color: #aaa;
            letter-spacing: normal;
        }

        .terms {
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .terms input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .terms a {
            color: white;
            text-decoration: underline;
            font-weight: bold;
        }

        /* Footer Section */
        .footer-section {
            padding: 20px;
            background-color: #fff;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            font-size: 16px;
            border-radius: 3px;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
        }

        .btn-cancel {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .btn-confirm {
            background-color: <?php echo $brand_color; ?>;
            color: white;
            transition: 0.3s;
        }

        .btn-confirm:hover {
            background-color: <?php echo $brand_hover; ?>;
        }

        .support {
            text-align: center;
            font-size: 14px;
            color: <?php echo $brand_color; ?>;
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .support svg {
            width: 16px;
            height: 16px;
            fill: <?php echo $brand_color; ?>;
        }

        .copyright {
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="payment-container">
        <div class="header-logo">
            <a href="<?php echo $brand_logo_href; ?>" target="_blank">
                <img src="<?php echo $brand_logo_src; ?>" alt="<?php echo $method; ?> Logo">
            </a>
        </div>

        <div class="merchant-section">
            <div class="merchant-info">
                <div class="merchant-logo">🎬</div>
                <div class="merchant-details">
                    <h3>ShowFlow Entertainment</h3>
                    <p>Wallet Recharge</p>
                </div>
            </div>
            <div class="amount">
                ৳<?php echo number_format($amount, 2); ?>
            </div>
        </div>

        <form method="POST">
            <div class="bkash-body">
                
                <?php if (!empty($verification_error)): ?>
                    <div class="error-message">
                        ⚠️ <?php echo htmlspecialchars($verification_error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <input type="hidden" name="step" value="2">
                    
                    <div class="test-credentials">
                        <strong>📌 Test Mode:</strong> Enter any 10-11 digit mobile number
                    </div>

                    <h2>Your <?php echo $method; ?> Account Number</h2>
                    <input type="tel" class="account-input" name="account_number" placeholder="e.g 01XXXXXXXXX" 
                           pattern="\d{10,11}" required autofocus value="<?php echo htmlspecialchars($account_number); ?>">
                    
                    <div class="terms">
                        <input type="checkbox" id="agree" name="agree" required>
                        <label for="agree">I agree to the <a href="#">terms & conditions</a></label>
                    </div>

                <?php elseif ($step === 2): ?>
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="account_number" value="<?php echo htmlspecialchars($account_number); ?>">
                    
                    <div class="test-credentials">
                        <strong>📌 Test Mode:</strong> Enter any 6-digit OTP (e.g., 123456)
                    </div>

                    <h2>Enter Verification Code sent to<br><?php echo htmlspecialchars($account_number); ?></h2>
                    <input type="text" class="account-input" name="otp" placeholder="bKash Verification Code" 
                           pattern="\d{6}" maxlength="6" required autofocus inputmode="numeric">

                <?php elseif ($step === 3): ?>
                    <input type="hidden" name="step" value="4">
                    <input type="hidden" name="account_number" value="<?php echo htmlspecialchars($account_number); ?>">
                    <input type="hidden" name="otp" value="<?php echo htmlspecialchars($otp); ?>">
                    
                    <div class="test-credentials">
                        <strong>📌 Test Mode:</strong> Use PIN: 1234
                    </div>

                    <h2>Enter PIN of your <?php echo $method; ?> Account</h2>
                    <input type="password" class="account-input" name="pin" placeholder="Enter PIN" 
                           pattern="\d{4}" maxlength="4" required autofocus inputmode="numeric">

                <?php elseif ($step === 4 && !$verification_success): ?>
                    <div style="padding: 40px 0;">
                        <h2>Processing Payment...</h2>
                        <p style="opacity: 0.8; margin-top: 10px;">Please wait while we verify your information.</p>
                    </div>
                <?php endif; ?>

            </div>

            <div class="footer-section">
                <div class="button-group">
                    <a href="recharge.php" class="btn btn-cancel" style="text-decoration: none;">CLOSE</a>
                    <?php if ($step < 4): ?>
                        <button type="submit" class="btn btn-confirm">PROCEED</button>
                    <?php else: ?>
                        <button type="button" class="btn" style="background-color: #e0e0e0; color: #a0a0a0; cursor: not-allowed;" disabled>PROCESSING</button>
                    <?php endif; ?>
                </div>
                
                <div class="support">
                    <svg viewBox="0 0 24 24">
                        <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                    </svg>
                    <?php echo $brand_helpline; ?>
                </div>
                <div class="copyright">
                    © <?php echo date('Y'); ?> <?php echo $brand_copyright; ?>, All Rights Reserved
                </div>
            </div>
        </form>
    </div>

</body>
</html>