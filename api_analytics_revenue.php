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

// Get daily revenue
$stmt = $conn->prepare("
    SELECT DATE(booking_date) as date, SUM(total_price) as revenue
    FROM booking
    WHERE t_id = ? AND DATE(booking_date) BETWEEN ? AND ?
    GROUP BY DATE(booking_date)
    ORDER BY date ASC
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$daily_result = $stmt->get_result();
$daily_revenue = [];
$total_revenue = 0;

while ($row = $daily_result->fetch_assoc()) {
    $daily_revenue[] = $row;
    $total_revenue += $row['revenue'];
}
$stmt->close();

// Get total bookings
$stmt = $conn->prepare("
    SELECT COUNT(booking_id) as total
    FROM booking
    WHERE t_id = ? AND DATE(booking_date) BETWEEN ? AND ?
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get top shows
$stmt = $conn->prepare("
    SELECT 
        s.show_id,
        m.mov_name,
        DATE(s.show_date) as show_date,
        COUNT(b.booking_id) as bookings,
        SUM(b.total_price) as revenue,
        COUNT(DISTINCT sb.booking_id) as occupied_seats,
        ROUND((COUNT(DISTINCT sb.booking_id) / (h.hall_rows * h.hall_columns)) * 100, 2) as occupancy_pct
    FROM show_schedule s
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN hall h ON s.hall_id = h.hall_id
    LEFT JOIN booking b ON b.s_id = s.show_id AND DATE(b.booking_date) BETWEEN ? AND ?
    LEFT JOIN seat_booking sb ON sb.show_id = s.show_id
    WHERE s.t_id = ? AND DATE(s.show_date) BETWEEN ? AND ?
    GROUP BY s.show_id
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->bind_param("sssss", $start_date, $end_date, $theatre_id, $start_date, $end_date);
$stmt->execute();
$top_shows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get food revenue
$stmt = $conn->prepare("
    SELECT SUM(fo.total_price) as total_food_revenue, COUNT(fo.food_order_id) as food_orders
    FROM food_order fo
    WHERE fo.t_id = ? AND DATE(fo.order_date) BETWEEN ? AND ?
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$food_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_revenue' => (int)$total_revenue,
        'total_bookings' => (int)$total_bookings,
        'daily_revenue' => $daily_revenue,
        'top_shows' => $top_shows,
        'food_revenue' => (int)($food_data['total_food_revenue'] ?? 0),
        'food_orders' => (int)($food_data['food_orders'] ?? 0),
        'date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ]
    ]
]);
?>
