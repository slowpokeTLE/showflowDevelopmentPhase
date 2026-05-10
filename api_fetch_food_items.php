<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

$t_id = intval($_GET['t_id'] ?? 0);

if ($t_id <= 0) {
    jsonResponse('error', 'Theatre ID required');
}

$query = "SELECT food_id, food_name, price FROM food_item WHERE t_id = ? ORDER BY food_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $t_id);
$stmt->execute();

$result = $stmt->get_result();
$foods = [];

while ($row = $result->fetch_assoc()) {
    $foods[] = $row;
}

$stmt->close();

jsonResponse('success', 'Food items fetched', ['items' => $foods]);
?>
