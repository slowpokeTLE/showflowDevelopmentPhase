<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];

// Get food order history
$query = "
    SELECT f.food_order_id, f.t_id, t.theatre_name, f.order_date, f.total_price, f.order_status, COUNT(fi.food_order_item_id) as item_count
    FROM food_order f
    JOIN theatre t ON f.t_id = t.t_id
    LEFT JOIN food_order_item fi ON f.food_order_id = fi.food_order_id
    WHERE f.u_id = ?
    GROUP BY f.food_order_id
    ORDER BY f.order_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Food Orders - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .container {
            max-width: 1000px;
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

        .content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
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

        .status-pending {
            background: rgba(255, 165, 0, 0.2);
            border: 1px solid #FFA500;
            color: #FFA500;
        }

        .status-ready {
            background: rgba(45, 90, 45, 0.2);
            border: 1px solid #90EE90;
            color: #90EE90;
        }

        .status-completed {
            background: rgba(45, 90, 45, 0.2);
            border: 1px solid #90EE90;
            color: #90EE90;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 12px;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div class="container">
        <div class="header">
            <span>🍿 My Food Orders</span>
            <div class="nav-links">
                <a href="facilities.php" class="nav-link">🎭 Facilities</a>
                <a href="user-dashboard.php" class="nav-link">🎬 Movies</a>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>

        <div class="content">
            <?php if (count($orders) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Theatre</th>
                                <th>Date & Time</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['food_order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['theatre_name']); ?></td>
                                    <td>
                                        <div><?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                                        <div style="color: var(--text-secondary); font-size: 12px;"><?php echo date('h:i A', strtotime($order['order_date'])); ?></div>
                                    </td>
                                    <td><?php echo $order['item_count']; ?> item(s)</td>
                                    <td>₹<?php echo number_format($order['total_price'], 0); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🍿</div>
                    <h2 style="color: var(--text-primary);">No Orders Yet</h2>
                    <p>You haven't placed any food orders yet.</p>
                    <a href="facilities.php" style="color: var(--accent-red); text-decoration: none; margin-top: 1rem; display: inline-block;">→ Order Food Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
