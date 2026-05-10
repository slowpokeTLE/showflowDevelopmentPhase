<?php
// Database Connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'showflow2';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }
    
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection error: ' . $e->getMessage()]));
}
?>
