<?php
/**
 * Password Migration Script
 * This script hashes all plain text passwords in the user and manager tables
 * Run this once after updating to the secure password system
 *
 * IMPORTANT: Run this from CLI or delete after use for security
 */

require 'db.php';

echo "Starting password migration...\n\n";

// Migrate user passwords
echo "Migrating user passwords...\n";
$user_query = "SELECT u_id, password FROM user WHERE password IS NOT NULL AND password != ''";
$result = $conn->query($user_query);
$user_count = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $u_id = $row['u_id'];
        $password = $row['password'];

        // Check if password is already hashed (bcrypt hashes start with $2)
        if (strpos($password, '$2') === 0) {
            continue;
        }

        // Hash the plain text password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update_query = "UPDATE user SET password = ? WHERE u_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ss", $hashed, $u_id);

        if ($stmt->execute()) {
            $user_count++;
        } else {
            echo "Error migrating user $u_id: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

echo "Migrated $user_count user passwords\n\n";

// Migrate manager passwords
echo "Migrating manager passwords...\n";
$manager_query = "SELECT m_id, password FROM manager WHERE password IS NOT NULL AND password != ''";
$result = $conn->query($manager_query);
$manager_count = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $m_id = $row['m_id'];
        $password = $row['password'];

        // Check if password is already hashed (bcrypt hashes start with $2)
        if (strpos($password, '$2') === 0) {
            continue;
        }

        // Hash the plain text password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update_query = "UPDATE manager SET password = ? WHERE m_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed, $m_id);

        if ($stmt->execute()) {
            $manager_count++;
        } else {
            echo "Error migrating manager $m_id: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

echo "Migrated $manager_count manager passwords\n\n";
echo "✅ Password migration complete!\n";
echo "⚠️  IMPORTANT: Delete this file (migrate_passwords.php) after running it for security.\n";
?>
