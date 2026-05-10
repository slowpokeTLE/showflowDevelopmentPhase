<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

// Check manager access
if (!hasRole(ROLE_MANAGER)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$t_id = $_GET['t_id'] ?? $_SESSION['t_id'];
$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;

if (!$month || !$year) {
    echo json_encode(['status' => 'error', 'message' => 'Month and year required']);
    exit();
}

// Format date for the month
$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

// Get booking revenue for the month
$booking_query = "
    SELECT COALESCE(SUM(b.total_amount), 0) as booking_revenue
    FROM booking b
    JOIN show_schedule ss ON b.s_id = ss.s_id
    WHERE ss.t_id = ? AND DATE(b.booking_date) BETWEEN ? AND ?
";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("iss", $t_id, $start_date, $end_date);
$stmt->execute();
$booking_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get food sales for the month
$food_query = "
    SELECT COALESCE(SUM(fo.total_price), 0) as food_sales
    FROM food_order fo
    WHERE fo.t_id = ? AND DATE(fo.order_date) BETWEEN ? AND ?
";
$stmt = $conn->prepare($food_query);
$stmt->bind_param("iss", $t_id, $start_date, $end_date);
$stmt->execute();
$food_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get total expenses for the month
$expense_query = "
    SELECT COALESCE(SUM(cost), 0) as total_expenses
    FROM expense
    WHERE t_id = ? AND ex_date BETWEEN ? AND ?
";
$stmt = $conn->prepare($expense_query);
$stmt->bind_param("iss", $t_id, $start_date, $end_date);
$stmt->execute();
$expense_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get contract fees for the month
// This requires calculating fees from all bookings in the month for movies with contracts
$contract_fees_query = "
    SELECT COALESCE(SUM(
        (b.total_amount * c.percentage_per_ticket / 100) + 
        (CASE WHEN b.booking_date >= DATE_FORMAT(b.booking_date, '%Y-%m-01') 
              AND DATE_FORMAT(b.booking_date, '%Y-%m') = ?
              THEN c.one_time_cost / COUNT(DISTINCT DATE(b.booking_date)) 
              ELSE 0 END)
    ), 0) as contract_fees
    FROM booking b
    JOIN show_schedule ss ON b.s_id = ss.s_id
    LEFT JOIN contract c ON ss.mov_id = c.mov_id AND ss.t_id = c.t_id
    WHERE ss.t_id = ? AND DATE(b.booking_date) BETWEEN ? AND ? AND c.contract_id IS NOT NULL
";

// Simpler approach: calculate contract fees based on bookings
$contract_query = "
    SELECT 
        COALESCE(SUM(c.one_time_cost), 0) as one_time_fees,
        COALESCE(SUM(b.total_amount * c.percentage_per_ticket / 100), 0) as ticket_percentage_fees
    FROM booking b
    JOIN show_schedule ss ON b.s_id = ss.s_id
    LEFT JOIN contract c ON ss.mov_id = c.mov_id AND ss.t_id = c.t_id
    WHERE ss.t_id = ? AND DATE(b.booking_date) BETWEEN ? AND ? AND c.contract_id IS NOT NULL
";
$stmt = $conn->prepare($contract_query);
$stmt->bind_param("iss", $t_id, $start_date, $end_date);
$stmt->execute();
$contract_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$booking_revenue = $booking_result['booking_revenue'] ?? 0;
$food_sales = $food_result['food_sales'] ?? 0;
$total_expenses = $expense_result['total_expenses'] ?? 0;
$contract_fees = ($contract_result['one_time_fees'] ?? 0) + ($contract_result['ticket_percentage_fees'] ?? 0);

$response = [
    'status' => 'success',
    'report' => [
        'booking_revenue' => $booking_revenue,
        'food_sales' => $food_sales,
        'total_expenses' => $total_expenses,
        'contract_fees' => $contract_fees,
        'net_profit' => $booking_revenue + $food_sales - $total_expenses - $contract_fees
    ]
];

echo json_encode($response);
?>
