<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

// Fetch all movies
$query = "SELECT mov_id, mov_name, mov_poster, mov_trailer, created_at FROM movie ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
$stmt->close();

// Count stats
$movie_count = count($movies);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Movies - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .search-container {
            background: var(--bg-secondary);
            padding: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .search-input {
            width: 100%;
            max-width: 500px;
            padding: 1rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-red);
            box-shadow: 0 0 10px rgba(229, 9, 20, 0.3);
        }

        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }

        .movie-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .movie-card:hover {
            transform: scale(1.05);
            border-color: var(--accent-red);
            box-shadow: 0 8px 20px rgba(229, 9, 20, 0.2);
        }

        .movie-poster {
            width: 100%;
            height: 280px;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 48px;
            overflow: hidden;
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie-info {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .movie-title {
            font-size: 16px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .movie-actions {
            margin-top: auto;
            display: flex;
            gap: 0.5rem;
        }

        .btn-book {
            flex: 1;
            padding: 0.75rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-book:hover {
            background: #d40812;
            transform: scale(1.02);
        }

        .btn-details {
            flex: 1;
            padding: 0.75rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-details:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 1rem;
        }

        .header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            padding: 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-primary);
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: var(--accent-red);
            border-color: var(--accent-red);
        }

        .search-results-count {
            padding: 0 2rem;
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-primary);
        }

        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--accent-red);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .movie-detail-poster {
            width: 100%;
            height: 300px;
            background: var(--bg-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .movie-detail-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie-detail-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .movie-detail-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .movie-detail-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .movie-detail-rating .stars {
            font-size: 18px;
        }

        .movie-detail-synopsis {
            background: var(--bg-primary);
            border-left: 4px solid var(--accent-red);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .movie-detail-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            flex: 1;
            min-width: 150px;
            padding: 0.75rem 1.5rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #d40812;
            transform: translateY(-2px);
        }

        .btn-secondary {
            flex: 1;
            min-width: 150px;
            padding: 0.75rem 1.5rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .reviews-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .reviews-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .review-item {
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 0.75rem;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 14px;
        }

        .review-user {
            font-weight: bold;
            color: var(--text-primary);
        }

        .review-date {
            color: var(--text-secondary);
        }

        .review-rating {
            color: var(--accent-red);
            margin-bottom: 0.5rem;
            font-size: 14px;
        }

        .review-text {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            color: var(--text-secondary);
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
                padding: 1rem;
            }

            .search-container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-nav {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <!-- Header -->
    <div class="header">
        <div class="header-title">🎬 Browse Movies</div>
        <div class="header-nav">
            <a href="user-profile.php" class="nav-link">👤 Profile</a>
            <a href="logout.php" class="nav-link">🚪 Logout</a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <input 
            type="text" 
            id="searchInput" 
            class="search-input" 
            placeholder="🔍 Search movies by name..."
            autocomplete="off"
        >
    </div>

    <!-- Results Count -->
    <div class="search-results-count" id="resultsCount">
        Showing <strong id="movieCount"><?php echo $movie_count; ?></strong> movies
    </div>

    <!-- Movies Grid -->
    <div class="movies-grid" id="moviesGrid">
        <?php if (count($movies) > 0): ?>
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card" data-movie-id="<?php echo $movie['mov_id']; ?>" data-movie-name="<?php echo htmlspecialchars(strtolower($movie['mov_name'])); ?>" style="cursor: pointer;">
                    <div class="movie-poster">
                        <?php if (!empty($movie['mov_poster'])): ?>
                            <img src="<?php echo htmlspecialchars($movie['mov_poster']); ?>" alt="<?php echo htmlspecialchars($movie['mov_name']); ?>">
                        <?php else: ?>
                            🎥
                        <?php endif; ?>
                    </div>
                    <div class="movie-info">
                        <div class="movie-title"><?php echo htmlspecialchars($movie['mov_name']); ?></div>
                        <div class="movie-actions" onclick="event.stopPropagation();">
                            <button class="btn-book" onclick="event.stopPropagation(); goToBooking(<?php echo $movie['mov_id']; ?>)">🎫 Book</button>
                            <button class="btn-details" onclick="event.stopPropagation(); showMovieDetails(<?php echo $movie['mov_id']; ?>)">ℹ️</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">🎬</div>
                <h2>No movies available</h2>
                <p>Check back soon for new releases!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Movie Details Modal -->
    <div id="detailsModal" class="modal">
    </div>

    <script>
        const movieData = <?php echo json_encode($movies); ?>;

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const moviesGrid = document.getElementById('moviesGrid');
        const movieCards = moviesGrid.querySelectorAll('.movie-card');
        const movieCount = document.getElementById('movieCount');
        const resultsCount = document.getElementById('resultsCount');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;

            movieCards.forEach(card => {
                const movieName = card.dataset.movieName;
                if (movieName.includes(searchTerm) || searchTerm === '') {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            movieCount.textContent = visibleCount;
            
            if (visibleCount === 0 && searchTerm) {
                resultsCount.innerHTML = `No movies found matching "<strong>${escapeHtml(searchTerm)}</strong>"`;
            } else {
                resultsCount.innerHTML = `Showing <strong id="movieCount">${visibleCount}</strong> movies`;
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function goToBooking(movieId) {
            window.location.href = `booking.php?movie=${movieId}`;
        }

        function showMovieDetails(movieId) {
            // Fetch movie details via AJAX
            fetch(`api_fetch_movie_details.php?mov_id=${movieId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayMovieModal(data);
                    } else {
                        alert('Error loading movie details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load movie details');
                });
        }

        function displayMovieModal(data) {
            const movie = data.movie;
            const reviews = data.reviews;
            const avg_rating = data.avg_rating;
            const user_review = data.user_review;

            // Build HTML for reviews
            let reviewsHtml = '';
            if (reviews.length > 0) {
                reviewsHtml = reviews.map(review => {
                    const stars = '⭐'.repeat(review.rating) + '<span style="opacity: 0.3;">⭐</span>'.repeat(5 - review.rating);
                    const date = new Date(review.created_at).toLocaleDateString();
                    return `
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-user">${escapeHtml(review.u_name)}</span>
                                <span class="review-date">${date}</span>
                            </div>
                            <div class="review-rating">${stars}</div>
                            <div class="review-text">${escapeHtml(review.review_text)}</div>
                        </div>
                    `;
                }).join('');
            } else {
                reviewsHtml = '<div class="no-reviews">No reviews yet. Be the first to review this movie!</div>';
            }

            // Build modal content
            const posterImg = movie.mov_poster ? `<img src="${escapeHtml(movie.mov_poster)}" alt="${escapeHtml(movie.mov_name)}">` : '<span style="font-size: 48px;">🎬</span>';
            const synopsis = movie.mov_synopsis ? `<div class="movie-detail-synopsis"><strong>Synopsis:</strong><br>${escapeHtml(movie.mov_synopsis)}</div>` : '';
            const genreText = movie.mov_genre ? `📽️ ${escapeHtml(movie.mov_genre)}` : '';
            const durationText = movie.mov_duration ? `⏱️ ${movie.mov_duration} min` : '';
            const dateText = movie.mov_release_date ? `📅 ${new Date(movie.mov_release_date).toLocaleDateString()}` : '';
            const trailerBtn = movie.mov_trailer ? `<button class="btn-secondary" onclick="window.open('${escapeHtml(movie.mov_trailer)}', '_blank')">▶️ Watch Trailer</button>` : '';
            const ratingDisplay = avg_rating > 0 ? `<div class="movie-detail-rating">⭐ <span style="color: var(--accent-red); font-weight: bold;">${avg_rating}/5</span> (${data.total_reviews} reviews)</div>` : '';

            const modalContent = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">${escapeHtml(movie.mov_name)}</h2>
                        <button class="modal-close" onclick="closeMovieDetails()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="movie-detail-poster">${posterImg}</div>
                        <div class="movie-detail-title">${escapeHtml(movie.mov_name)}</div>
                        <div class="movie-detail-meta">
                            ${genreText}
                            ${durationText}
                            ${dateText}
                        </div>
                        ${ratingDisplay}
                        ${synopsis}
                        <div class="movie-detail-buttons">
                            <button class="btn-primary" onclick="goToBooking(${movie.mov_id})">🎫 Book Tickets</button>
                            ${trailerBtn}
                        </div>
                        <div class="reviews-section">
                            <div class="reviews-title">💬 User Reviews</div>
                            ${reviewsHtml}
                        </div>
                    </div>
                </div>
            `;

            // Update and show modal
            const modal = document.getElementById('detailsModal');
            modal.innerHTML = modalContent;
            modal.classList.add('active');
        }

        function closeMovieDetails() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                closeMovieDetails();
            }
        }

        // Escape key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMovieDetails();
            }
        });
    </script>
</body>
</html>
