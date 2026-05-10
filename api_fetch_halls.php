<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Get all halls for manager's theatre
$t_id = intval($_GET['t_id'] ?? 0);

if ($t_id <= 0) {
    jsonResponse('error', 'Theatre ID required');
}

$query = "SELECT h_id, hall_name, total_rows, total_columns FROM hall WHERE t_id = ? ORDER BY hall_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $t_id);
$stmt->execute();

$result = $stmt->get_result();
$halls = [];

while($row = $result->fetch_assoc()) {
    $halls[] = $row;
}

$stmt->close();

jsonResponse('success', 'Halls fetched', ['halls' => $halls]);
?>
