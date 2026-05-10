<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';
require 'wallet-utils.php';

// Fetch current movies with shows
$query = "SELECT DISTINCT m.mov_id, m.mov_name, m.mov_poster, 
          AVG(r.rating) as avg_rating
          FROM movie m
          LEFT JOIN review r ON m.mov_id = r.mov_id
          LEFT JOIN show_schedule ss ON m.mov_id = ss.mov_id
          WHERE ss.show_date >= CURDATE()
          GROUP BY m.mov_id
          ORDER BY ss.show_date ASC
          LIMIT 20";

$result = $conn->query($query);
$movies = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
}

// Fetch wallet balance for logged-in users.
$user_balance = null;
if (isLoggedIn() && hasRole(ROLE_USER)) {
    $u_id = $_SESSION['u_id'] ?? null;
    if ($u_id) {
        $user_balance = getUserWalletBalance($u_id);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowFlow - Movie Ticket Booking</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            padding: 4rem 2rem;
            text-align: center;
            border-bottom: 1px solid #333;
        }

        .hero-title {
            font-size: 48px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .hero-title span {
            color: var(--accent-red);
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .movies-section {
            padding: 4rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            border-left: 4px solid var(--accent-red);
            padding-left: 1rem;
        }

        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 2rem;
        }

        .movie-card {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 1px solid #333;
        }

        .movie-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            border-color: var(--accent-red);
        }

        .movie-poster {
            width: 100%;
            aspect-ratio: 2/3;
            background: #0f0f0f;
            position: relative;
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie-rating-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 4px;
            border: 1px solid var(--accent-red);
        }

        .movie-info {
            padding: 1rem;
        }

        .movie-title {
            font-size: 16px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .movie-rating {
            font-size: 14px;
            color: var(--accent-red);
            margin-bottom: 0.5rem;
        }

        .btn-book {
            width: 100%;
            padding: 0.5rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-book:hover {
            background: #cc0710;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content {
            background: #1a1a1a;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.9);
            animation: slideUp 0.3s ease;
            border: 1px solid #333;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #333;
        }

        .modal-header h2 {
            color: var(--accent-red);
            margin: 0;
            font-size: 24px;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: var(--accent-red);
        }

        .modal-body {
            padding: 2rem;
        }

        .movie-poster-container {
            width: 100%;
            max-width: 300px;
            margin: 0 auto 1.5rem;
            border-radius: 8px;
            overflow: hidden;
            background: #0f0f0f;
            aspect-ratio: 2/3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        .movie-poster-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #0f0f0f;
            border-radius: 8px;
        }

        .meta-item {
            text-align: center;
        }

        .meta-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .meta-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
            padding: 0.5rem 0;
        }

        .rating-stars {
            color: var(--accent-red);
            font-size: 18px;
        }

        .synopsis {
            background: rgba(229, 9, 20, 0.1);
            border-left: 3px solid var(--accent-red);
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }

        .synopsis-label {
            font-weight: bold;
            color: var(--accent-red);
            margin-bottom: 0.5rem;
            font-size: 12px;
            text-transform: uppercase;
        }

        .synopsis-text {
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .action-buttons button, .action-buttons a {
            flex: 1;
            min-width: 140px;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-book-tickets {
            background: var(--accent-red);
            color: white;
        }

        .btn-book-tickets:hover {
            background: #cc0710;
            transform: scale(1.02);
        }

        .btn-add-review {
            background: #444;
            color: var(--text-primary);
            border: 1px solid #555;
        }

        .btn-add-review:hover {
            background: #555;
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .btn-watch-trailer {
            background: #333;
            color: var(--text-primary);
            border: 1px solid #555;
        }

        .btn-watch-trailer:hover {
            background: #444;
        }

        .reviews-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #333;
        }

        .reviews-title {
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 16px;
        }

        .review-item {
            background: #0f0f0f;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border-left: 2px solid var(--accent-red);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .review-author {
            font-weight: 600;
            color: var(--text-primary);
        }

        .review-date {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .review-rating {
            color: var(--accent-red);
            margin-bottom: 0.5rem;
        }

        .review-text {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-secondary);
        }

        .modal-loading {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .modal-error {
            background: rgba(229, 9, 20, 0.2);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
            padding: 1.5rem;
            border-radius: 6px;
            text-align: center;
        }

        /* --- Inline Form Label Styling --- */
        .form-group-modal label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* --- Notification Bell Styling --- */
        .notification-bell {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            position: relative;
            padding: 8px;
            transition: transform 0.2s;
        }

        .notification-bell:hover {
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--accent-red);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #notificationPanel {
            display: none;
            position: fixed;
            top: 70px;
            right: 20px;
            width: 400px;
            max-height: 500px;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.9);
            z-index: 1000;
            overflow-y: auto;
        }

        .notification-panel-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--tertiary-bg);
        }

        .notification-panel-header h3 {
            color: var(--accent-red);
            margin: 0;
            font-size: 16px;
        }

        .notification-panel-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 20px;
            cursor: pointer;
            padding: 0;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: background 0.2s;
        }

        .notification-item:hover {
            background: var(--tertiary-bg);
        }

        .notification-item.unread {
            background: rgba(229, 9, 20, 0.1);
        }

        .notification-content {
            flex: 1;
            margin-right: 1rem;
        }

        .notification-message {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }

        .notification-action-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            transition: color 0.2s;
        }

        .notification-action-btn:hover {
            color: var(--accent-red);
        }

        .notification-empty {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
        }

        @media (max-width: 600px) {
            #notificationPanel {
                width: 90vw;
                right: 5vw;
                max-height: 60vh;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="showflowicon.png" alt="ShowFlow" style="height: 24px; width: auto; margin-right: 8px;">
                ShowFlow
            </a>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Now in Theatre</a></li>
                    <li><a href="all_movies.php">All Movies</a></li>
                    <?php if (isLoggedIn() && hasRole(ROLE_USER)): ?>
                        <li><a href="facilities.php">Facilities</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole(ROLE_USER) && $user_balance !== null): ?>
                        <div class="wallet-balance">
                            <div class="wallet-balance-icon">💳</div>
                            <div class="wallet-balance-info">
                                <div class="wallet-balance-label">Wallet</div>
                                <div class="wallet-balance-amount">৳ <?php echo number_format($user_balance, 2); ?></div>
                            </div>
                        </div>
                        <a href="recharge.php" class="btn-recharge-header">+ Recharge</a>
                    <?php endif; ?>
                    <a href="user-profile.php" class="btn btn-secondary btn-small">👤 Profile</a>
                    <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
                <?php else: ?>
                    <a href="user-login.php" class="btn btn-secondary btn-small">Login</a>
                    <a href="user-login.php" class="btn btn-primary btn-small">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="hero-section">
        <h1 class="hero-title">Experience <span>Cinematic</span> Magic</h1>
        <p class="hero-subtitle">Book, snack, and enjoy an unforgettable movie night with ShowFlow<br>
        <b><a href="user-login.php" style="color: var(--accent-red); text-decoration: none;">LOGIN </a>to get ALL feature</b></p>
    </div>

    <div class="movies-section">
        <div class="section-header">
            <h2 class="section-title">Now Showing in Theatres</h2>
        </div>

        <?php if (count($movies) > 0): ?>
            <div class="movies-grid">
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card" onclick="showMovieDetails(<?php echo $movie['mov_id']; ?>)" style="cursor: pointer;">
                        <div class="movie-poster">
                            <?php if ($movie['mov_poster']): ?>
                                <img src="<?php echo htmlspecialchars($movie['mov_poster']); ?>" alt="<?php echo htmlspecialchars($movie['mov_name']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 40px; background: #0f0f0f;">🎬</div>
                            <?php endif; ?>
                            
                            <?php if ($movie['avg_rating'] > 0): ?>
                                <div class="movie-rating-badge">
                                    ⭐ <?php echo number_format($movie['avg_rating'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="movie-info">
                            <div class="movie-title"><?php echo htmlspecialchars($movie['mov_name']); ?></div>
                            <div class="movie-rating">
                                ⭐ <?php echo $movie['avg_rating'] ? number_format($movie['avg_rating'], 1) : 'No ratings'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem; color: var(--text-secondary); background: #1a1a1a; border-radius: 8px;">
                <h3 style="font-size: 24px; margin-bottom: 1rem;">No Movies Playing Today</h3>
                <p>Check back later or browse our complete movie catalog.</p>
                <a href="all_movies.php" class="btn btn-secondary" style="margin-top: 1rem; display: inline-block;">View All Movies</a>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; padding: 3rem 0; border-top: 1px solid var(--border-color); color: var(--text-secondary);">
        <p>Admin? <a href="developers-login.php" style="color: var(--accent-red); text-decoration: none;">Developer Login</a> | 
           Manager? <a href="manager-login.php" style="color: var(--accent-red); text-decoration: none;">Manager Login</a></p>
    </div>

    <div id="movieModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Movie Details</h2>
                <button class="close-btn" onclick="closeMovieModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="modal-loading">Loading movie details...</div>
            </div>
        </div>
    </div>

    <!-- Notification Panel -->
    <div id="notificationPanel">
        <div class="notification-panel-header">
            <h3>🔔 Notifications</h3>
            <button class="notification-panel-close" onclick="toggleNotificationPanel()">✕</button>
        </div>
        <div id="notificationList" style="max-height: 400px; overflow-y: auto;">
            <div class="notification-empty">Loading notifications...</div>
        </div>
    </div>

    <script>
        function showMovieDetails(movieId) {
            const modal = document.getElementById('movieModal');
            const modalBody = document.getElementById('modalBody');
            
            modal.classList.add('active');
            modalBody.innerHTML = '<div class="modal-loading">Loading movie details...</div>';
            
            fetch(`api_fetch_movie_details_public.php?mov_id=${movieId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayMovieModal(data);
                    } else {
                        modalBody.innerHTML = `<div class="modal-error">Error: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="modal-error">Failed to load movie details. Please try again.</div>';
                });
        }

        function displayMovieModal(data) {
            const modalBody = document.getElementById('modalBody');
            const movie = data.movie;
            const reviews = data.reviews;
            const avgRating = data.avg_rating;
            
            let starsDisplay = '';
            if (avgRating > 0) {
                const fullStars = Math.round(avgRating);
                starsDisplay = '⭐'.repeat(fullStars);
            }
            
            let trailerBtn = '';
            if (movie.mov_trailer) {
                trailerBtn = `<a href="${movie.mov_trailer}" target="_blank" class="btn-watch-trailer">▶️ Watch Trailer</a>`;
            }

            // --- Updated Inline Review Form (Dropdown + Styled Button) ---
            const reviewFormHTML = `
                <div id="reviewFormSection" style="display: none; background: #222; padding: 25px; margin: 25px 0; border-radius: 8px; border: 1px solid #444; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
                    <h3 style="color: white; margin-top: 0; margin-bottom: 20px; font-size: 20px;">Write a Review</h3>
                    <form id="modalReviewForm" onsubmit="submitModalReview(event, ${movie.mov_id})">
                        <div class="form-group-modal" style="margin-bottom: 20px;">
                            <label>Rating (1-5)</label>
                            <select id="modalRating" required style="width: 100%; padding: 12px; background: #141414; border: 1px solid #444; color: #ffffff; border-radius: 4px; outline: none; cursor: pointer;">
                                <option value="5" style="background: #141414; color: #ffffff;">⭐⭐⭐⭐⭐ (5/5 - Excellent)</option>
                                <option value="4" style="background: #141414; color: #ffffff;">⭐⭐⭐⭐ (4/5 - Good)</option>
                                <option value="3" style="background: #141414; color: #ffffff;">⭐⭐⭐ (3/5 - Average)</option>
                                <option value="2" style="background: #141414; color: #ffffff;">⭐⭐ (2/5 - Poor)</option>
                                <option value="1" style="background: #141414; color: #ffffff;">⭐ (1/5 - Terrible)</option>
                            </select>
                        </div>
                        <div class="form-group-modal" style="margin-bottom: 20px;">
                            <label>Review Text</label>
                            <textarea id="modalReviewText" required minlength="10" placeholder="Minimum 10 characters..." style="height: 100px; width: 100%; padding: 12px; background: #141414; border: 1px solid #444; color: #ffffff; border-radius: 4px; resize: vertical; box-sizing: border-box;"></textarea>
                        </div>
                        <button type="submit" style="width: 100%; padding: 14px; background: var(--accent-red); color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s;">Submit Review</button>
                    </form>
                    <div id="reviewMessage" style="margin-top: 15px; font-weight: bold; text-align: center;"></div>
                </div>
            `;
            
            let reviewsHTML = '';
            if (reviews.length > 0) {
                reviewsHTML = '<div class="reviews-section">';
                reviewsHTML += '<div class="reviews-title">💬 User Reviews</div>';
                reviews.forEach(review => {
                    const reviewDate = new Date(review.created_at).toLocaleDateString();
                    const reviewStars = '⭐'.repeat(review.rating);
                    reviewsHTML += `
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-author">${escapeHtml(review.u_name)}</span>
                                <span class="review-date">${reviewDate}</span>
                            </div>
                            <div class="review-rating">${reviewStars} (${review.rating}/5)</div>
                            <div class="review-text">${escapeHtml(review.review_text)}</div>
                        </div>
                    `;
                });
                reviewsHTML += '</div>';
            } else {
                reviewsHTML = '<div class="reviews-section"><div class="no-reviews">No reviews yet. Be the first to review!</div></div>';
            }
            
            const posterImg = movie.mov_poster ? 
                `<img src="${movie.mov_poster}" alt="${escapeHtml(movie.mov_name)}" onerror="this.parentElement.textContent='🎬'">` :
                '🎬';
            
            const html = `
                <div class="movie-poster-container">
                    ${posterImg}
                </div>
                
                <h2 style="color: var(--text-primary); margin: 1rem 0; font-size: 24px; text-align: center;">
                    ${escapeHtml(movie.mov_name)}
                </h2>
                
                ${avgRating > 0 ? `
                    <div class="rating-display">
                        <span class="rating-stars">${starsDisplay}</span>
                        <span style="color: var(--text-secondary);">${avgRating.toFixed(1)}/5 (${data.total_reviews} reviews)</span>
                    </div>
                ` : ''}
                
                <div class="movie-meta">
                    <div class="meta-item">
                        <div class="meta-label">Genre</div>
                        <div class="meta-value">${escapeHtml(movie.mov_genre || 'N/A')}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Duration</div>
                        <div class="meta-value">${movie.mov_duration ? movie.mov_duration + ' min' : 'N/A'}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Release</div>
                        <div class="meta-value">${movie.mov_release_date ? new Date(movie.mov_release_date).toLocaleDateString() : 'N/A'}</div>
                    </div>
                </div>
                
                ${movie.mov_synopsis ? `
                    <div class="synopsis">
                        <div class="synopsis-label">Synopsis</div>
                        <div class="synopsis-text">${escapeHtml(movie.mov_synopsis)}</div>
                    </div>
                ` : ''}
                
                <div class="action-buttons">
                    <button onclick="bookTickets(${movie.mov_id})" class="btn-book-tickets">🎫 Book Tickets</button>
                    <button onclick="toggleReviewForm()" class="btn-add-review">📝 Add Review</button>
                    ${trailerBtn}
                </div>
                
                ${reviewFormHTML}
                
                ${reviewsHTML}
            `;
            
            modalBody.innerHTML = html;
        }

        function bookTickets(movieId) {
            checkLoginAndRedirect('booking.php?movie=' + movieId);
        }

        function toggleReviewForm() {
            fetch('api_check_login.php')
                .then(response => response.json())
                .then(data => {
                    if (data.isLoggedIn) {
                        const formSection = document.getElementById('reviewFormSection');
                        if (formSection.style.display === 'none') {
                            formSection.style.display = 'block';
                            formSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            formSection.style.display = 'none';
                        }
                    } else {
                        window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent('index.php');
                    }
                })
                .catch(error => {
                    window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent('index.php');
                });
        }

        function submitModalReview(event, movId) {
            event.preventDefault(); // Stop page reload
            
            const rating = document.getElementById('modalRating').value;
            const reviewText = document.getElementById('modalReviewText').value;
            const messageDiv = document.getElementById('reviewMessage');

            const params = new URLSearchParams();
            params.append('action', 'add_review');
            params.append('mov_id', movId);
            params.append('rating', rating);
            params.append('review_text', reviewText);

            messageDiv.style.color = 'var(--text-secondary)';
            messageDiv.innerText = 'Submitting review...';

            fetch('add_review_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    messageDiv.style.color = '#4CAF50';
                    messageDiv.innerText = 'Review posted successfully!';
                    document.getElementById('modalReviewForm').reset();
                    
                    // Reload modal automatically to show new review
                    setTimeout(() => showMovieDetails(movId), 1500);
                } else {
                    messageDiv.style.color = 'var(--accent-red)';
                    messageDiv.innerText = 'Error: ' + data.message;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                messageDiv.style.color = 'var(--accent-red)';
                messageDiv.innerText = 'A network error occurred. Check console.';
            });
        }

        function checkLoginAndRedirect(targetPage) {
            fetch('api_check_login.php')
                .then(response => response.json())
                .then(data => {
                    if (data.isLoggedIn) {
                        window.location.href = targetPage;
                    } else {
                        window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent(targetPage);
                    }
                })
                .catch(error => {
                    window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent(targetPage);
                });
        }

        function closeMovieModal() {
            const modal = document.getElementById('movieModal');
            modal.classList.remove('active');
        }

        document.getElementById('movieModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeMovieModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMovieModal();
            }
        });

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

    </script>

</body>
</html>