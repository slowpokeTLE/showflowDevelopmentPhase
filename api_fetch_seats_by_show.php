<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

if (!hasRole(ROLE_USER)) {
    jsonResponse('error', 'Unauthorized');
}

$s_id = intval($_GET['s_id'] ?? 0);

if ($s_id <= 0) {
    jsonResponse('error', 'Show ID required');
}

// Get hall dimensions for this show
$hall_query = "
    SELECT h.total_rows, h.total_columns
    FROM show_schedule s
    JOIN hall h ON s.h_id = h.h_id
    WHERE s.s_id = ?
";

$stmt = $conn->prepare($hall_query);
$stmt->bind_param("i", $s_id);
$stmt->execute();
$hall_result = $stmt->get_result();

if ($hall_result->num_rows === 0) {
    jsonResponse('error', 'Show not found');
}

$hall = $hall_result->fetch_assoc();
$stmt->close();

// Get all bookings for this show — seats stored as comma-separated "row-col" text
$seats_query = "
    SELECT seat_numbers
    FROM booking
    WHERE s_id = ? AND status = 'Confirmed'
";

$stmt = $conn->prepare($seats_query);
$stmt->bind_param("i", $s_id);
$stmt->execute();
$seats_result = $stmt->get_result();

$booked_seats = [];
while ($row = $seats_result->fetch_assoc()) {
    // seat_numbers format: "0-0,0-1,1-5"
    foreach (explode(',', $row['seat_numbers']) as $seat) {
        $parts = explode('-', trim($seat));
        if (count($parts) === 2) {
            $booked_seats[] = [
                'row' => (int)$parts[0],
                'col' => (int)$parts[1]
            ];
        }
    }
}
$stmt->close();

jsonResponse('success', 'Seats fetched', [
    'total_rows'    => (int)$hall['total_rows'],
    'total_columns' => (int)$hall['total_columns'],
    'booked_seats'  => $booked_seats
]);
?>