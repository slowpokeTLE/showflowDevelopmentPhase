<?php
/**
 * File Replacement Utility
 * This script safely replaces the corrupted movie-details.php with the new version
 * 
 * Usage: Open in browser: http://localhost/showflow/fix-movie-details.php
 */

$old_file = 'movie-details.php';
$new_file = 'movie-details-new.php';
$backup_file = 'movie-details.php.backup';

// Check if both files exist
$old_exists = file_exists($old_file);
$new_exists = file_exists($new_file);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Movie Details Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            background: #0f0f0f;
            color: #fff;
            padding: 20px;
        }
        .container {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
        }
        h1 {
            color: #e50914;
            border-bottom: 2px solid #e50914;
            padding-bottom: 10px;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            background: #333;
        }
        .status.ok {
            background: #1a3a1a;
            color: #90ee90;
            border-left: 4px solid #00aa00;
        }
        .status.error {
            background: #3a1a1a;
            color: #ff6b6b;
            border-left: 4px solid #cc0000;
        }
        .status.info {
            background: #1a2a3a;
            color: #6b9eff;
            border-left: 4px solid #0066cc;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #e50914;
            color: white;
        }
        .btn-primary:hover {
            background: #c1040b;
        }
        .btn-primary:disabled {
            background: #666;
            cursor: not-allowed;
        }
        .output {
            background: #0f0f0f;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Fix Movie Details Page</h1>
        
        <h2>Current Status:</h2>
        
        <div class="status <?php echo $old_exists ? 'ok' : 'error'; ?>">
            📄 Old file (movie-details.php): <?php echo $old_exists ? '✓ Found' : '✗ Not found'; ?>
        </div>
        
        <div class="status <?php echo $new_exists ? 'ok' : 'error'; ?>">
            📄 New file (movie-details-new.php): <?php echo $new_exists ? '✓ Found' : '✗ Not found'; ?>
        </div>

        <?php if ($old_exists && $new_exists): ?>
            <div class="status info">
                💡 Both files found. Ready to perform replacement.
            </div>

            <h2>What will happen:</h2>
            <ol>
                <li>Backup old file to: <code>movie-details.php.backup</code></li>
                <li>Delete corrupted file: <code>movie-details.php</code></li>
                <li>Rename new file: <code>movie-details-new.php</code> → <code>movie-details.php</code></li>
            </ol>

            <div class="button-group">
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="action" value="fix" class="btn-primary">
                        🔧 Fix Now
                    </button>
                </form>
                <a href="user-dashboard.php" style="padding: 10px 20px; background: #333; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
                    ← Back to Dashboard
                </a>
            </div>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'fix') {
                echo '<div class="output">';
                
                // Backup old file
                if (copy($old_file, $backup_file)) {
                    echo "✓ Backup created: $backup_file\n";
                } else {
                    echo "✗ Failed to create backup\n";
                }

                // Delete old file
                if (unlink($old_file)) {
                    echo "✓ Old file deleted: $old_file\n";
                } else {
                    echo "✗ Failed to delete old file\n";
                }

                // Rename new file
                if (rename($new_file, $old_file)) {
                    echo "✓ New file renamed: $new_file → $old_file\n\n";
                    echo "✅ SUCCESS! Movie details page is now fixed.\n";
                    echo "📍 You can now access: movie-details.php\n";
                } else {
                    echo "✗ Failed to rename new file\n";
                }

                echo '</div>';
                echo '<div class="button-group">';
                echo '<a href="user-dashboard.php" class="btn-primary" style="text-decoration: none; display: inline-block;">Go to Dashboard</a>';
                echo '</div>';
            }
            ?>

        <?php elseif (!$old_exists && $new_exists): ?>
            <div class="status error">
                ⚠️ Old file not found but new file exists. Renaming directly...
            </div>

            <?php
            if (rename($new_file, $old_file)) {
                echo '<div class="output">';
                echo "✓ File renamed successfully\n";
                echo "✅ Movie details page is ready!\n";
                echo '</div>';
                echo '<div class="button-group">';
                echo '<a href="user-dashboard.php" class="btn-primary" style="text-decoration: none; display: inline-block;">Go to Dashboard</a>';
                echo '</div>';
            }
            ?>

        <?php else: ?>
            <div class="status error">
                ❌ Required files missing!
            </div>
            <p>Cannot proceed. Please ensure both files exist or contact support.</p>
        <?php endif; ?>
    </div>
</body>
</html>
