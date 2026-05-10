<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];

// Check if redirected from booking/food order with required amount
$amount = floatval($_GET['amount'] ?? 0);
$required_amount = floatval($_GET['required_amount'] ?? $amount);
$return_to = $_GET['return_to'] ?? '';
$movie_id = $_GET['movie_id'] ?? '';
$suggested_amount = $required_amount;

// Get user info
$user_query = "SELECT name, contact FROM user WHERE u_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get current balance
$balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
$stmt = $conn->prepare($balance_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$balance_result = $stmt->get_result()->fetch_assoc();
$current_balance = $balance_result['current_balance'] ?? 0;
$stmt->close();

// Get recent transactions
$recent_query = "SELECT r_id, amount, method, date FROM recharge_history WHERE u_id = ? ORDER BY date DESC LIMIT 5";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$recent = $stmt->get_result();
$recent_transactions = [];
while ($row = $recent->fetch_assoc()) {
    $recent_transactions[] = $row;
}
$stmt->close();


$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Recharge - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
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
        }

        .navbar {
            background: #0f0f0f;
            border-bottom: 1px solid #333;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar a {
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
        }

        .navbar a:hover {
            color: #fff;
        }

        .navbar .back-link {
            margin-right: auto;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #D81B60 0%, #FFB300 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #aaa;
            font-size: 14px;
        }

        .balance-card {
            background: linear-gradient(135deg, #D81B60 0%, #C2185B 100%);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
            text-align: center;
        }

        .balance-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .balance-amount {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .balance-description {
            font-size: 13px;
            opacity: 0.8;
            line-height: 1.6;
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid #F44336;
            color: #ffcccc;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message::before {
            content: '⚠️';
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .section {
            background: linear-gradient(135deg, #242424 0%, #1a1a1a 100%);
            border-radius: 12px;
            border: 1px solid #333;
            padding: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title-icon {
            width: 32px;
            height: 32px;
            background: rgba(216, 27, 96, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* Provider Selection */
        .providers-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .provider-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #242424 100%);
            border: 2px solid #333;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .provider-card:hover {
            border-color: #D81B60;
            background: linear-gradient(135deg, #2a1a1a 0%, #342424 100%);
            transform: translateY(-4px);
        }

        .provider-card input[type="radio"] {
            display: none;
        }

        .provider-card input[type="radio"]:checked + .provider-content {
            color: white;
        }

        .provider-card input[type="radio"]:checked ~ .provider-check {
            display: flex;
            animation: checkmark 0.3s ease;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
            }
            100% {
                transform: scale(1);
            }
        }

        .provider-logo {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .provider-content {
            color: #aaa;
            transition: color 0.3s ease;
        }

        .provider-content h3 {
            font-size: 18px;
            color: #fff;
            margin-bottom: 5px;
        }

        .provider-content p {
            font-size: 12px;
            opacity: 0.7;
        }

        .provider-check {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            background: #4CAF50;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .provider-card {
            position: relative;
        }

        /* Amount Input */
        .amount-section {
            margin-bottom: 30px;
        }

        .amount-input-group {
            margin-bottom: 20px;
        }

        .amount-input-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amount-input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #444;
            border-radius: 8px;
            background: #0f0f0f;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .amount-input-group input:focus {
            outline: none;
            border-color: #D81B60;
            box-shadow: 0 0 0 3px rgba(216, 27, 96, 0.1);
        }

        /* Preset amounts */
        .preset-amounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }

        .preset-btn {
            padding: 10px;
            background: linear-gradient(135deg, #2a2a2a 0%, #1f1f1f 100%);
            border: 1px solid #444;
            border-radius: 8px;
            color: #aaa;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .preset-btn:hover {
            border-color: #D81B60;
            color: #D81B60;
        }

        .btn-recharge {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #D81B60 0%, #C2185B 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-recharge:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(216, 27, 96, 0.4);
        }

        .btn-recharge:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Recent Transactions */
        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .transaction-item {
            background: linear-gradient(135deg, #1a1a1a 0%, #242424 100%);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transaction-info {
            flex: 1;
        }

        .transaction-method {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .transaction-date {
            font-size: 12px;
            color: #aaa;
        }

        .transaction-amount {
            text-align: right;
        }

        .transaction-amount .amount {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #aaa;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <a href="index.php" class="back-link">← Back to Home</a>
        <a href="user-profile.php">My Profile</a>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>💳 Wallet Recharge</h1>
            <p>Top up your ShowFlow wallet instantly using Mobile Financial Services</p>
        </div>

        <!-- Balance Card -->
        <div class="balance-card">
            <div class="balance-label">Current Wallet Balance</div>
            <div class="balance-amount">৳ <?php echo number_format($current_balance, 2); ?></div>
            <div class="balance-description">
                Use your wallet balance to book movie tickets and order food at ShowFlow theatres
            </div>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Insufficient Balance Message -->
        <?php if ($required_amount > 0): ?>
            <div class="error-message" style="background: rgba(216, 27, 96, 0.1); border: 1px solid #D81B60; color: #D81B60;">
                💳 You need to recharge at least <strong>৳<?php echo number_format($required_amount, 2); ?></strong> to complete your booking.
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="content-grid">
            <!-- Recharge Form -->
            <div class="section">
                <div class="section-title">
                    <div class="section-title-icon">🏦</div>
                    Payment Method
                </div>

                <form id="rechargeForm" method="GET" action="mfs_gateway.php">
                    <!-- Hidden fields for return navigation -->
                    <?php if ($return_to): ?>
                        <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to); ?>">
                    <?php endif; ?>
                    <?php if ($movie_id): ?>
                        <input type="hidden" name="movie_id" value="<?php echo htmlspecialchars($movie_id); ?>">
                    <?php endif; ?>
                    
                    <!-- Provider Selection -->
                    <div class="providers-grid">
                        <label class="provider-card">
                            <input type="radio" name="method" value="bKash" required>
                            <div class="provider-check">✓</div>
                            <div class="provider-content">
                                <div class="provider-logo"><img src="bkash.png" alt="bKash" style="height: 48px; width: auto;"></div>
                                <h3>bKash</h3>
                                <p>Bangladesh's Leading MFS</p>
                            </div>
                        </label>

                        <label class="provider-card">
                            <input type="radio" name="method" value="Nagad" required>
                            <div class="provider-check">✓</div>
                            <div class="provider-content">
                                <div class="provider-logo"><img src="nagad.png" alt="Nagad" style="height: 48px; width: auto;"></div>
                                <h3>Nagad</h3>
                                <p>Government Backed MFS</p>
                            </div>
                        </label>
                    </div>

                    <!-- Amount Section -->
                    <?php $locked = (in_array($return_to, ['booking', 'food']) && $required_amount > 0); ?>
                    <div class="amount-section">
                        <div class="amount-input-group">
                            <label for="amount">Recharge Amount (৳)
                                <?php if ($locked): ?>
                                    <span style="font-size: 11px; color: #D81B60; margin-left: 6px;">🔒 Minimum required for booking</span>
                                <?php endif; ?>
                            </label>
                            <input type="number" id="amount" name="amount" placeholder="Enter amount" 
                                   min="<?php echo $locked ? $required_amount : 10; ?>" max="100000" step="1" required
                                   value="<?php echo $suggested_amount > 0 ? $suggested_amount : ''; ?>"
                                   <?php if ($locked): ?>readonly style="opacity: 0.75; cursor: not-allowed;"<?php endif; ?>>
                        </div>
                    </div>

                    <!-- Recharge Button -->
                    <button type="submit" class="btn-recharge" id="rechargeBtn">
                        Proceed to Payment
                    </button>
                </form>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333; font-size: 12px; color: #aaa; line-height: 1.6;">
                    <strong>📌 Test Mode:</strong><br>
                    • Account: Any 10-11 digit mobile<br>
                    • OTP: Any 6 digits<br>
                    • PIN: Use <strong>1234</strong>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="section">
                <div class="section-title">
                    <div class="section-title-icon">📊</div>
                    Recent Recharges
                </div>

                <?php if (count($recent_transactions) > 0): ?>
                    <div class="transaction-list">
                        <?php foreach ($recent_transactions as $tx): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <div class="transaction-method">
                                        <?php echo htmlspecialchars($tx['method']); ?>
                                    </div>
                                    <div class="transaction-date">
                                        <?php echo date('M d, Y H:i A', strtotime($tx['date'])); ?>
                                    </div>
                                </div>
                                <div class="transaction-amount">
                                    <div class="amount">+৳ <?php echo number_format($tx['amount'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>💭 No recharge history yet</p>
                        <p style="font-size: 12px;">Your recharge transactions will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="section" style="margin-top: 30px;">
            <div class="section-title">
                <div class="section-title-icon">❓</div>
                Frequently Asked Questions
            </div>

            <div style="display: grid; gap: 20px;">
                <div>
                    <h4 style="margin-bottom: 8px; color: #D81B60;">How long does it take to receive my recharge?</h4>
                    <p style="font-size: 13px; color: #aaa; line-height: 1.6;">Recharges are processed instantly. Your wallet balance will be updated within seconds of successful payment.</p>
                </div>

                <div>
                    <h4 style="margin-bottom: 8px; color: #D81B60;">Is there a minimum recharge amount?</h4>
                    <p style="font-size: 13px; color: #aaa; line-height: 1.6;">The minimum recharge amount is ৳10. You can recharge up to ৳100,000 at a time.</p>
                </div>

                <div>
                    <h4 style="margin-bottom: 8px; color: #D81B60;">Can I refund my wallet balance?</h4>
                    <p style="font-size: 13px; color: #aaa; line-height: 1.6;">Wallet balances cannot be refunded, but they will be automatically refunded if you cancel a booking or order.</p>
                </div>

                <div>
                    <h4 style="margin-bottom: 8px; color: #D81B60;">Which payment methods are supported?</h4>
                    <p style="font-size: 13px; color: #aaa; line-height: 1.6;">Currently, we support bKash and Nagad. More payment methods will be added soon.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('rechargeForm');
        const methodRadios = document.querySelectorAll('input[name="method"]');
        const amountInput = document.getElementById('amount');
        const rechargeBtn = document.getElementById('rechargeBtn');
        <?php if ($locked): ?>
        const lockedAmount = <?php echo $required_amount; ?>;
        <?php endif; ?>

        // Update button state
        function updateButtonState() {
            const isMethodSelected = Array.from(methodRadios).some(r => r.checked);
            const isAmountValid = amountInput.value && parseInt(amountInput.value) >= 10;
            rechargeBtn.disabled = !(isMethodSelected && isAmountValid);
        }

        methodRadios.forEach(radio => {
            radio.addEventListener('change', updateButtonState);
        });

        amountInput.addEventListener('input', updateButtonState);

        <?php if ($locked): ?>
        // Prevent devtools tampering — restore locked amount before submit
        form.addEventListener('submit', function(e) {
            amountInput.removeAttribute('readonly');
            amountInput.value = lockedAmount;
        });
        <?php endif; ?>

        // Update initial button state
        updateButtonState();
    </script>
</body>
</html>