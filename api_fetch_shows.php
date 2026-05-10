<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

$show_date = trim($_GET['show_date'] ?? '');

if (empty($show_date)) {
    jsonResponse('error', 'Show date required');
}

$query = "
    SELECT s.show_time, COUNT(b.book_id) as booked_seats, h.total_rows, h.total_columns
    FROM show_schedule s
    LEFT JOIN booking b ON s.s_id = b.s_id
    LEFT JOIN hall h ON s.h_id = h.h_id
    WHERE DATE(s.show_date) = ?
    GROUP BY s.s_id
    ORDER BY s.show_time ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $show_date);
$stmt->execute();

$result = $stmt->get_result();
$shows = [];

while($row = $result->fetch_assoc()) {
    $shows[] = $row;
}

$stmt->close();

jsonResponse('success', 'Shows fetched', ['shows' => $shows]);
?>
