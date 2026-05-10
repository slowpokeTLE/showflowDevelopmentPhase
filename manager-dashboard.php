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

// Fetch upcoming shows for this theatre (from current time)
$upcoming_shows_query = "
    SELECT ss.*, m.mov_name, h.hall_name
    FROM show_schedule ss
    JOIN movie m ON ss.mov_id = m.mov_id
    JOIN hall h ON ss.h_id = h.h_id
    WHERE ss.t_id = ? AND CONCAT(ss.show_date, ' ', ss.show_time) >= NOW()
    ORDER BY ss.show_date ASC, ss.show_time ASC
    LIMIT 50
";
$stmt = $conn->prepare($upcoming_shows_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$shows_result = $stmt->get_result();
$upcoming_shows = [];
while($row = $shows_result->fetch_assoc()) {
    $upcoming_shows[] = $row;
}
$stmt->close();

// Fetch past shows for this theatre
$past_shows_query = "
    SELECT ss.*, m.mov_name, h.hall_name
    FROM show_schedule ss
    JOIN movie m ON ss.mov_id = m.mov_id
    JOIN hall h ON ss.h_id = h.h_id
    WHERE ss.t_id = ? AND CONCAT(ss.show_date, ' ', ss.show_time) < NOW()
    ORDER BY ss.show_date DESC, ss.show_time DESC
    LIMIT 50
";
$stmt = $conn->prepare($past_shows_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$shows_result = $stmt->get_result();
$past_shows = [];
while($row = $shows_result->fetch_assoc()) {
    $past_shows[] = $row;
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

// Fetch complaints for this theatre
$complaint_query = "
    SELECT c.comp_id, c.u_id, u.name AS user_name, c.comp_date, c.complaint_text, c.created_at
    FROM complaint c
    LEFT JOIN user u ON c.u_id = u.u_id
    WHERE c.t_id = ?
    ORDER BY c.created_at DESC
    LIMIT 25
";
$stmt = $conn->prepare($complaint_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$complaint_result = $stmt->get_result();
$complaints = [];
while ($row = $complaint_result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
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

        .complaint-message-cell {
            max-width: 320px;
            white-space: normal;
            line-height: 1.5;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo"><img src="showflowicon.png" alt="ShowFlow" style="height: 24px; width: auto;"> ShowFlow</a>
            <nav>
                <ul>
                    <li><a href="#">Manager Portal</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <button class="profile-btn" onclick="toggleProfile()">👤</button>
                <button class="btn btn-secondary btn-small" onclick="openEditManagerModal()">⚙️ Settings</button>
                <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
            </div>
        </div>
    </header>

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

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <div class="dashboard-title"><img src="showflowicon.png" alt="ShowFlow" style="height: 32px; width: auto; margin-right: 8px; display: inline;"> Manager Dashboard</div>
                <div class="theatre-info">
                    📍 <?php echo htmlspecialchars($theatre['theatre_name']); ?> • <?php echo htmlspecialchars($theatre['location']); ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $week_ago = date('Y-m-d', strtotime('-7 days'));
                    $tickets_query = "SELECT COUNT(*) as total FROM booking b
                        JOIN show_schedule s ON b.s_id = s.s_id
                        WHERE s.t_id = ? AND b.booking_date >= ?";
                    $stmt = $conn->prepare($tickets_query);
                    $stmt->bind_param("is", $t_id, $week_ago);
                    $stmt->execute();
                    $tickets_result = $stmt->get_result()->fetch_assoc();
                    echo $tickets_result['total'] ?? 0;
                    $stmt->close();
                    ?>
                </div>
                <div class="stat-label">Tickets Sold (Last Week)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($halls); ?></div>
                <div class="stat-label">Halls/Screens</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($upcoming_shows); ?></div>
                <div class="stat-label">Upcoming Shows</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($food_items); ?></div>
                <div class="stat-label">Food Items</div>
            </div>
        </div>

        <div class="actions-grid">
            <div class="action-card" onclick="openAddMovieModal()">
                <div class="action-icon">🎥</div>
                <div class="action-title">Add Movie</div>
            </div>

            <div class="action-card" onclick="openEditMovieModal()">
                <div class="action-icon">✏️</div>
                <div class="action-title">Edit Movie</div>
            </div>
            
            <div class="action-card" onclick="openAddHallModal()">
                <div class="action-icon">🎭</div>
                <div class="action-title">Add Hall</div>
            </div>
            
            <div class="action-card" onclick="openCreateShowModal()">
                <div class="action-icon">🕐</div>
                <div class="action-title">Create Show</div>
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

            <div class="action-card" onclick="openMonthlyIncomeModal()">
                <div class="action-icon">📊</div>
                <div class="action-title">Monthly Income</div>
            </div>

            <div class="action-card" onclick="openConfirmFoodOrderModal()">
                <div class="action-icon">✅</div>
                <div class="action-title">Confirm Food Order</div>
            </div>

            <div class="action-card" onclick="openComplaintsModal()">
                <div class="action-icon">💬</div>
                <div class="action-title">See Complaints</div>
            </div>
        </div>

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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($halls as $hall): ?>
                                <tr>
                                    <td>#<?php echo $hall['h_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($hall['hall_name']); ?></strong></td>
                                    <td><?php echo $hall['total_rows'] . ' × ' . $hall['total_columns']; ?></td>
                                    <td><?php echo $hall['total_rows'] * $hall['total_columns']; ?></td>
                                    <td style="display: flex; gap: 8px;">
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="editHall(<?php echo $hall['h_id']; ?>, '<?php echo htmlspecialchars($hall['hall_name']); ?>', <?php echo $hall['total_rows']; ?>, <?php echo $hall['total_columns']; ?>)">✏️ Edit</button>
                                        <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; background: #ef4444;" onclick="deleteHall(<?php echo $hall['h_id']; ?>, '<?php echo htmlspecialchars($hall['hall_name']); ?>')">🗑️ Delete</button>
                                    </td>
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

        <div class="data-section">
            <div class="section-title">🕐 Upcoming Show Schedules</div>
            <?php if (count($upcoming_shows) > 0): ?>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_shows as $show): ?>
                                <tr>
                                    <td>#<?php echo $show['s_id']; ?></td>
                                    <td><?php echo htmlspecialchars($show['mov_name']); ?></td>
                                    <td><?php echo htmlspecialchars($show['hall_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($show['show_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($show['show_time'])); ?></td>
                                    <td>৳<?php echo number_format($show['ticket_price'], 2); ?></td>
                                    <td style="display: flex; gap: 8px;">
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openEditShowModal(<?php echo $show['s_id']; ?>, '<?php echo htmlspecialchars($show['mov_name']); ?>', <?php echo $show['h_id']; ?>, '<?php echo $show['show_time']; ?>', <?php echo $show['ticket_price']; ?>)">✏️ Edit</button>
                                        <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; background: #ef4444;" onclick="deleteShow(<?php echo $show['s_id']; ?>, '<?php echo htmlspecialchars($show['mov_name']); ?>')">🗑️ Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No upcoming shows. <a href="#" onclick="openCreateShowModal()" style="color: var(--accent-red); text-decoration: underline;">Create one</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="data-section">
            <div class="section-title">📜 Past Show Schedules</div>
            <div style="cursor: pointer; user-select: none; display: flex; align-items: center; gap: 8px;" onclick="togglePastShows()" id="pastShowsToggle">
                <span id="pastShowsToggleIcon">▶</span>
                <span style="color: var(--text-secondary);">Click to expand past shows</span>
            </div>
            <div id="pastShowsContent" style="display: none; margin-top: 1rem;">
                <?php if (count($past_shows) > 0): ?>
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
                                <?php foreach ($past_shows as $show): ?>
                                    <tr style="opacity: 0.7;">
                                        <td>#<?php echo $show['s_id']; ?></td>
                                        <td><?php echo htmlspecialchars($show['mov_name']); ?></td>
                                        <td><?php echo htmlspecialchars($show['hall_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($show['show_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($show['show_time'])); ?></td>
                                        <td>৳<?php echo number_format($show['ticket_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        📭 No past shows
                    </div>
                <?php endif; ?>
            </div>
        </div>

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
                                    <td>৳<?php echo number_format($food['price'], 2); ?></td>
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
                <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Add Movie</button>
            </form>
        </div>
    </div>

    <div id="editMovieModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Existing Movie</h2>
                <button class="modal-close" onclick="closeEditMovieModal()">✕</button>
            </div>
            <form id="editMovieForm" onsubmit="submitEditMovie(event)">
                <input type="hidden" name="action" value="edit_movie">
                <div class="form-group">
                    <label>Select Movie to Edit</label>
                    <select name="mov_id" onchange="populateEditMovieForm(this.value)" required>
                        <option value="">-- Select Movie --</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?php echo $movie['mov_id']; ?>"><?php echo htmlspecialchars($movie['mov_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="editMovieFields" style="display: none;">
                    <div class="form-group">
                        <label>Movie Name</label>
                        <input type="text" name="mov_name" id="edit_mov_name" required>
                    </div>
                    <div class="form-group">
                        <label>Poster Link (optional)</label>
                        <input type="text" name="mov_poster" id="edit_mov_poster">
                    </div>
                    <div class="form-group">
                        <label>Trailer Link (optional)</label>
                        <input type="text" name="mov_trailer" id="edit_mov_trailer">
                    </div>
                    <div class="form-group">
                        <label>Genre (optional)</label>
                        <input type="text" name="mov_genre" id="edit_mov_genre">
                    </div>
                    <div class="form-group">
                        <label>Duration (minutes - optional)</label>
                        <input type="number" name="mov_duration" id="edit_mov_duration" min="0">
                    </div>
                    <div class="form-group">
                        <label>Release Date (optional)</label>
                        <input type="date" name="mov_release_date" id="edit_mov_release_date">
                    </div>
                    <div class="form-group">
                        <label>Synopsis / Description</label>
                        <textarea name="mov_synopsis" id="edit_mov_synopsis" rows="4" style="resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

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

    <div id="editHallModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Hall/Screen</h2>
                <button class="modal-close" onclick="closeEditHallModal()">✕</button>
            </div>
            <form id="editHallForm" method="POST" onsubmit="submitEditHall(event)">
                <input type="hidden" name="action" value="edit_hall">
                <input type="hidden" id="editHallIdInput" name="hall_id">
                <div class="form-group">
                    <label>Hall Name</label>
                    <input type="text" id="editHallNameInput" name="hall_name" placeholder="e.g., Screen 1, Hall A" required>
                </div>
                <div class="form-group">
                    <label>Total Rows</label>
                    <input type="number" id="editHallRowsInput" name="total_rows" min="1" max="50" placeholder="e.g., 10" required>
                </div>
                <div class="form-group">
                    <label>Total Columns</label>
                    <input type="number" id="editHallColsInput" name="total_columns" min="1" max="50" placeholder="e.g., 15" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditHallModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

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
                    <label>Ticket Price (৳)</label>
                    <input type="number" name="ticket_price" min="0.01" step="0.01" placeholder="e.g., 250" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Show</button>
            </form>
        </div>
    </div>

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
                    <label>Cost (৳)</label>
                    <input type="number" name="cost" min="0.01" step="0.01" placeholder="e.g., 5000" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </form>
        </div>
    </div>

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
                    <label>Price (৳)</label>
                    <input type="number" name="price" min="0.01" step="0.01" placeholder="e.g., 150" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Food Item</button>
            </form>
        </div>
    </div>

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

    <div id="movieEarningsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Movie Earnings Analyzer</h2>
                <button class="modal-close" onclick="closeMovieEarningsModal()">✕</button>
            </div>
            <div style="padding: 1rem;">
                <div class="form-group">
                    <label>Select Movie</label>
                    <select id="earningsMovieSelect" onchange="calculateMovieEarnings()">
                        <option value="">-- Select Movie --</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?php echo $movie['mov_id']; ?>"><?php echo htmlspecialchars($movie['mov_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="earningsContainer" style="margin-top: 2rem; display: none;">
                    <div class="stats-grid" style="gap: 1rem;">
                        <div class="stat-card">
                            <div class="stat-label">Tickets Sold</div>
                            <div class="stat-number" id="ticketsSold">0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-number" id="totalRevenue">৳0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Contract Fee</div>
                            <div class="stat-number" id="contractFeeDisplay">৳0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Ticket Fee %</div>
                            <div class="stat-number" id="ticketFeePercent">0%</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; background: var(--secondary-bg); padding: 1rem; border-radius: 8px; border-left: 4px solid var(--accent-red);">
                        <h3 style="color: var(--accent-red); margin-bottom: 1rem;">Earnings Breakdown</h3>
                        <div style="font-size: 14px; color: var(--text-secondary); line-height: 2;">
                            <div>Ticket Price × Tickets Sold: <span id="grossCalc" style="color: var(--text-primary); font-weight: bold;">৳0</span></div>
                            <div>- One-Time Cost: <span id="oneCostDisplay" style="color: var(--text-primary); font-weight: bold;">৳0</span></div>
                            <div>- Ticket Percentage Fee: <span id="ticketFeeDisplay" style="color: var(--text-primary); font-weight: bold;">৳0</span></div>
                            <div style="border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">
                                <strong style="color: var(--accent-red); font-size: 16px;">Theatre Net Earning: <span id="netEarning">৳0</span></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="contractModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Film Contracts</h2>
                <button class="modal-close" onclick="closeContractModal()">✕</button>
            </div>
            <div style="padding: 1rem;">
                <form id="contractForm" onsubmit="submitContract(event)">
                    <div class="form-group">
                        <label>Select Movie</label>
                        <select name="mov_id" id="contractMovieSelect" required onchange="checkExistingContract()">
                            <option value="">-- Select Movie --</option>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?php echo $movie['mov_id']; ?>"><?php echo htmlspecialchars($movie['mov_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>One-Time Cost Fee (৳)</label>
                        <input type="number" name="one_time_cost" id="contractCost" step="0.01" min="0" placeholder="e.g., 50000" required>
                    </div>
                    <div class="form-group">
                        <label>Ticket Percentage to Owner (%)</label>
                        <input type="number" name="percentage_per_ticket" id="contractPercent" step="0.01" min="0" max="100" placeholder="e.g., 40" required>
                    </div>
                    <div style="background: var(--secondary-bg); padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 12px; color: var(--text-secondary);">
                        <p>ℹ️ <strong>One-Time Cost:</strong> Fixed fee to be paid to distributor<br>
                        <strong>Ticket Percentage:</strong> % of each ticket price to be given to owner</p>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Contract</button>
                </form>
            </div>
        </div>
    </div>

    <div id="monthlyIncomeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Monthly Income Report</h2>
                <button class="modal-close" onclick="closeMonthlyIncomeModal()">✕</button>
            </div>
            <div style="padding: 1rem;">
                <div class="form-group">
                    <label>Select Month & Year</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <select id="monthSelect" required>
                            <option value="">-- Month --</option>
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                        <select id="yearSelect" required>
                            <option value="">-- Year --</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="generateMonthlyReport()">Generate Report</button>
                
                <div id="monthlyReportContainer" style="margin-top: 2rem; display: none;">
                    <div style="background: var(--secondary-bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--accent-red);">
                        <h3 style="color: var(--accent-red); margin-bottom: 1rem;">Monthly Report - <span id="reportMonth"></span></h3>
                        
                        <div class="stats-grid" style="gap: 1rem;">
                            <div class="stat-card">
                                <div class="stat-label">Booking Revenue</div>
                                <div class="stat-number" id="bookingRevenue">৳0</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Food Sales</div>
                                <div class="stat-number" id="foodSales">৳0</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Total Expenses</div>
                                <div class="stat-number" id="totalExpenses">৳0</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">Contract Fees</div>
                                <div class="stat-number" id="contractFees">৳0</div>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: var(--accent-red);">
                                <span>💰 Net Profit:</span>
                                <span id="netProfit">৳0</span>
                            </div>
                        </div>

                        <div id="breakdownDetails" style="margin-top: 1rem; font-size: 12px; color: var(--text-secondary);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Edit Show -->
    <div id="editShowModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">✏️ Edit Show</h2>
                <button class="modal-close" onclick="closeEditShowModal()">✕</button>
            </div>
            <form id="editShowForm" onsubmit="submitEditShow(event)">
                <input type="hidden" name="action" value="edit_show">
                <input type="hidden" name="s_id" id="editShowId">
                <div class="form-group">
                    <label>Movie Name (Read-only)</label>
                    <input type="text" id="editShowMovieName" readonly style="background: var(--tertiary-bg); cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>New Ticket Price (৳)</label>
                    <input type="number" name="ticket_price" id="editShowPrice" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>New Show Time</label>
                    <input type="time" name="show_time" id="editShowTime" required>
                </div>
                <div class="form-group">
                    <label>Select Hall</label>
                    <select name="h_id" id="editShowHall" required>
                        <option value="">-- Select Hall --</option>
                        <?php foreach ($halls as $hall): ?>
                            <option value="<?php echo $hall['h_id']; ?>"><?php echo htmlspecialchars($hall['hall_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="editShowMessage" style="display: none; margin: 1rem 0; padding: 0.75rem 1rem; border-radius: 6px;"></div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- MODAL: Confirm Food Order -->
    <div id="confirmFoodOrderModal" class="modal">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header">
                <h2 class="modal-title">✅ Confirm Food Order</h2>
                <button class="modal-close" onclick="closeConfirmFoodOrderModal()">✕</button>
            </div>
            <div style="padding: 1.5rem;">
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 13px;">
                    Enter the Order ID shown to the customer (e.g. <code style="color: var(--accent-red);">ORDER-000005</code> → enter <strong style="color: var(--text-primary);">5</strong>), then click Confirm to mark it as Delivered.
                </p>
                <div class="form-group">
                    <label>Order ID</label>
                    <input type="number" id="confirmOrderIdInput" min="1" placeholder="e.g. 5" style="width: 100%;">
                </div>
                <div id="confirmOrderMessage" style="display: none; margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 6px;"></div>
                <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;" onclick="submitConfirmFoodOrder()">
                    Confirm Delivery
                </button>
            </div>
        </div>
    </div>

    <div id="complaintsModal" class="modal">
        <div class="modal-content" style="max-width: 1200px; width: 96%;">
            <div class="modal-header">
                <h2 class="modal-title">💬 Theatre Complaints</h2>
                <button class="modal-close" onclick="closeComplaintsModal()">✕</button>
            </div>
            <div style="padding: 1rem;">
                <?php if (count($complaints) > 0): ?>
                    <div class="table-container" style="margin-bottom: 0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($complaint['comp_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['user_name'] ?: $complaint['u_id']); ?></td>
                                        <td class="complaint-message-cell"><?php echo htmlspecialchars($complaint['complaint_text']); ?></td>
                                        <td><?php echo date('d M Y h:i A', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <select id="status_<?php echo $complaint['comp_id']; ?>" style="padding: 4px 8px; border-radius: 4px; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color);">
                                                <option value="Not Seen">Not Seen</option>
                                                <option value="Seen">Seen</option>
                                                <option value="Working">Working</option>
                                                <option value="Resolved">Resolved</option>
                                            </select>
                                        </td>
                                        <td style="display: flex; gap: 8px;">
                                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;" onclick="updateComplaintStatus(<?php echo $complaint['comp_id']; ?>)">Update Status</button>
                                            <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;" onclick="sendMessageToUser('<?php echo htmlspecialchars($complaint['u_id']); ?>', '<?php echo str_pad($complaint['comp_id'], 5, '0', STR_PAD_LEFT); ?>')">Send Message</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="margin-bottom: 0;">
                        📭 No complaints found for this theatre.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Manager Profile Modal -->
    <div id="editManagerModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 class="modal-title">Edit Manager Profile</h2>
                <button class="modal-close" onclick="closeEditManagerModal()">✕</button>
            </div>
            <form id="editManagerForm" onsubmit="submitEditManager(event)">
                <div class="form-group">
                    <label>Manager Name</label>
                    <input type="text" name="name" id="managerNameInput" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact" id="managerContactInput" required>
                </div>
                <div class="form-group">
                    <label>New Password (optional)</label>
                    <input type="password" name="password" id="managerPasswordInput" placeholder="Leave blank to keep current password">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="managerConfirmPasswordInput" placeholder="Re-enter new password">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div id="sendMessageModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 class="modal-title">Send Message to User</h2>
                <button class="modal-close" onclick="closeSendMessageModal()">✕</button>
            </div>
            <form id="sendMessageForm" onsubmit="submitSendMessage(event)">
                <input type="hidden" id="messageUserId" name="u_id">
                <input type="hidden" id="messageComplaintId" name="complaint_id">
                <div class="form-group">
                    <label>Message</label>
                    <textarea id="messageText" name="message" rows="6" placeholder="Type your message..." required style="resize: vertical;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
            </form>
        </div>
    </div>

    <script>
        // Pass PHP movies array to JavaScript for the edit auto-fill
        const moviesData = <?php echo json_encode($movies); ?>;

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

        // Edit Movie Modal functions
        function openEditMovieModal() {
            document.getElementById('editMovieModal').classList.add('active');
        }
        function closeEditMovieModal() {
            document.getElementById('editMovieModal').classList.remove('active');
            document.getElementById('editMovieForm').reset();
            document.getElementById('editMovieFields').style.display = 'none';
        }

        function openAddHallModal() {
            document.getElementById('addHallModal').classList.add('active');
        }
        function closeAddHallModal() {
            document.getElementById('addHallModal').classList.remove('active');
            document.getElementById('addHallForm').reset();
        }

        function editHall(hallId, hallName, rows, cols) {
            document.getElementById('editHallIdInput').value = hallId;
            document.getElementById('editHallNameInput').value = hallName;
            document.getElementById('editHallRowsInput').value = rows;
            document.getElementById('editHallColsInput').value = cols;
            document.getElementById('editHallModal').classList.add('active');
        }

        function closeEditHallModal() {
            document.getElementById('editHallModal').classList.remove('active');
            document.getElementById('editHallForm').reset();
        }

        function deleteHall(hallId, hallName) {
            if (confirm(`Are you sure you want to delete hall "${hallName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_hall">
                    <input type="hidden" name="hall_id" value="${hallId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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
            // Removed - Seat Status feature discontinued
        }
        function closeSeatStatusModal() {
            // Removed - Seat Status feature discontinued
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
            document.getElementById('contractForm').reset();
        }

        function openMonthlyIncomeModal() {
            document.getElementById('monthlyIncomeModal').classList.add('active');
            // Populate year dropdown with current year and 2 years before
            const yearSelect = document.getElementById('yearSelect');
            const currentYear = new Date().getFullYear();
            if (yearSelect.options.length === 1) {
                for (let i = currentYear; i >= currentYear - 2; i--) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    yearSelect.appendChild(option);
                }
            }
        }
        function closeMonthlyIncomeModal() {
            document.getElementById('monthlyIncomeModal').classList.remove('active');
            document.getElementById('monthlyReportContainer').style.display = 'none';
        }

        function openConfirmFoodOrderModal() {
            document.getElementById('confirmOrderIdInput').value = '';
            const msg = document.getElementById('confirmOrderMessage');
            msg.style.display = 'none';
            msg.textContent = '';
            document.getElementById('confirmFoodOrderModal').classList.add('active');
        }
        function closeConfirmFoodOrderModal() {
            document.getElementById('confirmFoodOrderModal').classList.remove('active');
        }

        function openComplaintsModal() {
            document.getElementById('complaintsModal').classList.add('active');
        }

        function closeComplaintsModal() {
            document.getElementById('complaintsModal').classList.remove('active');
        }

        function openEditManagerModal() {
            document.getElementById('editManagerModal').classList.add('active');
        }

        function closeEditManagerModal() {
            document.getElementById('editManagerModal').classList.remove('active');
            document.getElementById('editManagerForm').reset();
        }

        function submitEditManager(event) {
            event.preventDefault();
            const name = document.getElementById('managerNameInput').value;
            const contact = document.getElementById('managerContactInput').value;
            const password = document.getElementById('managerPasswordInput').value;
            const confirmPassword = document.getElementById('managerConfirmPasswordInput').value;

            if (password && password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'edit_manager_profile');
            formData.append('name', name);
            formData.append('contact', contact);
            if (password) {
                formData.append('password', password);
            }

            fetch('manager_profile_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Profile updated successfully!');
                    closeEditManagerModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function sendMessageToUser(userId, complaintId) {
            document.getElementById('messageUserId').value = userId;
            document.getElementById('messageComplaintId').value = complaintId;
            document.getElementById('messageText').value = '';
            document.getElementById('sendMessageModal').classList.add('active');
        }

        function closeSendMessageModal() {
            document.getElementById('sendMessageModal').classList.remove('active');
            document.getElementById('sendMessageForm').reset();
        }

        function submitSendMessage(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'send_complaint_message');

            fetch('send_complaint_message_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Message sent successfully!');
                    closeSendMessageModal();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function updateComplaintStatus(complaintId) {
            const statusSelect = document.getElementById('status_' + complaintId);
            const newStatus = statusSelect.value;

            const formData = new FormData();
            formData.append('action', 'update_complaint_status');
            formData.append('complaint_id', complaintId);
            formData.append('status', newStatus);

            fetch('update_complaint_status_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Complaint status updated!');
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function submitConfirmFoodOrder() {
            const orderId = document.getElementById('confirmOrderIdInput').value.trim();
            const msg = document.getElementById('confirmOrderMessage');

            if (!orderId || parseInt(orderId) <= 0) {
                showConfirmMsg('Please enter a valid Order ID.', false);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'confirm_food_order');
            formData.append('order_id', orderId);

            fetch('food_order_confirm_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showConfirmMsg('✅ ' + data.message, true);
                    document.getElementById('confirmOrderIdInput').value = '';
                } else {
                    showConfirmMsg('❌ ' + data.message, false);
                }
            })
            .catch(error => {
                showConfirmMsg('❌ Network error: ' + error.message, false);
            });
        }

        function showConfirmMsg(text, success) {
            const msg = document.getElementById('confirmOrderMessage');
            msg.textContent = text;
            msg.style.display = 'block';
            msg.style.background = success ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)';
            msg.style.border = '1px solid ' + (success ? 'var(--success-color)' : 'var(--error-color)');
            msg.style.color = success ? 'var(--success-color)' : 'var(--error-color)';
        }

        // Contract function
        function checkExistingContract() {
            const movId = document.getElementById('contractMovieSelect').value;
            if (movId) {
                fetch('api_fetch_contract.php?mov_id=' + movId + '&t_id=' + <?php echo $t_id; ?>)
                    .then(response => response.json())
                    .then(data => {
                        if (data.contract) {
                            document.getElementById('contractCost').value = data.contract.one_time_cost;
                            document.getElementById('contractPercent').value = data.contract.percentage_per_ticket;
                        }
                    });
            }
        }

        function submitContract(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'save_contract');
            formData.append('t_id', <?php echo $t_id; ?>);
            
            fetch('contract_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Contract saved successfully!');
                    closeContractModal();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        // Earnings function
        function calculateMovieEarnings() {
            const movId = document.getElementById('earningsMovieSelect').value;
            if (!movId) {
                document.getElementById('earningsContainer').style.display = 'none';
                return;
            }

            fetch('api_movie_earnings.php?mov_id=' + movId + '&t_id=' + <?php echo $t_id; ?>)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const earnings = data.earnings;
                        
                        document.getElementById('ticketsSold').textContent = earnings.tickets_sold;
                        document.getElementById('totalRevenue').textContent = '৳' + parseFloat(earnings.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        const oneCost = parseFloat(earnings.one_time_cost) || 0;
                        document.getElementById('contractFeeDisplay').textContent = '৳' + oneCost.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        document.getElementById('oneCostDisplay').textContent = '৳' + oneCost.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        const percentage = parseFloat(earnings.percentage_per_ticket) || 0;
                        document.getElementById('ticketFeePercent').textContent = percentage + '%';
                        
                        const ticketFee = (parseFloat(earnings.total_amount) * percentage) / 100;
                        document.getElementById('ticketFeeDisplay').textContent = '৳' + ticketFee.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        document.getElementById('grossCalc').textContent = '৳' + parseFloat(earnings.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        const netEarning = parseFloat(earnings.total_amount) - oneCost - ticketFee;
                        document.getElementById('netEarning').textContent = '৳' + netEarning.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        document.getElementById('earningsContainer').style.display = 'block';
                    } else {
                        alert('Error fetching earnings: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        // Monthly Income Report
        function generateMonthlyReport() {
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            
            if (!month || !year) {
                alert('Please select both month and year');
                return;
            }

            fetch('api_monthly_income.php?month=' + month + '&year=' + year + '&t_id=' + <?php echo $t_id; ?>)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const report = data.report;
                        const monthName = new Date(year, parseInt(month) - 1).toLocaleString('default', {month: 'long', year: 'numeric'});
                        
                        document.getElementById('reportMonth').textContent = monthName;
                        document.getElementById('bookingRevenue').textContent = '৳' + parseFloat(report.booking_revenue).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        document.getElementById('foodSales').textContent = '৳' + parseFloat(report.food_sales).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        document.getElementById('totalExpenses').textContent = '৳' + parseFloat(report.total_expenses).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        document.getElementById('contractFees').textContent = '৳' + parseFloat(report.contract_fees).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        const netProfit = parseFloat(report.booking_revenue) + parseFloat(report.food_sales) - parseFloat(report.total_expenses) - parseFloat(report.contract_fees);
                        document.getElementById('netProfit').textContent = '৳' + netProfit.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        
                        const profitColor = netProfit >= 0 ? 'var(--accent-red)' : '#ff4444';
                        document.getElementById('netProfit').style.color = profitColor;
                        
                        document.getElementById('monthlyReportContainer').style.display = 'block';
                    } else {
                        alert('Error generating report: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        // Auto-fill the form when a movie is selected from the dropdown
        function populateEditMovieForm(movId) {
            const fieldsContainer = document.getElementById('editMovieFields');
            
            if (!movId) {
                fieldsContainer.style.display = 'none';
                return;
            }

            // Find the selected movie in our JSON data
            const movie = moviesData.find(m => m.mov_id == movId);
            
            if (movie) {
                document.getElementById('edit_mov_name').value = movie.mov_name || '';
                document.getElementById('edit_mov_poster').value = movie.mov_poster || '';
                document.getElementById('edit_mov_trailer').value = movie.mov_trailer || '';
                document.getElementById('edit_mov_genre').value = movie.mov_genre || '';
                document.getElementById('edit_mov_duration').value = movie.mov_duration || '';
                document.getElementById('edit_mov_release_date').value = movie.mov_release_date || '';
                document.getElementById('edit_mov_synopsis').value = movie.mov_synopsis || '';
                
                // Reveal the form fields
                fieldsContainer.style.display = 'block';
            }
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

        function submitEditMovie(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('manager_operations_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Movie updated successfully!');
                    closeEditMovieModal();
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

        function submitEditHall(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('manager_operations_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Hall updated successfully!');
                    closeEditHallModal();
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

        // Toggle past shows visibility
        function togglePastShows() {
            const content = document.getElementById('pastShowsContent');
            const toggle = document.getElementById('pastShowsToggleIcon');
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '▼';
            } else {
                content.style.display = 'none';
                toggle.textContent = '▶';
            }
        }

        // Edit Show Functions
        function openEditShowModal(s_id, movieName, h_id, showTime, price) {
            document.getElementById('editShowId').value = s_id;
            document.getElementById('editShowMovieName').value = movieName;
            document.getElementById('editShowPrice').value = price;
            document.getElementById('editShowTime').value = showTime;
            document.getElementById('editShowHall').value = h_id;
            document.getElementById('editShowModal').classList.add('active');
        }

        function closeEditShowModal() {
            document.getElementById('editShowModal').classList.remove('active');
            document.getElementById('editShowForm').reset();
            document.getElementById('editShowMessage').style.display = 'none';
        }

        function submitEditShow(event) {
            event.preventDefault();
            const s_id = document.getElementById('editShowId').value;
            const price = document.getElementById('editShowPrice').value;
            const time = document.getElementById('editShowTime').value;
            const h_id = document.getElementById('editShowHall').value;
            
            const formData = new FormData();
            formData.append('action', 'edit_show');
            formData.append('s_id', s_id);
            formData.append('ticket_price', price);
            formData.append('show_time', time);
            formData.append('h_id', h_id);

            fetch('removing_show_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const msg = document.getElementById('editShowMessage');
                if (data.status === 'success') {
                    msg.textContent = '✅ ' + data.message + ' (' + data.users_notified + ' users notified)';
                    msg.style.background = 'rgba(16,185,129,0.15)';
                    msg.style.border = '1px solid var(--success-color)';
                    msg.style.color = 'var(--success-color)';
                    msg.style.display = 'block';
                    setTimeout(() => {
                        closeEditShowModal();
                        location.reload();
                    }, 2000);
                } else {
                    msg.textContent = '❌ ' + data.message;
                    msg.style.background = 'rgba(239,68,68,0.15)';
                    msg.style.border = '1px solid var(--error-color)';
                    msg.style.color = 'var(--error-color)';
                    msg.style.display = 'block';
                }
            })
            .catch(error => {
                const msg = document.getElementById('editShowMessage');
                msg.textContent = '❌ Error: ' + error.message;
                msg.style.display = 'block';
            });
        }

        // Delete Show Function
        function deleteShow(s_id, movieName) {
            if (confirm('Are you sure you want to delete this show?\n\nAll users with bookings will be refunded automatically.\n\nMovie: ' + movieName)) {
                const formData = new FormData();
                formData.append('action', 'delete_show');
                formData.append('s_id', s_id);

                fetch('removing_show_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('✅ Show deleted!\n\n' + data.users_refunded + ' users refunded with ৳' + data.total_refunded);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Error: ' + error.message);
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