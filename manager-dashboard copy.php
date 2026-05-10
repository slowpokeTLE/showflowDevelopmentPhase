<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Check manager access
if (!hasRole(ROLE_MANAGER)) {
    header('Location: manager-login.php');
    exit();
}

$m_id = $_SESSION['m_id'];
$t_id = $_SESSION['t_id'];
$name = $_SESSION['name'];

// Fetch theatre details
$theatre_query = "SELECT * FROM theatre WHERE t_id = ?";
$stmt = $conn->prepare($theatre_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$theatre = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch all movies (not filtered by theatre - movies are system-wide)
$movies_query = "SELECT * FROM movie ORDER BY created_at DESC";
$movies_result = $conn->query($movies_query);
$movies = [];
if ($movies_result) {
    while($row = $movies_result->fetch_assoc()) {
        $movies[] = $row;
    }
}

// Fetch all halls for this theatre
$halls_query = "SELECT * FROM hall WHERE t_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($halls_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$halls_result = $stmt->get_result();
$halls = [];
while($row = $halls_result->fetch_assoc()) {
    $halls[] = $row;
}
$stmt->close();

// Fetch all shows for this theatre
$shows_query = "
    SELECT ss.*, m.mov_name, h.hall_name
    FROM show_schedule ss
    JOIN movie m ON ss.mov_id = m.mov_id
    JOIN hall h ON ss.h_id = h.h_id
    WHERE ss.t_id = ?
    ORDER BY ss.show_date DESC, ss.show_time DESC
    LIMIT 20
";
$stmt = $conn->prepare($shows_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$shows_result = $stmt->get_result();
$shows = [];
while($row = $shows_result->fetch_assoc()) {
    $shows[] = $row;
}
$stmt->close();

// Fetch food items
$food_query = "SELECT * FROM food_item WHERE t_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($food_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$food_result = $stmt->get_result();
$food_items = [];
while($row = $food_result->fetch_assoc()) {
    $food_items[] = $row;
}
$stmt->close();

// Fetch expenses
$expense_query = "SELECT * FROM expense WHERE t_id = ? ORDER BY ex_date DESC LIMIT 10";
$stmt = $conn->prepare($expense_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$expense_result = $stmt->get_result();
$expenses = [];
while($row = $expense_result->fetch_assoc()) {
    $expenses[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 2rem;
        }
        
        .dashboard-title {
            font-size: 32px;
            font-weight: bold;
            color: var(--accent-red);
        }
        
        .theatre-info {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .profile-btn {
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 20px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .action-card {
            background: linear-gradient(135deg, var(--secondary-bg) 0%, var(--tertiary-bg) 100%);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .action-card:hover {
            border-color: var(--accent-red);
            background: linear-gradient(135deg, var(--tertiary-bg) 0%, var(--secondary-bg) 100%);
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(229, 9, 20, 0.3);
        }
        
        .action-icon {
            font-size: 40px;
            margin-bottom: 0.5rem;
        }
        
        .action-title {
            font-size: 14px;
            font-weight: bold;
            color: var(--text-primary);
        }
        
        .data-section {
            margin-top: 3rem;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .table-container {
            overflow-x: auto;
            background: var(--secondary-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent-red);
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">🎬 ShowFlow</a>
            <nav>
                <ul>
                    <li><a href="#">Manager Portal</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <button class="profile-btn" onclick="toggleProfile()">👤</button>
                <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
            </div>
        </div>
    </header>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Manager Profile</h2>
                <button class="modal-close" onclick="toggleProfile()">✕</button>
            </div>
            <div style="padding: 1rem;">
                <div class="form-group">
                    <label>Manager Name:</label>
                    <p style="color: var(--text-primary); font-weight: bold;"><?php echo htmlspecialchars($name); ?></p>
                </div>
                <div class="form-group">
                    <label>Manager ID:</label>
                    <p style="color: var(--text-secondary);"><code><?php echo htmlspecialchars($m_id); ?></code></p>
                </div>
                <div class="form-group">
                    <label>Theatre:</label>
                    <p style="color: var(--accent-red); font-weight: bold;"><?php echo htmlspecialchars($theatre['theatre_name']); ?></p>
                </div>
                <div class="form-group">
                    <label>Location:</label>
                    <p style="color: var(--text-primary);"><?php echo htmlspecialchars($theatre['location']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <div class="dashboard-title">🎬 Manager Dashboard</div>
                <div class="theatre-info">
                    📍 <?php echo htmlspecialchars($theatre['theatre_name']); ?> • <?php echo htmlspecialchars($theatre['location']); ?>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($movies); ?></div>
                <div class="stat-label">Movies Added</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($halls); ?></div>
                <div class="stat-label">Halls/Screens</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($shows); ?></div>
                <div class="stat-label">Show Schedules</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($food_items); ?></div>
                <div class="stat-label">Food Items</div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="actions-grid">
            <div class="action-card" onclick="openAddMovieModal()">
                <div class="action-icon">🎥</div>
                <div class="action-title">Add Movie</div>
            </div>
            
            <div class="action-card" onclick="openAddHallModal()">
                <div class="action-icon">🎭</div>
                <div class="action-title">Add Hall</div>
            </div>
            
            <div class="action-card" onclick="openCreateShowModal()">
                <div class="action-icon">🕐</div>
                <div class="action-title">Create Show</div>
            </div>
            
            <div class="action-card" onclick="openSeatStatusModal()">
                <div class="action-icon">💺</div>
                <div class="action-title">Seat Status</div>
            </div>
            
            <div class="action-card" onclick="openMovieEarningsModal()">
                <div class="action-icon">💰</div>
                <div class="action-title">Earnings</div>
            </div>
            
            <div class="action-card" onclick="openFoodManagementModal()">
                <div class="action-icon">🍿</div>
                <div class="action-title">Food Items</div>
            </div>
            
            <div class="action-card" onclick="openAddExpenseModal()">
                <div class="action-icon">💸</div>
                <div class="action-title">Add Expense</div>
            </div>
            
            <div class="action-card" onclick="openContractModal()">
                <div class="action-icon">📋</div>
                <div class="action-title">Contracts</div>
            </div>
        </div>

        <!-- Movies Section -->
        <div class="data-section">
            <div class="section-title">🎥 Movies System Wide</div>
            <?php if (count($movies) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Movie ID</th>
                                <th>Movie Name</th>
                                <th>Poster</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movies as $movie): ?>
                                <tr>
                                    <td>#<?php echo $movie['mov_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($movie['mov_name']); ?></strong></td>
                                    <td><?php echo $movie['mov_poster'] ? '✓' : '-'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($movie['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No movies yet. Movies are added by system.
                </div>
            <?php endif; ?>
        </div>

        <!-- Halls Section -->
        <div class="data-section">
            <div class="section-title">🎭 Halls/Screens</div>
            <?php if (count($halls) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Hall ID</th>
                                <th>Hall Name</th>
                                <th>Rows × Cols</th>
                                <th>Total Seats</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($halls as $hall): ?>
                                <tr>
                                    <td>#<?php echo $hall['h_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($hall['hall_name']); ?></strong></td>
                                    <td><?php echo $hall['total_rows'] . ' × ' . $hall['total_columns']; ?></td>
                                    <td><?php echo $hall['total_rows'] * $hall['total_columns']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No halls yet. <a href="#" onclick="openAddHallModal()" style="color: var(--accent-red); text-decoration: underline;">Add one</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Shows Section -->
        <div class="data-section">
            <div class="section-title">🕐 Show Schedules</div>
            <?php if (count($shows) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Show ID</th>
                                <th>Movie</th>
                                <th>Hall</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shows as $show): ?>
                                <tr>
                                    <td>#<?php echo $show['s_id']; ?></td>
                                    <td><?php echo htmlspecialchars($show['mov_name']); ?></td>
                                    <td><?php echo htmlspecialchars($show['hall_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($show['show_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($show['show_time'])); ?></td>
                                    <td>₹<?php echo number_format($show['ticket_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No shows yet. <a href="#" onclick="openCreateShowModal()" style="color: var(--accent-red); text-decoration: underline;">Create one</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Food Items Section -->
        <div class="data-section">
            <div class="section-title">🍿 Food Menu</div>
            <?php if (count($food_items) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Food ID</th>
                                <th>Item Name</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($food_items as $food): ?>
                                <tr>
                                    <td>#<?php echo $food['food_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($food['food_name']); ?></strong></td>
                                    <td>₹<?php echo number_format($food['price'], 2); ?></td>
                                    <td>
                                        <button class="btn-small btn-edit" onclick="deleteFood(<?php echo $food['food_id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No food items yet. <a href="#" onclick="openFoodManagementModal()" style="color: var(--accent-red); text-decoration: underline;">Add one</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Movie Modal -->
    <div id="addMovieModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Movie</h2>
                <button class="modal-close" onclick="closeAddMovieModal()">✕</button>
            </div>
            <form id="addMovieForm" onsubmit="submitAddMovie(event)">
                <input type="hidden" name="action" value="add_movie">
                <div class="form-group">
                    <label>Movie Name</label>
                    <input type="text" name="mov_name" placeholder="e.g., Avatar: The Way of Water" required>
                </div>
                <div class="form-group">
                    <label>Poster Link (optional)</label>
                    <input type="text" name="mov_poster" placeholder="https://example.com/poster.jpg">
                </div>
                <div class="form-group">
                    <label>Trailer Link (optional)</label>
                    <input type="text" name="mov_trailer" placeholder="https://youtube.com/...">
                </div>
                <div class="form-group">
                    <label>Genre (optional)</label>
                    <input type="text" name="mov_genre" placeholder="e.g., Action, Drama, Comedy">
                </div>
                <div class="form-group">
                    <label>Duration (minutes - optional)</label>
                    <input type="number" name="mov_duration" min="0" placeholder="e.g., 120">
                </div>
                <div class="form-group">
                    <label>Release Date (optional)</label>
                    <input type="date" name="mov_release_date">
                </div>
                <div class="form-group">
                    <label>Synopsis / Description</label>
                    <textarea name="mov_synopsis" placeholder="Brief plot summary and movie details..." rows="4" style="resize: vertical;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Movie</button>
            </form>
        </div>
    </div>

    <!-- Add Hall Modal -->
    <div id="addHallModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Hall/Screen</h2>
                <button class="modal-close" onclick="closeAddHallModal()">✕</button>
            </div>
            <form id="addHallForm" onsubmit="submitAddHall(event)">
                <input type="hidden" name="action" value="add_hall">
                <div class="form-group">
                    <label>Hall Name</label>
                    <input type="text" name="hall_name" placeholder="e.g., Screen 1, Hall A" required>
                </div>
                <div class="form-group">
                    <label>Total Rows</label>
                    <input type="number" name="total_rows" min="1" max="50" placeholder="e.g., 10" required>
                </div>
                <div class="form-group">
                    <label>Total Columns</label>
                    <input type="number" name="total_columns" min="1" max="50" placeholder="e.g., 15" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Hall</button>
            </form>
        </div>
    </div>

    <!-- Create Show Modal -->
    <div id="createShowModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create Show Schedule</h2>
                <button class="modal-close" onclick="closeCreateShowModal()">✕</button>
            </div>
            <form id="createShowForm" onsubmit="submitCreateShow(event)">
                <input type="hidden" name="action" value="create_show">
                <div class="form-group">
                    <label>Select Movie</label>
                    <select name="mov_id" required>
                        <option value="">-- Select Movie --</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?php echo $movie['mov_id']; ?>"><?php echo htmlspecialchars($movie['mov_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Hall</label>
                    <select name="h_id" required>
                        <option value="">-- Select Hall --</option>
                        <?php foreach ($halls as $hall): ?>
                            <option value="<?php echo $hall['h_id']; ?>"><?php echo htmlspecialchars($hall['hall_name']); ?> (<?php echo $hall['total_rows'] . 'x' . $hall['total_columns']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Show Date</label>
                    <input type="date" name="show_date" required>
                </div>
                <div class="form-group">
                    <label>Show Time</label>
                    <input type="time" name="show_time" required>
                </div>
                <div class="form-group">
                    <label>Ticket Price (₹)</label>
                    <input type="number" name="ticket_price" min="0.01" step="0.01" placeholder="e.g., 250" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Show</button>
            </form>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Expense</h2>
                <button class="modal-close" onclick="closeAddExpenseModal()">✕</button>
            </div>
            <form id="addExpenseForm" onsubmit="submitAddExpense(event)">
                <input type="hidden" name="action" value="add_expense">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="ex_date" required>
                </div>
                <div class="form-group">
                    <label>Expense Reason</label>
                    <input type="text" name="ex_reason" placeholder="e.g., Staff Salary, Maintenance" required>
                </div>
                <div class="form-group">
                    <label>Cost (₹)</label>
                    <input type="number" name="cost" min="0.01" step="0.01" placeholder="e.g., 5000" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </form>
        </div>
    </div>

    <!-- Food Management Modal -->
    <div id="foodManagementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Food Management</h2>
                <button class="modal-close" onclick="closeFoodManagementModal()">✕</button>
            </div>
            <form id="foodManagementForm" onsubmit="submitAddFood(event)">
                <input type="hidden" name="action" value="add_food">
                <div class="form-group">
                    <label>Food Item Name</label>
                    <input type="text" name="food_name" placeholder="e.g., Popcorn, Coke" required>
                </div>
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" min="0.01" step="0.01" placeholder="e.g., 150" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Food Item</button>
            </form>
        </div>
    </div>

    <!-- Seat Status Modal (Placeholder for Phase 3) -->
    <div id="seatStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Seat Status Viewer</h2>
                <button class="modal-close" onclick="closeSeatStatusModal()">✕</button>
            </div>
            <div style="padding: 1rem; color: var(--text-secondary);">
                <p>Select a show to view seat availability (2D grid coming in Phase 3)</p>
                <div class="form-group">
                    <select id="seatShowSelect">
                        <option>-- Select Show --</option>
                        <?php foreach ($shows as $show): ?>
                            <option value="<?php echo $show['s_id']; ?>">
                                <?php echo htmlspecialchars($show['mov_name']) . ' - ' . $show['hall_name'] . ' @ ' . date('h:i A', strtotime($show['show_time'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="font-size: 12px; margin-top: 1rem;">📋 Full 2D seat grid viewer coming in Phase 3</p>
            </div>
        </div>
    </div>

    <!-- Movie Earnings Modal (Placeholder) -->
    <div id="movieEarningsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Movie Earnings Analyzer</h2>
                <button class="modal-close" onclick="closeMovieEarningsModal()">✕</button>
            </div>
            <div style="padding: 1rem; color: var(--text-secondary);">
                <div class="form-group">
                    <label>Select Movie</label>
                    <select id="earningsMovieSelect">
                        <option>-- Select Movie --</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?php echo $movie['mov_id']; ?>"><?php echo htmlspecialchars($movie['mov_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="font-size: 12px; margin-top: 1rem;">💰 Earnings calculation coming in Phase 3:
                <br>• Total tickets sold
                <br>• Total earnings
                <br>• Distributor fee
                <br>• Net profit
                </p>
            </div>
        </div>
    </div>

    <!-- Contract Modal (Placeholder) -->
    <div id="contractModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Film Contracts</h2>
                <button class="modal-close" onclick="closeContractModal()">✕</button>
            </div>
            <div style="padding: 1rem; color: var(--text-secondary);">
                <p>Coming in Phase 3:</p>
                <ul style="margin-top: 1rem;">
                    <li>• Add distribution contracts</li>
                    <li>• Set one-time costs</li>
                    <li>• Configure percentage per ticket</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function toggleProfile() {
            document.getElementById('profileModal').classList.toggle('active');
        }

        // Modal functions
        function openAddMovieModal() {
            document.getElementById('addMovieModal').classList.add('active');
        }
        function closeAddMovieModal() {
            document.getElementById('addMovieModal').classList.remove('active');
            document.getElementById('addMovieForm').reset();
        }

        function openAddHallModal() {
            document.getElementById('addHallModal').classList.add('active');
        }
        function closeAddHallModal() {
            document.getElementById('addHallModal').classList.remove('active');
            document.getElementById('addHallForm').reset();
        }

        function openCreateShowModal() {
            document.getElementById('createShowModal').classList.add('active');
        }
        function closeCreateShowModal() {
            document.getElementById('createShowModal').classList.remove('active');
            document.getElementById('createShowForm').reset();
        }

        function openAddExpenseModal() {
            document.getElementById('addExpenseModal').classList.add('active');
        }
        function closeAddExpenseModal() {
            document.getElementById('addExpenseModal').classList.remove('active');
            document.getElementById('addExpenseForm').reset();
        }

        function openFoodManagementModal() {
            document.getElementById('foodManagementModal').classList.add('active');
        }
        function closeFoodManagementModal() {
            document.getElementById('foodManagementModal').classList.remove('active');
            document.getElementById('foodManagementForm').reset();
        }

        function openSeatStatusModal() {
            document.getElementById('seatStatusModal').classList.add('active');
        }
        function closeSeatStatusModal() {
            document.getElementById('seatStatusModal').classList.remove('active');
        }

        function openMovieEarningsModal() {
            document.getElementById('movieEarningsModal').classList.add('active');
        }
        function closeMovieEarningsModal() {
            document.getElementById('movieEarningsModal').classList.remove('active');
        }

        function openContractModal() {
            document.getElementById('contractModal').classList.add('active');
        }
        function closeContractModal() {
            document.getElementById('contractModal').classList.remove('active');
        }

        // Form submissions
        function submitAddMovie(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('manager_operations_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Movie added!');
                    closeAddMovieModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }

        function submitAddHall(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('manager_operations_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Hall added!');
                    closeAddHallModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }

        function submitCreateShow(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('manager_operations_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Show created!');
                    closeCreateShowModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }

        function submitAddExpense(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('manager_operations_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Expense recorded!');
                    closeAddExpenseModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }

        function submitAddFood(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('food_management_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Food item added!');
                    closeFoodManagementModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }

        function deleteFood(foodId) {
            if (confirm('Delete this food item?')) {
                const formData = new FormData();
                formData.append('action', 'delete_food');
                formData.append('food_id', foodId);
                
                fetch('food_management_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('✅ Food item deleted!');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                });
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
