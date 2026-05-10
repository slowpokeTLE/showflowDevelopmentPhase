<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Get theatres showing a specific movie
$mov_id = intval($_GET['mov_id'] ?? 0);

if ($mov_id <= 0) {
    jsonResponse('error', 'Movie ID required');
}

$query = "
    SELECT DISTINCT t.t_id, t.theatre_name, t.location
    FROM theatre t
    JOIN show_schedule s ON t.t_id = s.t_id
    WHERE s.mov_id = ?
    ORDER BY t.theatre_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mov_id);
$stmt->execute();

$result = $stmt->get_result();
$theatres = [];

while ($row = $result->fetch_assoc()) {
    $theatres[] = $row;
}

$stmt->close();

jsonResponse('success', 'Theatres fetched', ['theatres' => $theatres]);
?>
