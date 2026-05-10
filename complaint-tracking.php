<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];

// Get complaint history
$query = "
    SELECT c.complaint_id, c.t_id, t.theatre_name, c.complaint_type, c.subject, c.message, c.status, 
           c.created_at, c.resolved_at, c.manager_notes
    FROM complaint c
    JOIN theatre t ON c.t_id = t.t_id
    WHERE c.u_id = ?
    ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$result = $stmt->get_result();
$complaints = [];
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - ShowFlow</title>
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

        .complaints-grid {
            display: grid;
            gap: 1.5rem;
        }

        .complaint-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .complaint-card:hover {
            border-color: var(--accent-red);
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .complaint-id {
            font-weight: bold;
            color: var(--accent-red);
            font-family: monospace;
        }

        .complaint-status-badge {
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

        .complaint-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .complaint-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            font-size: 14px;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .complaint-type {
            display: inline-block;
            background: var(--bg-primary);
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 12px;
        }

        .complaint-message {
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            color: var(--text-primary);
            line-height: 1.5;
            border-left: 3px solid var(--accent-red);
        }

        .complaint-notes {
            background: rgba(45, 90, 45, 0.1);
            padding: 1rem;
            border-radius: 6px;
            color: var(--text-primary);
            border-left: 3px solid #90EE90;
            margin-top: 1rem;
        }

        .complaint-dates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .date-item {
            background: var(--bg-primary);
            padding: 0.75rem;
            border-radius: 4px;
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

            .complaint-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .complaint-dates {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div class="container">
        <div class="header">
            <span>💬 My Complaints</span>
            <div class="nav-links">
                <a href="facilities.php" class="nav-link">🎭 Facilities</a>
                <a href="user-dashboard.php" class="nav-link">🎬 Movies</a>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>

        <?php if (count($complaints) > 0): ?>
            <div class="complaints-grid">
                <?php foreach ($complaints as $complaint): ?>
                    <div class="complaint-card">
                        <div class="complaint-header">
                            <div>
                                <div class="complaint-id">#<?php echo str_pad($complaint['complaint_id'], 5, '0', STR_PAD_LEFT); ?></div>
                                <div class="complaint-title"><?php echo htmlspecialchars($complaint['subject']); ?></div>
                            </div>
                            <span class="complaint-status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                            </span>
                        </div>

                        <div class="complaint-meta">
                            <span>🏢 <?php echo htmlspecialchars($complaint['theatre_name']); ?></span>
                            <span class="complaint-type"><?php echo ucfirst($complaint['complaint_type']); ?></span>
                        </div>

                        <div class="complaint-message">
                            <?php echo htmlspecialchars($complaint['message']); ?>
                        </div>

                        <?php if (!empty($complaint['manager_notes'])): ?>
                            <div class="complaint-notes">
                                <strong>Manager Response:</strong><br>
                                <?php echo htmlspecialchars($complaint['manager_notes']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="complaint-dates">
                            <div class="date-item">
                                <strong>Submitted:</strong><br>
                                <?php echo date('d M Y, h:i A', strtotime($complaint['created_at'])); ?>
                            </div>
                            <?php if (!empty($complaint['resolved_at'])): ?>
                                <div class="date-item">
                                    <strong>Resolved:</strong><br>
                                    <?php echo date('d M Y, h:i A', strtotime($complaint['resolved_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">💬</div>
                <h2 style="color: var(--text-primary);">No Complaints</h2>
                <p>You haven't submitted any complaints yet.</p>
                <a href="facilities.php" style="color: var(--accent-red); text-decoration: none; margin-top: 1rem; display: inline-block;">→ Submit a Complaint</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
