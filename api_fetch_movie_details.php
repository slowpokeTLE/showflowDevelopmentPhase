<?php
session_start();
require 'db.php';
require 'session_handler.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['u_id'])) {
        throw new Exception('Unauthorized');
    }

    $mov_id = $_GET['mov_id'] ?? null;

    if (!$mov_id) {
        throw new Exception('Movie ID required');
    }

    // Get movie details
    $stmt = $conn->prepare("SELECT * FROM movie WHERE mov_id = ?");
    $stmt->bind_param("i", $mov_id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$movie) {
        throw new Exception('Movie not found');
    }

    // Get reviews for this movie
    $stmt = $conn->prepare("
        SELECT r.rev_id, r.rating, r.comment as review_text, u.name as u_name, r.created_at
        FROM review r
        JOIN user u ON r.u_id = u.u_id
        WHERE r.mov_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $mov_id);
    $stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calculate average rating
    $avg_rating = 0;
    if (count($reviews) > 0) {
        $total_rating = 0;
        foreach ($reviews as $review) {
            $total_rating += $review['rating'];
        }
        $avg_rating = round($total_rating / count($reviews), 1);
    }

    // Check if user already reviewed
    $user_id = $_SESSION['u_id'];
    $user_review = null;
    $stmt = $conn->prepare("SELECT * FROM review WHERE mov_id = ? AND u_id = ?");
    $stmt->bind_param("is", $mov_id, $user_id);
    $stmt->execute();
    $user_review = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'movie' => $movie,
        'reviews' => $reviews,
        'avg_rating' => $avg_rating,
        'user_review' => $user_review,
        'total_reviews' => count($reviews)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
