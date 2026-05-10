<?php
require 'session_handler.php';

header('Content-Type: application/json');

echo json_encode([
    'isLoggedIn' => isLoggedIn(),
    'userId' => isLoggedIn() ? $_SESSION['user_id'] : null,
    'role' => isLoggedIn() ? $_SESSION['role'] : null
]);
?>
