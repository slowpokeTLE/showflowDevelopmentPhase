<?php
session_start();
require 'db.php';
require 'session_handler.php';

requireRole(['ROLE_MANAGER']);

$theatre_id = $_SESSION['theatre_id'] ?? null;
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

if (!$theatre_id) {
    die(json_encode(['status' => 'error', 'message' => 'Theatre not found']));
}

// Get total food revenue and orders
$stmt = $conn->prepare("
    SELECT 
        COUNT(fo.food_order_id) as total_orders,
        SUM(fo.total_price) as total_revenue,
        AVG(fo.total_price) as avg_order_value
    FROM food_order fo
    WHERE fo.t_id = ? AND DATE(fo.order_date) BETWEEN ? AND ?
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$revenue_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get top food items
$stmt = $conn->prepare("
    SELECT 
        f.food_id,
        f.food_name,
        COUNT(foi.id) as orders,
        SUM(foi.qty) as units_sold,
        SUM(foi.qty * foi.price) as revenue
    FROM food_item f
    LEFT JOIN food_order_item foi ON f.food_id = foi.food_id
    LEFT JOIN food_order fo ON fo.food_order_id = foi.food_order_id
    WHERE f.t_id = ? AND DATE(fo.order_date) BETWEEN ? AND ?
    GROUP BY f.food_id
    ORDER BY revenue DESC
    LIMIT 20
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$top_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get food sales trend by date
$stmt = $conn->prepare("
    SELECT 
        DATE(fo.order_date) as date,
        COUNT(fo.food_order_id) as orders,
        SUM(fo.total_price) as revenue
    FROM food_order fo
    WHERE fo.t_id = ? AND DATE(fo.order_date) BETWEEN ? AND ?
    GROUP BY DATE(fo.order_date)
    ORDER BY date ASC
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$sales_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get food orders by status
$stmt = $conn->prepare("
    SELECT 
        fo.order_status,
        COUNT(fo.food_order_id) as count
    FROM food_order fo
    WHERE fo.t_id = ? AND DATE(fo.order_date) BETWEEN ? AND ?
    GROUP BY fo.order_status
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$orders_by_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_orders' => (int)($revenue_data['total_orders'] ?? 0),
        'total_revenue' => (int)($revenue_data['total_revenue'] ?? 0),
        'avg_order_value' => (int)($revenue_data['avg_order_value'] ?? 0),
        'top_items' => $top_items,
        'sales_trend' => $sales_trend,
        'orders_by_status' => $orders_by_status,
        'date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ]
    ]
]);
?>
