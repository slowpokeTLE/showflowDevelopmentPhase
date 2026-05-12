<?php
session_start();
require 'db.php';
require 'session_handler.php';

requireRole(['ROLE_USER']);

$mov_id = $_GET['mov_id'] ?? $_GET['movie_id'] ?? null;

if (!$mov_id) {
    header('Location: user-dashboard.php');
    exit;
}

// Get movie details
$stmt = $conn->prepare("SELECT * FROM movie WHERE mov_id = ?");
$stmt->bind_param("i", $mov_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) {
    header('Location: user-dashboard.php');
    exit;
}

// Get reviews for this movie
$stmt = $conn->prepare("
    SELECT r.rev_id, r.rating, r.comment as review_text, u.name as u_name, r.created_at
    FROM review r
    JOIN user u ON r.u_id = u.u_id
    WHERE r.mov_id = ?
    ORDER BY r.created_at DESC
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
    $avg_rating = $total_rating / count($reviews);
}

// Check if user already reviewed this movie
$user_id = $_SESSION['u_id'] ?? null;
$user_review = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM review WHERE mov_id = ? AND u_id = ?");
    $stmt->bind_param("is", $mov_id, $user_id);
    $stmt->execute();
    $user_review = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['mov_name']); ?> - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0f0f0f; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; gap: 30px; margin-bottom: 40px; align-items: flex-start; }
        .movie-poster { flex-shrink: 0; width: 250px; }
        .movie-poster img { width: 100%; border-radius: 8px; box-shadow: 0 8px 24px rgba(229, 9, 20, 0.3); }
        .movie-poster-placeholder { width: 100%; aspect-ratio: 2/3; background: #1a1a1a; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 64px; border: 2px solid #333; }
        .movie-info { flex: 1; }
        .movie-title { font-size: 36px; font-weight: 700; margin-bottom: 15px; }
        .movie-meta { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; color: #999; font-size: 14px; }
        .rating-section { margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
        .rating-value { font-size: 24px; font-weight: 700; color: #e50914; }
        .stars { display: flex; gap: 5px; }
        .star { font-size: 18px; color: #e50914; }
        .synopsis { background: #1a1a1a; border-left: 4px solid #e50914; padding: 20px; border-radius: 4px; margin: 20px 0; line-height: 1.6; color: #ddd; }
        .button-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        .btn-primary { background: #e50914; color: white; }
        .btn-primary:hover { background: #c1040b; box-shadow: 0 0 20px rgba(229, 9, 20, 0.5); }
        .btn-secondary { background: #333; color: white; border: 2px solid #555; }
        .btn-secondary:hover { border-color: #e50914; color: #e50914; }
        .btn-back { background: #1a1a1a; color: #999; border: 1px solid #333; padding: 8px 16px; font-size: 14px; margin-bottom: 20px; }
        .btn-back:hover { background: #333; color: #fff; }
        .reviews-section { margin-top: 50px; }
        .section-title { font-size: 24px; font-weight: 600; margin-bottom: 30px; padding-bottom: 10px; border-bottom: 2px solid #e50914; display: inline-block; }
        .add-review-form { background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #fff; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; background: #0f0f0f; border: 1px solid #333; color: #fff; border-radius: 4px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .rating-input { display: flex; gap: 10px; font-size: 32px; cursor: pointer; }
        .rating-star { cursor: pointer; color: #333; transition: all 0.2s; }
        .rating-star:hover { color: #e50914; transform: scale(1.2); }
        .rating-star.selected { color: #e50914; }
        .reviews-list { display: grid; gap: 20px; }
        .review-card { background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 8px; transition: all 0.3s; }
        .review-card:hover { border-color: #e50914; box-shadow: 0 0 20px rgba(229, 9, 20, 0.1); }
        .review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .reviewer-name { font-weight: 600; font-size: 16px; color: #fff; }
        .review-date { font-size: 12px; color: #666; margin-top: 3px; }
        .review-rating { display: flex; gap: 3px; }
        .review-text { color: #ccc; line-height: 1.5; }
        .no-reviews { text-align: center; padding: 40px; color: #666; }
        @media (max-width: 768px) { .header { flex-direction: column; } .movie-title { font-size: 24px; } .button-group { flex-direction: column; } .btn { width: 100%; } }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn btn-back" onclick="window.history.back()">← Back to Movies</button>

        <div class="header">
            <div class="movie-poster">
                <?php if (!empty($movie['mov_poster'])): ?>
                    <img src="<?php echo htmlspecialchars($movie['mov_poster']); ?>" 
                         alt="<?php echo htmlspecialchars($movie['mov_name']); ?>">
                <?php else: ?>
                    <div class="movie-poster-placeholder">🎬</div>
                <?php endif; ?>
            </div>

            <div class="movie-info">
                <h1 class="movie-title"><?php echo htmlspecialchars($movie['mov_name']); ?></h1>

                <div class="movie-meta">
                    <?php if (!empty($movie['mov_genre'])): ?>
                        <span>📽️ <?php echo htmlspecialchars($movie['mov_genre']); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($movie['mov_duration'])): ?>
                        <span>⏱️ <?php echo $movie['mov_duration']; ?> min</span>
                    <?php endif; ?>

                    <?php if (!empty($movie['mov_release_date'])): ?>
                        <span>📅 <?php echo date('M d, Y', strtotime($movie['mov_release_date'])); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($avg_rating > 0): ?>
                    <div class="rating-section">
                        <div class="rating-value"><?php echo number_format($avg_rating, 1); ?>/5</div>
                        <div style="font-size: 12px; color: #999;">
                            <?php 
                            $full_stars = (int)$avg_rating;
                            for ($i = 0; $i < 5; $i++) {
                                echo $i < $full_stars ? '⭐' : '<span style="opacity: 0.3;">⭐</span>';
                            }
                            ?><br><?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($movie['mov_synopsis'])): ?>
                    <div class="synopsis">
                        <strong>Synopsis:</strong><br>
                        <?php echo nl2br(htmlspecialchars($movie['mov_synopsis'])); ?>
                    </div>
                <?php endif; ?>

                <div class="button-group">
                    <button class="btn btn-primary" onclick="document.getElementById('addReviewForm').scrollIntoView({ behavior: 'smooth' })">
                        ✏️ Add Review
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.href='booking.php?movie=<?php echo $mov_id; ?>'">
                        🎫 Buy Tickets
                    </button>
                    <?php if (!empty($movie['mov_trailer'])): ?>
                        <button class="btn btn-secondary" onclick="window.open('<?php echo htmlspecialchars($movie['mov_trailer']); ?>', '_blank')">
                            ▶️ Watch Trailer
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <h2 class="section-title">💬 User Reviews</h2>

            <?php if ($user_id): ?>
                <div id="addReviewForm" class="add-review-form">
                    <h3 style="margin-bottom: 20px;">
                        <?php echo $user_review ? '✏️ Update Your Review' : '✏️ Add Your Review'; ?>
                    </h3>

                    <form onsubmit="submitReview(event)">
                        <div class="form-group">
                            <label>Your Rating (1-5 stars)</label>
                            <div class="rating-input" id="ratingInput">
                                <span class="rating-star" data-rating="1" onclick="setRating(1)">★</span>
                                <span class="rating-star" data-rating="2" onclick="setRating(2)">★</span>
                                <span class="rating-star" data-rating="3" onclick="setRating(3)">★</span>
                                <span class="rating-star" data-rating="4" onclick="setRating(4)">★</span>
                                <span class="rating-star" data-rating="5" onclick="setRating(5)">★</span>
                            </div>
                            <input type="hidden" id="ratingValue" name="rating" value="<?php echo $user_review['rating'] ?? 0; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Your Review</label>
                            <textarea name="review_text" placeholder="Share your thoughts about this movie..." required><?php echo $user_review ? htmlspecialchars($user_review['comment'] ?? '') : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo $user_review ? 'Update Review' : 'Post Review'; ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 30px;">
                    <p style="color: #999;">Please <a href="user-login.php" style="color: #e50914; text-decoration: none; font-weight: 600;">login</a> to add a review</p>
                </div>
            <?php endif; ?>

            <h3 style="margin-bottom: 20px;">All Reviews (<?php echo count($reviews); ?>)</h3>
            <?php if (count($reviews) > 0): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['u_name']); ?></div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php 
                                    for ($i = 0; $i < 5; $i++) {
                                        echo $i < (int)$review['rating'] ? '⭐' : '<span style="opacity: 0.3;">⭐</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <p>📝 No reviews yet. Be the first to review this movie!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let selectedRating = <?php echo $user_review['rating'] ?? 0; ?>;

        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('ratingValue').value = rating;
            
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }

        // Initialize rating display
        if (selectedRating > 0) {
            setRating(selectedRating);
        }

        function submitReview(event) {
            event.preventDefault();
            
            const form = event.target;
            const rating = document.getElementById('ratingValue').value;
            const reviewText = form.querySelector('textarea[name="review_text"]').value;
            
            // Build parameters explicitly to guarantee they are sent
            const params = new URLSearchParams();
            params.append('action', 'add_review');
            params.append('mov_id', '<?php echo htmlspecialchars($mov_id); ?>'); // Added quotes to prevent JS errors
            params.append('rating', rating);
            params.append('review_text', reviewText);

            if (!rating || rating === '0') {
                alert('Please select a rating');
                return;
            }

            // Send as standard url-encoded form data
            fetch('add_review_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = `movie-details.php?mov_id=<?php echo htmlspecialchars($mov_id); ?>`;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('Failed to submit review');
            });
        }
    </script>
</body>
</html>