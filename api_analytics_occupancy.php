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

// Get occupancy by show
$stmt = $conn->prepare("
    SELECT 
        s.show_id,
        m.mov_name,
        h.hall_rows,
        h.hall_columns,
        COUNT(DISTINCT sb.booking_id) as booked_seats,
        (h.hall_rows * h.hall_columns) as total_seats,
        ROUND((COUNT(DISTINCT sb.booking_id) / (h.hall_rows * h.hall_columns)) * 100, 2) as occupancy_pct
    FROM show_schedule s
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN hall h ON s.hall_id = h.hall_id
    LEFT JOIN seat_booking sb ON sb.show_id = s.show_id
    WHERE s.t_id = ? AND DATE(s.show_date) BETWEEN ? AND ?
    GROUP BY s.show_id
    ORDER BY occupancy_pct DESC
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$occupancy_shows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate average occupancy
$total_booked = 0;
$total_seats = 0;
foreach ($occupancy_shows as $show) {
    $total_booked += $show['booked_seats'];
    $total_seats += $show['total_seats'];
}
$avg_occupancy = $total_seats > 0 ? ($total_booked / $total_seats) * 100 : 0;

// Get occupancy trend by date
$stmt = $conn->prepare("
    SELECT 
        DATE(s.show_date) as date,
        COUNT(DISTINCT sb.booking_id) as booked_seats,
        SUM(h.hall_rows * h.hall_columns) as total_seats,
        ROUND((COUNT(DISTINCT sb.booking_id) / SUM(h.hall_rows * h.hall_columns)) * 100, 2) as occupancy_pct
    FROM show_schedule s
    JOIN hall h ON s.hall_id = h.hall_id
    LEFT JOIN seat_booking sb ON sb.show_id = s.show_id
    WHERE s.t_id = ? AND DATE(s.show_date) BETWEEN ? AND ?
    GROUP BY DATE(s.show_date)
    ORDER BY date ASC
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$occupancy_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get hall utilization
$stmt = $conn->prepare("
    SELECT 
        h.hall_id,
        h.hall_name,
        COUNT(DISTINCT s.show_id) as total_shows,
        SUM(CASE WHEN sb.booking_id IS NOT NULL THEN 1 ELSE 0 END) as booked_seats,
        SUM(h.hall_rows * h.hall_columns) as total_seats
    FROM hall h
    LEFT JOIN show_schedule s ON s.hall_id = h.hall_id AND s.t_id = ? AND DATE(s.show_date) BETWEEN ? AND ?
    LEFT JOIN seat_booking sb ON sb.show_id = s.show_id
    GROUP BY h.hall_id
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$hall_utilization = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'status' => 'success',
    'data' => [
        'avg_occupancy' => $avg_occupancy,
        'seats_booked' => $total_booked,
        'total_seats' => $total_seats,
        'occupancy_by_show' => $occupancy_shows,
        'occupancy_trend' => $occupancy_trend,
        'hall_utilization' => $hall_utilization,
        'date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ]
    ]
]);
?>
