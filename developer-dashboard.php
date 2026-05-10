<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Check developer access
if (!hasRole(ROLE_DEVELOPER)) {
    header('Location: developers-login.php');
    exit();
}

// Fetch all theatres
$theatres_query = "SELECT * FROM theatre ORDER BY created_at DESC";
$theatres_result = $conn->query($theatres_query);
$theatres = [];
if ($theatres_result) {
    while($row = $theatres_result->fetch_assoc()) {
        $theatres[] = $row;
    }
}

// Fetch all managers with theatre names
$managers_query = "
    SELECT m.*, t.theatre_name 
    FROM manager m
    JOIN theatre t ON m.t_id = t.t_id
    ORDER BY m.created_at DESC
";
$managers_result = $conn->query($managers_query);
$managers = [];
if ($managers_result) {
    while($row = $managers_result->fetch_assoc()) {
        $managers[] = $row;
    }
}

// Fetch analytics data for all movies
$analytics_query = "
    SELECT 
        COUNT(DISTINCT m.mov_id) as total_movies,
        COALESCE(SUM(b.total_amount), 0) as total_revenue,
        COUNT(DISTINCT b.book_id) as total_bookings,
        COUNT(DISTINCT r.rev_id) as total_reviews
    FROM movie m
    LEFT JOIN show_schedule s ON m.mov_id = s.mov_id
    LEFT JOIN booking b ON s.s_id = b.s_id
    LEFT JOIN review r ON m.mov_id = r.mov_id
";
$analytics_result = $conn->query($analytics_query);
$analytics = $analytics_result ? $analytics_result->fetch_assoc() : [
    'total_movies' => 0,
    'total_revenue' => 0,
    'total_bookings' => 0,
    'total_reviews' => 0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Dashboard - ShowFlow</title>
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
        
        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .action-card {
            background: linear-gradient(135deg, var(--secondary-bg) 0%, var(--tertiary-bg) 100%);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 150px;
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
            font-size: 48px;
            margin-bottom: 1rem;
        }
        
        .action-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .action-description {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .data-section {
            margin-top: 3rem;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .table-container {
            overflow-x: auto;
            background: var(--secondary-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .btn-edit {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .btn-edit:hover {
            background-color: #2563eb;
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
                    <li><a href="#">System Control</a></li>
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
                <h2 class="modal-title">Developer Profile</h2>
                <button class="modal-close" onclick="toggleProfile()">✕</button>
            </div>
            <div style="padding: 1rem;">
                <div class="form-group">
                    <label>Role:</label>
                    <p style="color: var(--accent-red); font-weight: bold;">System Administrator (Developer)</p>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <p style="color: var(--success-color); font-weight: bold;">✓ Active</p>
                </div>
                <div class="form-group">
                    <label>Access Level:</label>
                    <p style="color: var(--text-primary);">Full System Control</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-title">🎬 Developer Control Panel</div>
            <div style="color: var(--text-secondary); font-size: 12px;">
                Logged in as: <strong>admin</strong>
            </div>
        </div>

        <!-- Analytics Dashboard -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: var(--secondary-bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--accent-red); text-align: center;">
                <div style="font-size: 28px; font-weight: bold; color: var(--accent-red); margin-bottom: 0.5rem;">
                    <?php echo $analytics['total_movies']; ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                    Total Movies
                </div>
            </div>
            <div style="background: var(--secondary-bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--accent-red); text-align: center;">
                <div style="font-size: 28px; font-weight: bold; color: var(--accent-red); margin-bottom: 0.5rem;">
                    ₹<?php echo number_format($analytics['total_revenue']); ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                    Total Revenue
                </div>
            </div>
            <div style="background: var(--secondary-bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--accent-red); text-align: center;">
                <div style="font-size: 28px; font-weight: bold; color: var(--accent-red); margin-bottom: 0.5rem;">
                    <?php echo $analytics['total_bookings']; ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                    Total Bookings
                </div>
            </div>
            <div style="background: var(--secondary-bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--accent-red); text-align: center;">
                <div style="font-size: 28px; font-weight: bold; color: var(--accent-red); margin-bottom: 0.5rem;">
                    <?php echo $analytics['total_reviews']; ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                    Total Reviews
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="actions-grid">
            <div class="action-card" onclick="openAddTheatreModal()">
                <div class="action-icon">🏢</div>
                <div class="action-title">Add Theatre</div>
                <div class="action-description">Create new cinema location</div>
            </div>
            
            <div class="action-card" onclick="openAddManagerModal()">
                <div class="action-icon">👨‍💼</div>
                <div class="action-title">Add Manager</div>
                <div class="action-description">Assign manager to theatre</div>
            </div>
            
            <div class="action-card" onclick="openReplaceManagerModal()">
                <div class="action-icon">🔄</div>
                <div class="action-title">Replace Manager</div>
                <div class="action-description">Update manager credentials</div>
            </div>
        </div>

        <!-- Theatres Section -->
        <div class="data-section">
            <div class="section-title">🏢 Theatres</div>
            <?php if (count($theatres) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Theatre ID</th>
                                <th>Theatre Name</th>
                                <th>Location</th>
                                <th>Created</th>
                                <th>Managers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($theatres as $theatre): ?>
                                <?php 
                                $manager_count = 0;
                                foreach ($managers as $mgr) {
                                    if ($mgr['t_id'] == $theatre['t_id']) {
                                        $manager_count++;
                                    }
                                }
                                ?>
                                <tr>
                                    <td>#<?php echo $theatre['t_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($theatre['theatre_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($theatre['location']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($theatre['created_at'])); ?></td>
                                    <td><?php echo $manager_count; ?> Manager(s)</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No theatres yet. <a href="#" onclick="openAddTheatreModal()" style="color: var(--accent-red); text-decoration: underline;">Create one</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Managers Section -->
        <div class="data-section">
            <div class="section-title">👨‍💼 Managers</div>
            <?php if (count($managers) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Manager ID</th>
                                <th>Name</th>
                                <th>Theatre</th>
                                <th>Contact</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($managers as $manager): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($manager['m_id']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($manager['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($manager['theatre_name']); ?></td>
                                    <td><?php echo htmlspecialchars($manager['contact']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($manager['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit btn-small" onclick="editManager('<?php echo htmlspecialchars($manager['m_id']); ?>', '<?php echo $manager['t_id']; ?>')">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    📭 No managers yet. <a href="#" onclick="openAddManagerModal()" style="color: var(--accent-red); text-decoration: underline;">Create one</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Theatre Modal -->
    <div id="addTheatreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Theatre</h2>
                <button class="modal-close" onclick="closeAddTheatreModal()">✕</button>
            </div>
            <form id="addTheatreForm" onsubmit="submitAddTheatre(event)">
                <div class="form-group">
                    <label>Theatre Name</label>
                    <input type="text" name="theatre_name" placeholder="e.g., PVR Cinemas" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g., Mumbai, MH" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Theatre</button>
            </form>
        </div>
    </div>

    <!-- Add Manager Modal -->
    <div id="addManagerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Manager</h2>
                <button class="modal-close" onclick="closeAddManagerModal()">✕</button>
            </div>
            <form id="addManagerForm" onsubmit="submitAddManager(event)">
                <div class="form-group">
                    <label>Manager User ID</label>
                    <input type="text" name="manager_id" placeholder="e.g., manager001" required>
                </div>
                <div class="form-group">
                    <label>Manager Name</label>
                    <input type="text" name="name" placeholder="Full name" required>
                </div>
                <div class="form-group">
                    <label>Theatre</label>
                    <select name="t_id" required>
                        <option value="">-- Select Theatre --</option>
                        <?php foreach ($theatres as $theatre): ?>
                            <option value="<?php echo $theatre['t_id']; ?>">
                                <?php echo htmlspecialchars($theatre['theatre_name']); ?> (<?php echo htmlspecialchars($theatre['location']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contact (Email/Phone)</label>
                    <input type="text" name="contact" placeholder="e.g., manager@email.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Secure password" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Manager</button>
            </form>
        </div>
    </div>

    <!-- Replace Manager Modal -->
    <div id="replaceManagerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Replace Manager</h2>
                <button class="modal-close" onclick="closeReplaceManagerModal()">✕</button>
            </div>
            <form id="replaceManagerForm" onsubmit="submitReplaceManager(event)">
                <div class="form-group">
                    <label>Theatre</label>
                    <select name="t_id" id="replaceTheatreSelect" onchange="loadCurrentManager()" required>
                        <option value="">-- Select Theatre --</option>
                        <?php foreach ($theatres as $theatre): ?>
                            <option value="<?php echo $theatre['t_id']; ?>">
                                <?php echo htmlspecialchars($theatre['theatre_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="currentManagerInfo" style="display:none; background: var(--tertiary-bg); padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <small style="color: var(--text-secondary);">Current Manager:</small>
                    <p style="color: var(--accent-red); margin: 0;"><strong id="currentManagerName"></strong></p>
                </div>
                
                <div class="form-group">
                    <label>New Manager ID</label>
                    <input type="text" name="new_manager_id" placeholder="e.g., newmanager001" required>
                </div>
                <div class="form-group">
                    <label>New Manager Name</label>
                    <input type="text" name="new_name" placeholder="Full name" required>
                </div>
                <div class="form-group">
                    <label>New Contact</label>
                    <input type="text" name="new_contact" placeholder="Email/Phone" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Secure password" required>
                </div>
                <button type="submit" class="btn btn-primary">Replace Manager</button>
            </form>
        </div>
    </div>

    <script>
        function openAddTheatreModal() {
            document.getElementById('addTheatreModal').classList.add('active');
        }
        
        function closeAddTheatreModal() {
            document.getElementById('addTheatreModal').classList.remove('active');
            document.getElementById('addTheatreForm').reset();
        }
        
        function openAddManagerModal() {
            if (<?php echo count($theatres) > 0 ? 'true' : 'false'; ?>) {
                document.getElementById('addManagerModal').classList.add('active');
            } else {
                alert('Please add a theatre first!');
            }
        }
        
        function closeAddManagerModal() {
            document.getElementById('addManagerModal').classList.remove('active');
            document.getElementById('addManagerForm').reset();
        }
        
        function openReplaceManagerModal() {
            if (<?php echo count($theatres) > 0 ? 'true' : 'false'; ?>) {
                document.getElementById('replaceManagerModal').classList.add('active');
            } else {
                alert('Please add a theatre and manager first!');
            }
        }
        
        function closeReplaceManagerModal() {
            document.getElementById('replaceManagerModal').classList.remove('active');
            document.getElementById('replaceManagerForm').reset();
            document.getElementById('currentManagerInfo').style.display = 'none';
        }
        
        function toggleProfile() {
            document.getElementById('profileModal').classList.toggle('active');
        }
        
        function loadCurrentManager() {
            const t_id = document.getElementById('replaceTheatreSelect').value;
            const managers = <?php echo json_encode($managers); ?>;
            
            const manager = managers.find(m => m.t_id == t_id);
            if (manager) {
                document.getElementById('currentManagerName').textContent = manager.name + ' (' + manager.m_id + ')';
                document.getElementById('currentManagerInfo').style.display = 'block';
            } else {
                document.getElementById('currentManagerInfo').style.display = 'none';
            }
        }
        
        function submitAddTheatre(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('add_theatre_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Theatre added successfully!');
                    closeAddTheatreModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function submitAddManager(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('add_manager_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Manager added successfully!');
                    closeAddManagerModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function submitReplaceManager(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('replace_manager_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Manager replaced successfully!');
                    closeReplaceManagerModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
