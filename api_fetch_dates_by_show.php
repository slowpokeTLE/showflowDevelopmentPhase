<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Get available show dates for a movie at a theatre
$mov_id = intval($_GET['mov_id'] ?? 0);
$t_id = intval($_GET['t_id'] ?? 0);

if ($mov_id <= 0 || $t_id <= 0) {
    jsonResponse('error', 'Movie ID and Theatre ID required');
}

$query = "
    SELECT DISTINCT DATE(s.show_date) AS show_date
    FROM show_schedule s
    WHERE s.mov_id = ? AND s.t_id = ?
      AND (
            DATE(s.show_date) > CURDATE()
         OR (DATE(s.show_date) = CURDATE() AND s.show_time > CURTIME())
      )
    ORDER BY show_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $mov_id, $t_id);
$stmt->execute();

$result = $stmt->get_result();
$dates = [];

while ($row = $result->fetch_assoc()) {
    $dates[] = [
        'show_date' => $row['show_date'],
        'formatted_date' => date('d M Y', strtotime($row['show_date']))
    ];
}

$stmt->close();

jsonResponse('success', 'Dates fetched', ['dates' => $dates]);
?>