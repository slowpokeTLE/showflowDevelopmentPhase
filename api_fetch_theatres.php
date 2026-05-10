<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Get all theatres
$query = "SELECT t_id, theatre_name, location FROM theatre ORDER BY theatre_name ASC";
$result = $conn->query($query);

$theatres = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $theatres[] = $row;
    }
}

jsonResponse('success', 'Theatres fetched', ['theatres' => $theatres]);
?>
