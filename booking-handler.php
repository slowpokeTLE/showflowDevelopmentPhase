<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];
$s_id = intval($_GET['s_id'] ?? 0);
$seats_param = trim($_GET['seats'] ?? '');

if ($s_id <= 0 || empty($seats_param)) {
    header('Location: user-dashboard.php');
    exit();
}

// Parse and validate seat data (format: "0-0,0-1,1-5")
$seats = [];
foreach (explode(',', $seats_param) as $seat) {
    $parts = explode('-', trim($seat));
    if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
        $seats[] = [
            'row' => intval($parts[0]),
            'col' => intval($parts[1])
        ];
    }
}

if (count($seats) === 0) {
    header('Location: user-dashboard.php');
    exit();
}

// Get show details
$show_query = "
    SELECT s.s_id, s.ticket_price, s.show_date, s.show_time,
           m.mov_name, h.hall_name, t.theatre_name
    FROM show_schedule s
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN hall h ON s.h_id = h.h_id
    JOIN theatre t ON s.t_id = t.t_id
    WHERE s.s_id = ?
";
$stmt = $conn->prepare($show_query);
$stmt->bind_param("i", $s_id);
$stmt->execute();
$show_result = $stmt->get_result();

if ($show_result->num_rows === 0) {
    header('Location: user-dashboard.php');
    exit();
}
$show = $show_result->fetch_assoc();
$stmt->close();

// Check none of the requested seats are already booked
$existing_query = "SELECT seat_numbers FROM booking WHERE s_id = ? AND status = 'Confirmed'";
$stmt = $conn->prepare($existing_query);
$stmt->bind_param("i", $s_id);
$stmt->execute();
$existing_result = $stmt->get_result();

$already_booked = [];
while ($row = $existing_result->fetch_assoc()) {
    foreach (explode(',', $row['seat_numbers']) as $seat) {
        $already_booked[] = trim($seat);
    }
}
$stmt->close();

foreach ($seats as $seat) {
    if (in_array($seat['row'] . '-' . $seat['col'], $already_booked)) {
        $_SESSION['booking_error'] = 'One or more selected seats were just booked. Please select again.';
        header('Location: booking.php?movie=' . $s_id);
        exit();
    }
}

// Calculate price
$total_amount = count($seats) * $show['ticket_price'];

// Check user's wallet balance
$balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
$stmt = $conn->prepare($balance_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$balance_result = $stmt->get_result()->fetch_assoc();
$current_balance = (float)($balance_result['current_balance'] ?? 0);
$stmt->close();

// Check if balance is sufficient
if ($current_balance < $total_amount) {
    $required_recharge = ceil($total_amount - $current_balance);
    // Store booking details for auto-confirmation after recharge
    $_SESSION['pending_booking'] = [
        's_id' => $s_id,
        'seats' => $seats,
        'show' => $show,
        'total_amount' => $total_amount
    ];
    header('Location: recharge.php?required_amount=' . $required_recharge . '&return_to=booking&movie_id=' . $s_id);
    exit();
}

// seat_numbers stored as "row-col,row-col" text
$seat_numbers = implode(',', array_map(fn($s) => $s['row'] . '-' . $s['col'], $seats));

// Insert booking and deduct from wallet
$conn->begin_transaction();
try {
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

    // Build seat label array for confirmation display (e.g. "A1", "B3")
    $seat_labels = [];
    foreach ($seats as $s) {
        $seat_labels[] = [
            'row' => $s['row'],
            'col' => $s['col'],
            'label' => chr(65 + $s['row']) . ($s['col'] + 1)
        ];
    }

    $_SESSION['booking_confirmation'] = [
        'booking_id' => $book_id,
        'show'       => $show,
        'seats'      => $seat_labels,
        'total'      => $total_amount
    ];

    header('Location: booking-confirmation.php');
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['booking_error'] = 'Booking failed. Please try again. Error: ' . $e->getMessage();
    header('Location: booking.php');
    exit();
}
?>