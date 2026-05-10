<?php
// Utility function to ensure wallet tables exist
function ensureWalletTablesExist() {
    global $conn;
    
    // Check if balance table exists
    $result = $conn->query("SHOW TABLES LIKE 'balance'");
    if ($result && $result->num_rows === 0) {
        // Tables don't exist, run migration
        $migration_sql = file_get_contents(__DIR__ . '/wallet_migration.sql');
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $migration_sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!$conn->query($statement)) {
                    error_log('Wallet migration error: ' . $conn->error);
                    return false;
                }
            }
        }
    }
    return true;
}

// Wrapper function to get user's wallet balance
function getUserWalletBalance($u_id) {
    global $conn;
    
    // Ensure tables exist
    if (!ensureWalletTablesExist()) {
        return 0;
    }
    
    $balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
    $stmt = $conn->prepare($balance_query);
    
    if (!$stmt) {
        error_log('Balance query prepare error: ' . $conn->error);
        return 0;
    }
    
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $balance = (float)$row['current_balance'];
    } else {
        // Create balance record if it doesn't exist
        $balance = 0;
        $insert_query = "INSERT INTO balance (u_id, current_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE current_balance = current_balance";
        $stmt2 = $conn->prepare($insert_query);
        if ($stmt2) {
            $stmt2->bind_param("sd", $u_id, $balance);
            @$stmt2->execute();
            $stmt2->close();
        }
    }
    
    $stmt->close();
    return $balance;
}
?>
