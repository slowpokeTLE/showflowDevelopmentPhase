<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Get show times for a specific date
$mov_id = intval($_GET['mov_id'] ?? 0);
$t_id = intval($_GET['t_id'] ?? 0);
$show_date = trim($_GET['show_date'] ?? '');

if ($mov_id <= 0 || $t_id <= 0 || empty($show_date)) {
    jsonResponse('error', 'Movie ID, Theatre ID, and Show Date required');
}

$query = "
    SELECT s.s_id, s.show_time, s.ticket_price, h.hall_name, h.total_rows, h.total_columns,
           COUNT(b.book_id) as booked_seats
    FROM show_schedule s
    JOIN hall h ON s.h_id = h.h_id
    LEFT JOIN booking b ON s.s_id = b.s_id
    WHERE s.mov_id = ? AND s.t_id = ? AND DATE(s.show_date) = ?
      AND (DATE(s.show_date) > CURDATE() OR s.show_time > CURTIME())
    GROUP BY s.s_id
    ORDER BY s.show_time ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $mov_id, $t_id, $show_date);
$stmt->execute();

$result = $stmt->get_result();
$shows = [];

while ($row = $result->fetch_assoc()) {
    $total_seats = $row['total_rows'] * $row['total_columns'];
    $available_seats = $total_seats - intval($row['booked_seats']);
    
    $shows[] = [
        's_id' => $row['s_id'],
        'show_time' => $row['show_time'],
        'formatted_time' => date('h:i A', strtotime($row['show_time'])),
        'ticket_price' => $row['ticket_price'],
        'hall_name' => $row['hall_name'],
        'total_seats' => $total_seats,
        'available_seats' => $available_seats,
        'booked_seats' => intval($row['booked_seats'])
    ];
}

$stmt->close();

jsonResponse('success', 'Shows fetched', ['shows' => $shows]);
?>