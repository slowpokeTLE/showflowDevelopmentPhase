<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

// Get initial movie from URL or use first available
$mov_id = intval($_GET['movie'] ?? 0);

// Fetch all movies for initial dropdown
$query = "SELECT mov_id, mov_name FROM movie ORDER BY mov_name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
$stmt->close();

// If movie_id from URL is valid, get default theatre
$default_theatre = null;
if ($mov_id > 0) {
    $query = "
        SELECT DISTINCT t.t_id, t.theatre_name 
        FROM theatre t
        JOIN show_schedule s ON t.t_id = s.t_id
        WHERE s.mov_id = ? 
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mov_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $default_theatre = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .booking-header {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-link {
            color: var(--accent-red);
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .booking-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .filters-panel {
            background: var(--bg-secondary);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            display: block;
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 0.5rem;
            font-size: 14px;
        }

        .filter-select {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--accent-red);
        }

        .filter-select option {
            background: #141414;
            color: #ffffff;
        }

        .filter-select:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: var(--bg-secondary);
            color: var(--text-secondary);
        }

        .seats-panel {
            background: var(--bg-secondary);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .seat-grid-title {
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .seat-grid {
            display: inline-block;
            margin: 0 auto;
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .seat-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .row-label {
            width: 30px;
            text-align: right;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: bold;
        }

        .seat {
            width: 35px;
            height: 35px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            transition: all 0.2s ease;
            background: #2d5a2d;
            color: #90EE90;
        }

        .seat:hover:not(.booked) {
            transform: scale(1.1);
            border-color: #90EE90;
        }

        .seat.booked {
            background: #8b0000;
            color: #ff6b6b;
            border-color: #ff6b6b;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .seat.selected {
            background: var(--accent-red);
            color: white;
            border-color: var(--accent-red);
            transform: scale(1.15);
        }

        .legend {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            font-size: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-seat {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 3px;
        }

        .legend-available {
            background: #2d5a2d;
            border-color: #90EE90;
        }

        .legend-booked {
            background: #8b0000;
            border-color: #ff6b6b;
        }

        .legend-selected {
            background: var(--accent-red);
            border-color: var(--accent-red);
        }

        .summary {
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .summary-title {
            color: var(--text-secondary);
            font-size: 12px;
            margin-bottom: 0.5rem;
        }

        .selected-seats {
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 1rem;
            min-height: 20px;
        }

        .price-summary {
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 14px;
        }

        .price-total {
            display: flex;
            justify-content: space-between;
            color: var(--accent-red);
            font-weight: bold;
            font-size: 18px;
        }

        .btn-container {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--accent-red);
            color: white;
        }

        .btn-primary:hover {
            background: #d40812;
            transform: scale(1.02);
        }

        .btn-primary:disabled {
            background: #666;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .loading {
            color: var(--text-secondary);
            font-size: 12px;
            text-align: center;
        }

        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 14px;
        }

        .message.error {
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #8b0000;
            color: #ff6b6b;
        }

        .no-shows {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        @media (max-width: 1024px) {
            .booking-content {
                grid-template-columns: 1fr;
            }

            .seat {
                width: 30px;
                height: 30px;
                font-size: 9px;
            }

            .legend {
                gap: 1rem;
            }
        }

        @media (max-width: 640px) {
            .booking-container {
                padding: 1rem;
            }

            .booking-header {
                font-size: 20px;
            }

            .seat {
                width: 25px;
                height: 25px;
            }

            .seat-row {
                gap: 0.3rem;
            }

            .filters-panel,
            .seats-panel {
                padding: 1rem;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div class="booking-container">
        <div class="booking-header">
            <span>🎫 Book Your Tickets</span>
            <a href="index.php" class="back-link">← Back to Movies</a>
        </div>

        <?php 
        $booking_error = $_SESSION['booking_error'] ?? '';
        $booking_success = $_SESSION['booking_success'] ?? '';
        if ($booking_error): ?>
            <div class="message error" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; color: #ff6b6b;">
                <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($booking_error); ?>
            </div>
            <?php unset($_SESSION['booking_error']); ?>
        <?php endif; ?>

        <?php if ($booking_success): ?>
            <div class="message success" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981;">
                <strong>✓ Success:</strong> <?php echo htmlspecialchars($booking_success); ?>
            </div>
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>

        <div class="booking-content">
            <!-- Filters Panel -->
            <div class="filters-panel">
                <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">Select Show</h3>

                <div class="filter-group">
                    <label class="filter-label">Movie</label>
                    <select id="movieSelect" class="filter-select" onchange="onMovieChange()">
                        <option value="">-- Select Movie --</option>
                        <?php foreach ($movies as $m): ?>
                            <option value="<?php echo $m['mov_id']; ?>" <?php echo $m['mov_id'] == $mov_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['mov_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Theatre</label>
                    <select id="theatreSelect" class="filter-select" onchange="onTheatreChange()" disabled>
                        <option value="">-- Select Theatre --</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date</label>
                    <select id="dateSelect" class="filter-select" onchange="onDateChange()" disabled>
                        <option value="">-- Select Date --</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Show Time</label>
                    <select id="showSelect" class="filter-select" onchange="onShowChange()" disabled>
                        <option value="">-- Select Show --</option>
                    </select>
                </div>

                <div id="showInfo" style="display: none; background: var(--bg-primary); padding: 1rem; border-radius: 6px; margin-top: 1rem; font-size: 14px;">
                    <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Show Details:</div>
                    <div id="showDetails"></div>
                </div>
            </div>

            <!-- Seats Panel -->
            <div class="seats-panel">
                <div id="seatsContent">
                    <p style="text-align: center; color: var(--text-secondary);">Select a show to view seats</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const movieData = <?php echo json_encode($movies); ?>;
        let currentShow = null;
        let selectedSeats = [];

        // Wait for DOM to be ready before initializing
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded, initializing...');
            // Initialize if movie is pre-selected
            <?php if ($mov_id > 0): ?>
            console.log('Pre-selecting movie:', <?php echo $mov_id; ?>);
            document.getElementById('movieSelect').value = <?php echo $mov_id; ?>;
            setTimeout(function() {
                onMovieChange();
            }, 100);
            <?php endif; ?>
        });

        async function onMovieChange() {
            const movieId = document.getElementById('movieSelect').value;
            const theatreSelect = document.getElementById('theatreSelect');
            const dateSelect = document.getElementById('dateSelect');
            const showSelect = document.getElementById('showSelect');

            console.log('onMovieChange triggered with movieId:', movieId);

            theatreSelect.innerHTML = '<option value="">-- Select Theatre --</option>';
            dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
            showSelect.innerHTML = '<option value="">-- Select Show --</option>';
            theatreSelect.disabled = true;
            dateSelect.disabled = true;
            showSelect.disabled = true;
            document.getElementById('seatsContent').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Select a show to view seats</p>';

            if (!movieId) {
                console.log('No movie selected');
                return;
            }

            console.log('Fetching theatres for movie:', movieId);
            try {
                const response = await fetch(`api_fetch_theatres_by_movie.php?mov_id=${movieId}`);
                console.log('API Response Status:', response.status);
                
                if (!response.ok) {
                    throw new Error('API Error: ' + response.status);
                }
                
                const data = await response.json();
                console.log('Theatre response:', data);

                if (data.status === 'success' && data.data && data.data.theatres && data.data.theatres.length > 0) {
                    const theatres = data.data.theatres;
                    console.log('Adding', theatres.length, 'theatres to dropdown');
                    
                    theatres.forEach(theatre => {
                        const option = document.createElement('option');
                        option.value = theatre.t_id;
                        option.textContent = `${theatre.theatre_name} - ${theatre.location}`;
                        theatreSelect.appendChild(option);
                    });
                    theatreSelect.disabled = false;
                    console.log('Theatre dropdown enabled!');
                } else if (data.status === 'success' && (!data.data || !data.data.theatres || data.data.theatres.length === 0)) {
                    theatreSelect.innerHTML = '<option value="">-- No theatres available --</option>';
                    theatreSelect.disabled = true;
                    console.log('No theatres available for this movie');
                } else {
                    theatreSelect.innerHTML = '<option value="">-- Error loading theatres --</option>';
                    theatreSelect.disabled = true;
                    console.log('API Error:', data.message);
                }
            } catch (error) {
                console.error('Error fetching theatres:', error);
                theatreSelect.innerHTML = '<option value="">-- Error loading theatres --</option>';
                theatreSelect.disabled = true;
            }
        }

        async function onTheatreChange() {
            const movieId = document.getElementById('movieSelect').value;
            const theatreId = document.getElementById('theatreSelect').value;
            const dateSelect = document.getElementById('dateSelect');
            const showSelect = document.getElementById('showSelect');

            dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
            showSelect.innerHTML = '<option value="">-- Select Show --</option>';
            dateSelect.disabled = true;
            showSelect.disabled = true;
            document.getElementById('seatsContent').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Select a date to view shows</p>';

            if (!movieId || !theatreId) return;

            try {
                const response = await fetch(`api_fetch_dates_by_show.php?mov_id=${movieId}&t_id=${theatreId}`);
                const data = await response.json();

                if (data.status === 'success' && data.data.dates.length > 0) {
                    const dates = data.data.dates;
                    dates.forEach(date => {
                        const option = document.createElement('option');
                        option.value = date.show_date;
                        option.textContent = date.formatted_date;
                        dateSelect.appendChild(option);
                    });
                    dateSelect.disabled = false;
                } else {
                    dateSelect.innerHTML += '<option value="">No dates available</option>';
                }
            } catch (error) {
                console.error('Error fetching dates:', error);
                dateSelect.innerHTML += '<option value="">Error loading dates</option>';
            }
        }

        async function onDateChange() {
            const movieId = document.getElementById('movieSelect').value;
            const theatreId = document.getElementById('theatreSelect').value;
            const showDate = document.getElementById('dateSelect').value;
            const showSelect = document.getElementById('showSelect');

            showSelect.innerHTML = '<option value="">-- Select Show --</option>';
            showSelect.disabled = true;
            document.getElementById('seatsContent').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Loading shows...</p>';

            if (!movieId || !theatreId || !showDate) return;

            try {
                const response = await fetch(`api_fetch_shows_by_date.php?mov_id=${movieId}&t_id=${theatreId}&show_date=${showDate}`);
                const data = await response.json();

                if (data.status === 'success' && data.data.shows.length > 0) {
                    const shows = data.data.shows;
                    shows.forEach(show => {
                        const option = document.createElement('option');
                        option.value = show.s_id;
                        option.dataset.price = show.ticket_price;
                        option.textContent = `${show.formatted_time} (${show.available_seats}/${show.total_seats} available) - ৳${show.ticket_price}`;
                        showSelect.appendChild(option);
                    });
                    showSelect.disabled = false;
                } else {
                    showSelect.innerHTML += '<option value="">No shows available</option>';
                    document.getElementById('seatsContent').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No shows available for this date</p>';
                }
            } catch (error) {
                console.error('Error fetching shows:', error);
                showSelect.innerHTML += '<option value="">Error loading shows</option>';
            }
        }

        async function onShowChange() {
            const showId = document.getElementById('showSelect').value;
            selectedSeats = [];

            if (!showId) {
                document.getElementById('seatsContent').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Select a show to view seats</p>';
                document.getElementById('showInfo').style.display = 'none';
                return;
            }

            document.getElementById('seatsContent').innerHTML = '<p class="loading">Loading seats...</p>';

            try {
                const response = await fetch(`api_fetch_seats_by_show.php?s_id=${showId}`);
                const data = await response.json();

                if (data.status === 'success') {
                    currentShow = data.data;
                    renderSeatGrid(data.data);
                    updateShowInfo();
                } else {
                    document.getElementById('seatsContent').innerHTML = '<p class="message error">' + data.message + '</p>';
                }
            } catch (error) {
                console.error('Error fetching seats:', error);
                document.getElementById('seatsContent').innerHTML = '<p class="message error">Error loading seats</p>';
            }
        }

        function renderSeatGrid(seatData) {
            const rows = seatData.total_rows;
            const cols = seatData.total_columns;
            const bookedSeats = new Set(seatData.booked_seats.map(s => `${s.row}-${s.col}`));

            let html = '<div class="legend">';
            html += '<div class="legend-item"><div class="legend-seat legend-available"></div><span>Available</span></div>';
            html += '<div class="legend-item"><div class="legend-seat legend-booked"></div><span>Booked</span></div>';
            html += '<div class="legend-item"><div class="legend-seat legend-selected"></div><span>Selected</span></div>';
            html += '</div>';

            html += '<div style="text-align: center; margin-bottom: 1rem;"><strong style="color: var(--text-primary);">SCREEN</strong></div>';
            html += '<div class="seat-grid">';

            for (let row = 0; row < rows; row++) {
                html += '<div class="seat-row">';
                html += `<div class="row-label">${String.fromCharCode(65 + row)}</div>`;

                for (let col = 0; col < cols; col++) {
                    const seatId = `${row}-${col}`;
                    const isBooked = bookedSeats.has(seatId);
                    const seatLabel = String.fromCharCode(65 + row) + (col + 1);

                    html += '<div class="seat ' + (isBooked ? 'booked' : '') + '" ' +
                        'data-seat-id="' + seatId + '" ' +
                        'data-seat-label="' + seatLabel + '" ' +
                        'title="' + seatLabel + '">' +
                        seatLabel +
                    '</div>';
                }

                html += '</div>';
            }

            html += '</div>';

            html += '<div class="summary">';
            html += '<div class="summary-title">YOUR SELECTION:</div>';
            html += '<div class="selected-seats" id="selectedList">No seats selected</div>';
            html += '<div class="price-summary">';
            html += '<div class="price-row"><span>Seats × Price:</span><span id="priceDetail">--</span></div>';
            html += '<div class="price-total"><span>Total:</span><span id="totalPrice">৳0</span></div>';
            html += '</div>';
            html += '</div>';

            html += '<div class="btn-container">';
            html += '<button class="btn btn-secondary" onclick="clearSelection()">Clear Selection</button>';
            html += '<button class="btn btn-primary" id="bookBtn" onclick="proceedToBooking()" disabled>Proceed to Booking</button>';
            html += '</div>';

            document.getElementById('seatsContent').innerHTML = html;

            // Attach click handler via event delegation (avoids inline onclick quoting issues)
            document.getElementById('seatsContent').addEventListener('click', function(e) {
                const seat = e.target.closest('.seat');
                if (!seat || seat.classList.contains('booked')) return;
                const id    = seat.getAttribute('data-seat-id');
                const label = seat.getAttribute('data-seat-label');
                toggleSeat(id, label);
            });
        }

        function toggleSeat(seatId, seatLabel) {
            const seatElement = document.querySelector(`[data-seat-id="${seatId}"]`);
            if (seatElement.classList.contains('booked')) return;

            const index = selectedSeats.findIndex(s => s.id === seatId);
            if (index > -1) {
                selectedSeats.splice(index, 1);
                seatElement.classList.remove('selected');
            } else {
                selectedSeats.push({ id: seatId, label: seatLabel });
                seatElement.classList.add('selected');
            }

            updateSeatSummary();
        }

        function updateSeatSummary() {
            const selectedList = document.getElementById('selectedList');
            const bookBtn = document.getElementById('bookBtn');

            if (selectedSeats.length === 0) {
                selectedList.textContent = 'No seats selected';
                bookBtn.disabled = true;
                document.getElementById('priceDetail').textContent = '--';
                document.getElementById('totalPrice').textContent = '৳0';
            } else {
                const seatLabels = selectedSeats.map(s => s.label).join(', ');
                selectedList.textContent = `${seatLabels} (${selectedSeats.length} ${selectedSeats.length === 1 ? 'seat' : 'seats'})`;
                
                const showSelect = document.getElementById('showSelect');
                const selectedOption = showSelect.options[showSelect.selectedIndex];
                const ticketPrice = parseFloat(selectedOption.dataset.price) || 0;
                
                const subtotal = selectedSeats.length * ticketPrice;
                const total = subtotal;

                document.getElementById('priceDetail').textContent = `৳${subtotal}`;
                document.getElementById('totalPrice').textContent = `৳${total}`;
                bookBtn.disabled = false;
            }
        }

        function clearSelection() {
            selectedSeats = [];
            document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
            updateSeatSummary();
        }

        function updateShowInfo() {
            const showSelect = document.getElementById('showSelect');
            const selectedOption = showSelect.options[showSelect.selectedIndex];
            if (selectedOption.value) {
                const text = selectedOption.textContent;
                document.getElementById('showDetails').innerHTML = text;
                document.getElementById('showInfo').style.display = 'block';
            }
        }

        function proceedToBooking() {
            if (selectedSeats.length === 0) {
                alert('Please select at least one seat');
                return;
            }

            const showId = document.getElementById('showSelect').value;
            const seatData = selectedSeats.map(s => s.id).join(',');
            window.location.href = `booking-handler.php?s_id=${showId}&seats=${seatData}`;
        }
    </script>
</body>
</html>