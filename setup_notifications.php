<?php
// Database initialization script for notification features
require 'db.php';

// Create user_notification table if it doesn't exist
$create_notification_table = "
    CREATE TABLE IF NOT EXISTS user_notification (
        notif_id INT PRIMARY KEY AUTO_INCREMENT,
        u_id VARCHAR(50) NOT NULL,
        m_id VARCHAR(50),
        message TEXT NOT NULL,
        notif_type VARCHAR(50),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE,
        FOREIGN KEY (m_id) REFERENCES manager(m_id) ON DELETE SET NULL,
        INDEX idx_u_id (u_id),
        INDEX idx_created_at (created_at)
    )
";

if ($conn->query($create_notification_table)) {
    echo "✅ user_notification table created/verified successfully\n";
} else {
    echo "❌ Error creating user_notification table: " . $conn->error . "\n";
}

echo "✅ Database setup completed successfully!\n";
?>
