<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    die('Not logged in');
}

$u_id = $_SESSION['u_id'];

// Check if balance table exists
$check_table = "SHOW TABLES LIKE 'balance'";
$result = $conn->query($check_table);
if ($result->num_rows === 0) {
    die('Balance table does not exist. Please run wallet_migration.sql first.');
}

// Check balance structure
$check_structure = "DESCRIBE balance";
$result = $conn->query($check_structure);
echo "<h3>Balance Table Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check user's balance
$balance_query = "SELECT * FROM balance WHERE u_id = ?";
$stmt = $conn->prepare($balance_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>User's Balance Record:</h3>";
if ($result->num_rows > 0) {
    echo "<pre>";
    print_r($result->fetch_assoc());
    echo "</pre>";
} else {
    echo "No balance record found";
}
$stmt->close();

// Check all users' balances
$all_balances = "SELECT u_id, current_balance, typeof(current_balance) as type FROM balance LIMIT 5";
$result = $conn->query($all_balances);
echo "<h3>All Balance Records:</h3>";
echo "<table border='1'>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['u_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['current_balance']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
