<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Only logged-in users can view their tickets
if (!isset($_SESSION['u_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit();
}

$u_id = $_SESSION['u_id'];
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    echo 'Invalid booking id';
    exit();
}

$query = "
    SELECT b.book_id, b.total_amount, b.seat_numbers, b.booking_date,
           s.show_date, s.show_time, m.mov_name, t.theatre_name, h.hall_name
    FROM booking b
    JOIN show_schedule s ON b.s_id = s.s_id
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN theatre t ON s.t_id = t.t_id
    JOIN hall h ON s.h_id = h.h_id
    WHERE b.book_id = ? AND b.u_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $book_id, $u_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo 'Booking not found or access denied.';
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

// Prepare values
$booking_ref = 'BOOK-' . str_pad($booking['book_id'], 6, '0', STR_PAD_LEFT);
$movie = htmlspecialchars($booking['mov_name']);
$theatre = htmlspecialchars($booking['theatre_name']);
$hall = htmlspecialchars($booking['hall_name']);
$show_date = date('d M Y', strtotime($booking['show_date']));
$show_time = date('h:i A', strtotime($booking['show_time']));
$seats = htmlspecialchars($booking['seat_numbers']);
$amount = number_format($booking['total_amount'], 2);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ticket - <?php echo $booking_ref; ?></title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f7fb; color: #111827; padding: 20px; }
        .ticket { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 6px 18px rgba(15,23,42,0.08); padding: 24px; }
        .brand { display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; }
        .brand .title { font-size: 20px; font-weight: 700; color: #B91C1C; }
        .ref { font-family: monospace; background: #111827; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 13px; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
        .label { font-size: 12px; color: #6b7280; }
        .value { font-size: 16px; font-weight: 600; color: #111827; }
        .seats { font-size: 14px; color: #111827; font-weight: 600; }
        .amount { font-size: 20px; color: #B91C1C; font-weight: 800; }
        .footer { margin-top: 18px; font-size: 12px; color: #6b7280; }
        .print-btn { display:inline-block; margin-top:16px; padding:8px 14px; background:#111827; color:white; border-radius:6px; text-decoration:none; }
        @media print { .print-btn { display:none } body { background: #fff } }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="brand">
            <div>
                <div class="title">🎬 ShowFlow</div>
                <div style="font-size:13px;color:#6b7280;">Booking Confirmation</div>
            </div>
            <div class="ref"><?php echo $booking_ref; ?></div>
        </div>

        <div class="details">
            <div>
                <div class="label">Movie</div>
                <div class="value"><?php echo $movie; ?></div>
            </div>
            <div>
                <div class="label">Theatre</div>
                <div class="value"><?php echo $theatre; ?></div>
            </div>

            <div>
                <div class="label">Hall</div>
                <div class="value"><?php echo $hall; ?></div>
            </div>
            <div>
                <div class="label">Show</div>
                <div class="value"><?php echo $show_date . ' • ' . $show_time; ?></div>
            </div>

            <div>
                <div class="label">Seats</div>
                <div class="seats"><?php echo $seats; ?></div>
            </div>
            <div>
                <div class="label">Amount Paid</div>
                <div class="amount">₹<?php echo $amount; ?></div>
            </div>
        </div>

        <div class="footer">
            <div>Present this ticket at the theatre entrance.</div>
            <div style="margin-top:8px;">Booking date: <?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></div>
        </div>

        <a class="print-btn" href="#" onclick="window.print();return false;">Print / Save as PDF</a>
    </div>

</body>
</html>
