<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Fetch all movies with their stats
$query = "
    SELECT 
        m.mov_id,
        m.mov_name,
        m.mov_poster,
        m.mov_genre,
        COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
        COUNT(DISTINCT r.rev_id) as review_count,
        COALESCE(SUM(b.total_amount), 0) as total_earnings,
        COUNT(DISTINCT b.book_id) as booking_count
    FROM movie m
    LEFT JOIN review r ON m.mov_id = r.mov_id
    LEFT JOIN show_schedule s ON m.mov_id = s.mov_id
    LEFT JOIN booking b ON s.s_id = b.s_id
    GROUP BY m.mov_id, m.mov_name, m.mov_poster, m.mov_genre
    ORDER BY total_earnings DESC, avg_rating DESC
";

$result = $conn->query($query);
$movies = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Movies - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .movies-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 36px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }

        .filter-btn:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .filter-btn.active {
            background: var(--accent-red);
            color: white;
            border-color: var(--accent-red);
        }

        .movies-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-secondary);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .movies-table thead {
            background: var(--bg-primary);
            border-bottom: 2px solid var(--border-color);
        }

        .movies-table th {
            padding: 1rem;
            text-align: left;
            color: var(--text-primary);
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .movies-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .movies-table tbody tr:hover {
            background: var(--bg-primary);
        }

        .movies-table tbody tr:last-child {
            border-bottom: none;
        }

        .movies-table td {
            padding: 1rem;
            color: var(--text-primary);
            font-size: 14px;
        }

        .movie-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .movie-poster-small {
            width: 60px;
            height: 90px;
            background: #0f0f0f;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .movie-poster-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie-info h3 {
            margin: 0;
            font-size: 16px;
            color: var(--text-primary);
        }

        .movie-info p {
            margin: 0.25rem 0 0 0;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .genre-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(229, 9, 20, 0.2);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
            border-radius: 20px;
            font-size: 12px;
        }

        .rating-cell {
            text-align: center;
        }

        .rating-stars {
            font-size: 18px;
            color: var(--accent-red);
            margin-bottom: 0.25rem;
        }

        .rating-value {
            font-weight: bold;
            color: var(--text-primary);
        }

        .review-count {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .earnings-cell {
            text-align: right;
            font-weight: bold;
            color: var(--accent-red);
        }

        .booking-count {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .no-movies {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }

        .stats-bar {
            display: none;
        }

        .stat-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--accent-red);
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent-red);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 1024px) {
            .movies-table {
                font-size: 13px;
            }

            .movies-table th,
            .movies-table td {
                padding: 0.75rem;
            }

            .movie-poster-small {
                width: 50px;
                height: 75px;
            }
        }

        @media (max-width: 768px) {
            .movies-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 24px;
            }

            .movies-table {
                font-size: 12px;
            }

            .movies-table th,
            .movies-table td {
                padding: 0.5rem;
            }

            .movie-cell {
                gap: 0.5rem;
            }

            .movie-poster-small {
                width: 40px;
                height: 60px;
            }

            .movie-info h3 {
                font-size: 14px;
            }

            .stats-bar {
                grid-template-columns: 1fr;
            }
        }

        /* Movie Details Modal Styles */
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
            border: 1px solid #444;
        }

        .btn-watch-trailer:hover {
            background: #444;
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .reviews-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #333;
        }

        .reviews-title {
            font-size: 16px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .review-item {
            background: #0f0f0f;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border-left: 3px solid var(--accent-red);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .review-author {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }

        .review-date {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .review-rating {
            color: var(--accent-red);
            font-size: 14px;
            margin-bottom: 0.5rem;
        }

        .review-text {
            color: var(--text-primary);
            font-size: 13px;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            color: var(--text-secondary);
            padding: 1rem;
        }

        .modal-loading, .modal-error {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .modal-error {
            color: #ff6b6b;
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span>🎬</span> ShowFlow
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Now in Theatre</a></li>
                    <li><a href="all_movies.php" class="active">All Movies</a></li>
                    <?php if (isLoggedIn() && hasRole(ROLE_USER)): ?>
                        <li><a href="user-facilities.php">Facilities</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="user-profile.php" class="btn btn-secondary btn-small">👤 Profile</a>
                    <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
                <?php else: ?>
                    <a href="user-login.php" class="btn btn-secondary btn-small">Login</a>
                    <a href="user-login.php" class="btn btn-primary btn-small">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="movies-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">🎭 All Movies</div>
            <div class="page-subtitle">Browse our complete movie catalog</div>
        </div>

        <!-- Statistics Bar -->
        <?php if (count($movies) > 0): ?>
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($movies); ?></div>
                    <div class="stat-label">Total Movies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₹<?php echo number_format(array_sum(array_column($movies, 'total_earnings'))); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo array_sum(array_column($movies, 'booking_count')); ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo array_sum(array_column($movies, 'review_count')); ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Movies Table -->
        <?php if (count($movies) > 0): ?>
            <table class="movies-table">
                <thead>
                    <tr>
                        <th>Movie</th>
                        <th>Genre</th>
                        <th>Rating</th>
                        <th>Total Earnings</th>
                        <th>Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td onclick="showMovieDetails(<?php echo $movie['mov_id']; ?>)" style="cursor: pointer;">
                                <div class="movie-cell">
                                    <div class="movie-poster-small">
                                        <?php if ($movie['mov_poster']): ?>
                                            <img src="<?php echo htmlspecialchars($movie['mov_poster']); ?>" alt="<?php echo htmlspecialchars($movie['mov_name']); ?>">
                                        <?php else: ?>
                                            🎬
                                        <?php endif; ?>
                                    </div>
                                    <div class="movie-info">
                                        <h3><?php echo htmlspecialchars($movie['mov_name']); ?></h3>
                                        <p><?php echo $movie['review_count']; ?> reviews</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="genre-badge"><?php echo htmlspecialchars($movie['mov_genre'] ?? 'N/A'); ?></span>
                            </td>
                            <td class="rating-cell">
                                <div class="rating-stars">
                                    <?php 
                                    $stars = round($movie['avg_rating']);
                                    echo str_repeat('⭐', $stars);
                                    ?>
                                </div>
                                <div class="rating-value"><?php echo number_format($movie['avg_rating'], 1); ?>/5</div>
                                <div class="review-count"><?php echo $movie['review_count']; ?> ratings</div>
                            </td>
                            <td class="earnings-cell">
                                ₹<?php echo number_format($movie['total_earnings']); ?>
                                <div class="booking-count"><?php echo $movie['booking_count']; ?> bookings</div>
                            </td>
                            <td style="text-align: center;">
                                <button onclick="showMovieDetails(<?php echo $movie['mov_id']; ?>); return false;" class="btn btn-primary" style="font-size: 12px; padding: 0.5rem 1rem;">Details</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-movies">
                <p style="font-size: 18px; margin-bottom: 1rem;">🎭 No movies yet</p>
                <p>Check back soon for new movies!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer Navigation -->
    <div style="text-align: center; padding: 3rem 0; border-top: 1px solid var(--border-color); color: var(--text-secondary);">
        <p>Admin? <a href="developers-login.php" style="color: var(--accent-red); text-decoration: none;">Developer Login</a> | 
           Manager? <a href="manager-login.php" style="color: var(--accent-red); text-decoration: none;">Manager Login</a></p>
    </div>

    <!-- Movie Details Modal -->
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

    <script>
        function showMovieDetails(movieId) {
            const modal = document.getElementById('movieModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show modal with loading state
            modal.classList.add('active');
            modalBody.innerHTML = '<div class="modal-loading">Loading movie details...</div>';
            
            // Fetch movie data
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
            const modal = document.getElementById('movieModal');
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
                    <button onclick="addReview(${movie.mov_id})" class="btn-add-review">📝 Add Review</button>
                    ${trailerBtn}
                </div>
                
                ${reviewsHTML}
            `;
            
            modalBody.innerHTML = html;
        }

        function bookTickets(movieId) {
            checkLoginAndRedirect('booking.php?movie=' + movieId);
        }

        function addReview(movieId) {
            checkLoginAndRedirect('add_review_handler.php?mov_id=' + movieId);
        }

        function checkLoginAndRedirect(targetPage) {
            // Check if user is logged in by making a simple request
            fetch('api_check_login.php')
                .then(response => response.json())
                .then(data => {
                    if (data.isLoggedIn) {
                        // User is logged in, redirect to target page
                        window.location.href = targetPage;
                    } else {
                        // User not logged in, redirect to login with redirect_to parameter
                        window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent(targetPage);
                    }
                })
                .catch(error => {
                    console.error('Error checking login:', error);
                    // On error, assume not logged in and redirect to login
                    window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent(targetPage);
                });
        }

        function closeMovieModal() {
            const modal = document.getElementById('movieModal');
            modal.classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('movieModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeMovieModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMovieModal();
            }
        });

        // Helper function to escape HTML
        function escapeHtml(text) {
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
