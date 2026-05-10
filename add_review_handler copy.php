<?php
session_start();
require 'db.php';
require 'session_handler.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['u_id'])) {
        throw new Exception('User not logged in');
    }
    
    $action = $_POST['action'] ?? null;
    
    if ($action !== 'add_review') {
        throw new Exception('Invalid action');
    }

    $mov_id = $_POST['mov_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $review_text = $_POST['review_text'] ?? null;
    $user_id = $_SESSION['u_id'] ?? null;

    if (!$mov_id || !$rating || !$review_text || !$user_id) {
        throw new Exception('Missing required fields');
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
        $stmt->bind_param("isii", $mov_id, $user_id, $rating, $review_text);
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
