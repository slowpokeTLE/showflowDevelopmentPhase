<?php
require 'db.php';
require 'session_handler.php';

header('Content-Type: application/json');

try {
    // 1. Guard against direct browser access or redirect GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid Request Method (' . $_SERVER['REQUEST_METHOD'] . '). You cannot open this file directly in the browser.');
    }

    // 2. Fix: Use 'u_id' to match your user-login.php session variables
    if (!isset($_SESSION['u_id'])) {
        throw new Exception('User not logged in. Session is missing.');
    }
    
    $action = $_POST['action'] ?? null;
    
    // 3. Diagnostic error: Tell us exactly what $_POST received
    if ($action !== 'add_review') {
        $received = json_encode($_POST);
        throw new Exception("Invalid action. Expected 'add_review' but received: " . ($action ? $action : 'NULL') . ". Full POST data received: " . $received);
    }

    $mov_id = $_POST['mov_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $review_text = $_POST['review_text'] ?? null;
    
    // Fix: Use 'u_id' here as well
    $user_id = $_SESSION['u_id'] ?? null;

    // 4. Diagnostic error for missing fields
    if (!$mov_id || !$rating || !$review_text || !$user_id) {
        throw new Exception("Missing required fields. mov_id: $mov_id, rating: $rating, user_id: $user_id, review_text present: " . ($review_text ? 'Yes' : 'No'));
    }

    $rating = (int)$rating;
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }

    $review_text = trim($review_text);
    if (strlen($review_text) < 10) {
        throw new Exception('Review must be at least 10 characters');
    }

    // Check if movie exists
    $stmt = $conn->prepare("SELECT mov_id FROM movie WHERE mov_id = ?");
    $stmt->bind_param("i", $mov_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Movie not found');
    }
    $stmt->close();

    // Check if user already has a review
    $stmt = $conn->prepare("SELECT rev_id FROM review WHERE mov_id = ? AND u_id = ?");
    $stmt->bind_param("is", $mov_id, $user_id);
    $stmt->execute();
    $existing_review = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing_review) {
        // Update existing review
        $stmt = $conn->prepare("UPDATE review SET rating = ?, comment = ?, created_at = NOW() WHERE rev_id = ?");
        $stmt->bind_param("isi", $rating, $review_text, $existing_review['rev_id']);
    } else {
        // Insert new review
        $stmt = $conn->prepare("INSERT INTO review (mov_id, u_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $mov_id, $user_id, $rating, $review_text);
    }

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Review submitted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>