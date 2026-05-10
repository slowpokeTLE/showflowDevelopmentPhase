<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

if (!isset($_SESSION['booking_confirmation'])) {
    header('Location: index.php');
    exit();
}

$booking = $_SESSION['booking_confirmation'];
unset($_SESSION['booking_confirmation']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
            background: var(--bg-primary);
        }

        .confirmation-container {
            width: 100%;
            max-width: 600px;
            background: var(--bg-secondary);
            border: 2px solid var(--accent-red);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 1rem;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent-red);
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .ticket-details {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: left;
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .detail-value {
            font-weight: bold;
            font-size: 14px;
        }

        .booking-id {
            background: rgba(229, 9, 20, 0.1);
            border: 1px solid var(--accent-red);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .booking-id-label {
            color: var(--text-secondary);
            font-size: 12px;
            margin-bottom: 0.5rem;
        }

        .booking-id-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent-red);
            font-family: monospace;
        }

        .seats-display {
            background: var(--bg-primary);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .seats-label {
            color: var(--text-secondary);
            font-size: 12px;
            margin-bottom: 0.5rem;
        }

        .seats-list {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .seat-badge {
            background: var(--accent-red);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }

        .price-breakdown {
            background: var(--bg-primary);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            color: var(--text-primary);
            font-size: 14px;
        }

        .price-row.total {
            border-top: 1px solid var(--border-color);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            font-weight: bold;
            color: var(--accent-red);
            font-size: 18px;
        }

        .actions {
            display: flex;
            gap: 1rem;
            flex-direction: column;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--accent-red);
            color: white;
        }

        .btn-primary:hover {
            background: #d40812;
            transform: scale(1.02);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .confirmation-message {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 1rem;
        }

        @media (max-width: 640px) {
            .confirmation-container {
                padding: 1.5rem;
            }

            .title {
                font-size: 24px;
            }

            .seat-badge {
                font-size: 12px;
                padding: 0.4rem 0.6rem;
            }

            .detail-row {
                padding: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-icon">✅</div>
        <div class="title">Booking Confirmed!</div>
        <div class="subtitle">Your reservation has been successfully completed</div>

        <!-- Booking ID -->
        <div class="booking-id">
            <div class="booking-id-label">Booking Reference ID</div>
            <div class="booking-id-number">#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>

        <!-- Ticket Details -->
        <div class="ticket-details">
            <div class="detail-row">
                <span class="detail-label">🎬 Movie</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['show']['mov_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">🏢 Theatre</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['show']['theatre_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">🎭 Hall</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['show']['hall_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">📅 Date</span>
                <span class="detail-value"><?php echo date('d M Y', strtotime($booking['show']['show_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">🕐 Time</span>
                <span class="detail-value"><?php echo date('h:i A', strtotime($booking['show']['show_time'])); ?></span>
            </div>
        </div>

        <!-- Seats -->
        <div class="seats-display">
            <div class="seats-label">Your Seats</div>
            <div class="seats-list">
                <?php foreach ($booking['seats'] as $seat): ?>
                    <div class="seat-badge">
                        <?php echo htmlspecialchars($seat['label']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Price Breakdown -->
        <div class="price-breakdown">
            <div class="price-row total">
                <span><?php echo count($booking['seats']); ?> Seat(s) × ৳<?php echo number_format($booking['show']['ticket_price'], 2); ?></span>
                <span>৳<?php echo number_format($booking['total'], 2); ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions">
            <button class="btn btn-primary" onclick="window.location.href='user-profile.php'">
                📋 View Your Bookings
            </button>
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                🎬 Home
            </button>
        </div>

        <div class="confirmation-message">
            <p>A confirmation has been sent to your registered email.</p>
            <p>Please present your Booking ID at the theatre counter.</p>
        </div>
    </div>
</body>
</html>