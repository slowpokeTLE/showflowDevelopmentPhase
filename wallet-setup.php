<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

echo "=== Wallet System Debug & Setup ===\n\n";

// Check if tables exist
$tables_to_check = ['balance', 'recharge_history', 'wallet_transaction_log'];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        $missing_tables[] = $table;
        echo "❌ Table '$table' does NOT exist\n";
    } else {
        echo "✅ Table '$table' exists\n";
    }
}

if (!empty($missing_tables)) {
    echo "\n⚠️  Missing tables detected. Running migration...\n";
    
    // Read and execute the migration file
    $migration_sql = file_get_contents('wallet_migration.sql');
    
    if ($conn->multi_query($migration_sql)) {
        while ($conn->next_result()) {
            if (!$conn->more_results()) break;
        }
        echo "✅ Migration completed successfully!\n\n";
    } else {
        echo "❌ Migration failed: " . $conn->error . "\n\n";
        exit(1);
    }
}

// Now check user balance if logged in
if (hasRole(ROLE_USER)) {
    $u_id = $_SESSION['u_id'];
    
    $balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
    $stmt = $conn->prepare($balance_query);
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "\n=== User Balance Information ===\n";
    echo "User ID: " . htmlspecialchars($u_id) . "\n";
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "Current Balance: ৳" . number_format($row['current_balance'], 2) . "\n";
        echo "Type: " . gettype($row['current_balance']) . "\n";
    } else {
        echo "No balance record found.\n";
        
        // Create one
        $insert_query = "INSERT INTO balance (u_id, current_balance) VALUES (?, 0)";
        $stmt2 = $conn->prepare($insert_query);
        $stmt2->bind_param("s", $u_id);
        if ($stmt2->execute()) {
            echo "✅ Balance record created (initialized to 0)\n";
        } else {
            echo "❌ Failed to create balance record: " . $stmt2->error . "\n";
        }
        $stmt2->close();
    }
    $stmt->close();
    
    // Show recent transactions
    $trans_query = "SELECT * FROM wallet_transaction_log WHERE u_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($trans_query);
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "\n=== Recent Transactions ===\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . ucfirst(str_replace('_', ' ', $row['transaction_type'])) . ": ৳" . number_format($row['amount'], 2) . " (" . $row['operation'] . ") - " . $row['created_at'] . "\n";
        }
    } else {
        echo "No transactions yet\n";
    }
    $stmt->close();
}

echo "\n✅ Debug complete!\n";
?>
