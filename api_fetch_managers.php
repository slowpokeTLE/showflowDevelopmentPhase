<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

$t_id = intval($_GET['t_id'] ?? 0);

if ($t_id <= 0) {
    jsonResponse('error', 'Theatre ID required');
}

// Get managers for specific theatre
$query = "SELECT m_id, name, contact, t_id FROM manager WHERE t_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $t_id);
$stmt->execute();

$result = $stmt->get_result();
$managers = [];

while($row = $result->fetch_assoc()) {
    $managers[] = $row;
}

$stmt->close();

jsonResponse('success', 'Managers fetched', ['managers' => $managers]);
?>
