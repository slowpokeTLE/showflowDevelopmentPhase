<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_MANAGER)) {
    header('Location: manager-login.php');
    exit();
}

$t_id = $_SESSION['t_id'];

// Get today's revenue
$today_query = "
    SELECT SUM(b.total_price) as today_revenue, COUNT(b.book_id) as today_bookings
    FROM booking b
    JOIN show_schedule s ON b.s_id = s.s_id
    WHERE s.t_id = ? AND DATE(b.booking_date) = CURDATE()
";
$stmt = $conn->prepare($today_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$today_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get monthly revenue
$month_query = "
    SELECT SUM(b.total_price) as month_revenue, COUNT(b.book_id) as month_bookings
    FROM booking b
    JOIN show_schedule s ON b.s_id = s.s_id
    WHERE s.t_id = ? AND MONTH(b.booking_date) = MONTH(CURDATE()) AND YEAR(b.booking_date) = YEAR(CURDATE())
";
$stmt = $conn->prepare($month_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$month_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get food revenue today
$food_today_query = "
    SELECT SUM(f.total_price) as food_revenue, COUNT(f.food_order_id) as food_orders
    FROM food_order f
    WHERE f.t_id = ? AND DATE(f.order_date) = CURDATE()
";
$stmt = $conn->prepare($food_today_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$food_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get show performance (top 5 shows)
$shows_query = "
    SELECT s.s_id, m.mov_name, h.hall_name, s.show_date, s.show_time, s.ticket_price,
           COUNT(b.book_id) as booked_seats, (h.total_rows * h.total_columns) as total_seats,
           COUNT(b.book_id) * s.ticket_price as show_revenue
    FROM show_schedule s
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN hall h ON s.h_id = h.h_id
    LEFT JOIN booking b ON s.s_id = b.s_id
    WHERE s.t_id = ? AND s.show_date >= CURDATE()
    GROUP BY s.s_id
    ORDER BY show_revenue DESC
    LIMIT 5
";
$stmt = $conn->prepare($shows_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$shows_result = $stmt->get_result();
$shows = [];
while ($row = $shows_result->fetch_assoc()) {
    $shows[] = $row;
}
$stmt->close();

// Get complaints
$complaints_query = "
    SELECT complaint_id, u_id, complaint_type, subject, status, created_at
    FROM complaint
    WHERE t_id = ?
    ORDER BY created_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($complaints_query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$complaints_result = $stmt->get_result();
$complaints = [];
while ($row = $complaints_result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
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
            font-size: 14px;
        }

        .nav-link:hover {
            background: var(--accent-red);
            border-color: var(--accent-red);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--accent-red);
            margin-bottom: 0.5rem;
        }

        .stat-subtext {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-primary);
            border-bottom: 2px solid var(--border-color);
        }

        th {
            padding: 1rem;
            text-align: left;
            color: var(--text-primary);
            font-weight: bold;
            font-size: 14px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 14px;
        }

        tbody tr:hover {
            background: rgba(229, 9, 20, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-new {
            background: rgba(255, 165, 0, 0.2);
            border: 1px solid #FFA500;
            color: #FFA500;
        }

        .status-in-progress {
            background: rgba(100, 150, 255, 0.2);
            border: 1px solid #6496FF;
            color: #6496FF;
        }

        .status-resolved {
            background: rgba(45, 90, 45, 0.2);
            border: 1px solid #90EE90;
            color: #90EE90;
        }

        .occupancy-bar {
            display: flex;
            background: var(--bg-primary);
            border-radius: 4px;
            overflow: hidden;
            height: 20px;
            margin: 0.5rem 0;
        }

        .occupancy-filled {
            background: var(--accent-red);
            transition: width 0.3s ease;
        }

        .occupancy-text {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .analytics-container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 12px;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div class="analytics-container">
        <div class="header">
            <span>📊 Analytics Dashboard</span>
            <div class="nav-links">
                <a href="manager-dashboard.php" class="nav-link">← Back</a>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value">₹<?php echo number_format($today_stats['today_revenue'] ?? 0, 0); ?></div>
                <div class="stat-subtext"><?php echo ($today_stats['today_bookings'] ?? 0); ?> bookings</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">This Month</div>
                <div class="stat-value">₹<?php echo number_format($month_stats['month_revenue'] ?? 0, 0); ?></div>
                <div class="stat-subtext"><?php echo ($month_stats['month_bookings'] ?? 0); ?> bookings</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Food Orders Today</div>
                <div class="stat-value">₹<?php echo number_format($food_stats['food_revenue'] ?? 0, 0); ?></div>
                <div class="stat-subtext"><?php echo ($food_stats['food_orders'] ?? 0); ?> orders</div>
            </div>
        </div>

        <!-- Show Performance -->
        <div class="section">
            <div class="section-title">📽️ Top Performing Shows</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Show</th>
                            <th>Date & Time</th>
                            <th>Hall</th>
                            <th>Occupancy</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shows as $show): ?>
                            <?php 
                            $occupancy = ($show['booked_seats'] / $show['total_seats']) * 100;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($show['mov_name']); ?></td>
                                <td>
                                    <div><?php echo date('d M Y', strtotime($show['show_date'])); ?></div>
                                    <div style="color: var(--text-secondary); font-size: 12px;"><?php echo date('h:i A', strtotime($show['show_time'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($show['hall_name']); ?></td>
                                <td>
                                    <div class="occupancy-bar">
                                        <div class="occupancy-filled" style="width: <?php echo $occupancy; ?>%"></div>
                                    </div>
                                    <div class="occupancy-text"><?php echo round($occupancy, 1); ?>% (<?php echo $show['booked_seats']; ?>/<?php echo $show['total_seats']; ?>)</div>
                                </td>
                                <td><strong>₹<?php echo number_format($show['show_revenue'] ?? 0, 0); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Complaints -->
        <div class="section">
            <div class="section-title">💬 Recent Complaints</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($complaints) > 0): ?>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td>#<?php echo str_pad($complaint['complaint_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo ucfirst($complaint['complaint_type']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y h:i A', strtotime($complaint['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-secondary);">No complaints</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
