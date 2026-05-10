<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Handle Search and Sort Inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'earnings';

$whereClause = "";
if (!empty($search)) {
    // Sanitize search input to prevent SQL injection
    $safeSearch = $conn->real_escape_string($search);
    $whereClause = "WHERE m.mov_name LIKE '%$safeSearch%'";
}

// Determine sorting logic
$orderBy = "ORDER BY total_earnings DESC, avg_rating DESC"; // default
if ($sort === 'rating') {
    $orderBy = "ORDER BY avg_rating DESC, total_earnings DESC";
} else if ($sort === 'release_date') {
    $orderBy = "ORDER BY m.mov_release_date DESC, m.mov_id DESC";
}

// Fetch movies with their stats
$query = "
    SELECT 
        m.mov_id,
        m.mov_name,
        m.mov_poster,
        m.mov_genre,
        m.mov_release_date,
        COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
        COUNT(DISTINCT r.rev_id) as review_count,
        COALESCE(SUM(b.total_amount), 0) as total_earnings,
        COUNT(DISTINCT b.book_id) as booking_count
    FROM movie m
    LEFT JOIN review r ON m.mov_id = r.mov_id
    LEFT JOIN show_schedule s ON m.mov_id = s.mov_id
    LEFT JOIN booking b ON s.s_id = b.s_id
    $whereClause
    GROUP BY m.mov_id, m.mov_name, m.mov_poster, m.mov_genre, m.mov_release_date
    $orderBy
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
    <link rel="icon" type="image/png" href="showflowicon.png">
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

        /* --- Search and Sort UI Styles --- */
        .controls-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            max-width: 500px;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: var(--accent-red);
        }

        .search-btn {
            padding: 0.75rem 1.5rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }

        .search-btn:hover {
            background: #cc0710;
        }

        .sort-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sort-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .sort-select {
            padding: 0.75rem 2rem 0.75rem 1rem;
            background: var(--bg-primary, #141414);
            border: 1px solid var(--border-color, #333);
            color: var(--text-primary, #ffffff);
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23ffffff%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem top 50%;
            background-size: 0.65rem auto;
        }

        .sort-select:focus {
            border-color: var(--accent-red);
        }

        .sort-select option {
            background-color: #141414;
            color: #ffffff;
        }

        /* --- Existing Styles below --- */
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

        @media (max-width: 1024px) {
            .movies-table {
                font-size: 13px;
            }
            .movies-table th,
            .movies-table td {
                padding: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .movies-container {
                padding: 1rem;
            }
            .controls-container {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form {
                max-width: 100%;
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

        /* Styling for the embedded form */
        .form-group-modal {
            margin-bottom: 15px;
        }
        .form-group-modal label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 13px;
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
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
                        <li><a href="facilities.php">Facilities</a></li>
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
        <div class="page-header">
            <div class="page-title">🎭 All Movies</div>
            <div class="page-subtitle">Browse our complete movie catalog</div>
        </div>

        <div class="controls-container">
            <form class="search-form" method="GET" action="all_movies.php">
                <?php if ($sort !== 'earnings'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <?php endif; ?>
                <input type="text" name="search" class="search-input" placeholder="Search movies by name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">Search</button>
            </form>
            
            <form class="sort-container" method="GET" action="all_movies.php" id="sortForm">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <span class="sort-label">Sort by:</span>
                <select name="sort" class="sort-select" onchange="document.getElementById('sortForm').submit();">
                    <option value="earnings" <?php echo $sort === 'earnings' ? 'selected' : ''; ?>>Highest Earnings</option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rating</option>
                    <option value="release_date" <?php echo $sort === 'release_date' ? 'selected' : ''; ?>>Latest Release</option>
                </select>
            </form>
        </div>

        <?php if (count($movies) > 0): ?>
            <table class="movies-table">
                <thead>
                    <tr>
                        <th>Movie</th>
                        <th>Genre</th>
                        <th>Release</th>
                        <th>Rating</th>
                        <th>Total Earnings</th>
                        <th>Details</th>
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
                            <td>
                                <span style="color: var(--text-secondary); font-size: 13px;">
                                    <?php echo $movie['mov_release_date'] ? date('M d, Y', strtotime($movie['mov_release_date'])) : 'N/A'; ?>
                                </span>
                            </td>
                            <td class="rating-cell">
                                <div class="rating-stars">
                                    <?php 
                                    $stars = round($movie['avg_rating']);
                                    echo str_repeat('⭐', $stars);
                                    ?>
                                </div>
                                <div class="rating-value"><?php echo number_format($movie['avg_rating'], 1); ?>/5</div>
                            </td>
                            <td class="earnings-cell">
                                ৳<?php echo number_format($movie['total_earnings']); ?>
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
                <p style="font-size: 18px; margin-bottom: 1rem;">🎭 No movies found</p>
                <p>Try adjusting your search query.</p>
                <?php if(!empty($search)): ?>
                    <a href="all_movies.php" class="btn btn-secondary" style="margin-top: 1rem; display: inline-block;">Clear Search</a>
                <?php endif; ?>
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

            // Built-in Review Form HTML (Updated with Dropdown and Better Button Styling)
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

        // Toggle the review form inside the modal
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
                        window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent('all_movies.php');
                    }
                })
                .catch(error => {
                    window.location.href = 'user-login.php?redirect_to=' + encodeURIComponent('all_movies.php');
                });
        }

        // Handles the silent POST submission
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
                    
                    // Automatically reload the modal after 1.5 seconds to show the new review
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