<?php
require 'db.php';

header('Content-Type: application/json');

$mov_id = isset($_GET['mov_id']) ? intval($_GET['mov_id']) : 0;

if ($mov_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid movie ID']);
    exit;
}

try {
    // Fetch movie details
    $movie_query = "SELECT mov_id, mov_name, mov_poster, mov_trailer, mov_synopsis, 
                           mov_genre, mov_duration, mov_release_date, mov_description
                    FROM movie WHERE mov_id = ?";
    
    $stmt = $conn->prepare($movie_query);
    $stmt->bind_param("i", $mov_id);
    $stmt->execute();
    $movie_result = $stmt->get_result();
    
    if ($movie_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Movie not found']);
        exit;
    }
    
    $movie = $movie_result->fetch_assoc();
    
    // Fetch reviews with user names
    $reviews_query = "SELECT r.rev_id, r.rating, r.comment as review_text, 
                            u.name as u_name, r.created_at
                     FROM review r
                     JOIN user u ON r.u_id = u.u_id
                     WHERE r.mov_id = ?
                     ORDER BY r.created_at DESC
                     LIMIT 5";
    
    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param("i", $mov_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    
    $reviews = [];
    $total_rating = 0;
    $review_count = 0;
    
    while($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
        $total_rating += $row['rating'];
        $review_count++;
    }
    
    $avg_rating = $review_count > 0 ? $total_rating / $review_count : 0;
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'movie' => $movie,
        'reviews' => $reviews,
        'avg_rating' => round($avg_rating, 1),
        'total_reviews' => $review_count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
?>
