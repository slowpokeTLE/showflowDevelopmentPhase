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

// Get total complaints
$stmt = $conn->prepare("
    SELECT COUNT(complaint_id) as total FROM complaint
    WHERE t_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$total_complaints = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get resolved complaints
$stmt = $conn->prepare("
    SELECT COUNT(complaint_id) as total FROM complaint
    WHERE t_id = ? AND status = 'resolved' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$resolved_complaints = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get average resolution time
$stmt = $conn->prepare("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_date)) as avg_hours
    FROM complaint
    WHERE t_id = ? AND status = 'resolved' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$avg_resolution = $stmt->get_result()->fetch_assoc()['avg_hours'] ?? 0;
$stmt->close();

// Get complaints by type
$stmt = $conn->prepare("
    SELECT 
        complaint_type,
        COUNT(complaint_id) as count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_complaints
    FROM complaint
    WHERE t_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY complaint_type
    ORDER BY count DESC
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$by_type_result = $stmt->get_result();
$by_type = [];
while ($row = $by_type_result->fetch_assoc()) {
    $by_type[] = $row;
}
$stmt->close();

// Get complaints by status
$stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(complaint_id) as count
    FROM complaint
    WHERE t_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$by_status = [];
while ($row = $stmt->get_result()->fetch_assoc()) {
    $by_status[$row['status']] = $row['count'];
}
$stmt->close();

// Get complaint trend by date
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(complaint_id) as count
    FROM complaint
    WHERE t_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent complaints
$stmt = $conn->prepare("
    SELECT 
        complaint_id,
        complaint_type,
        subject,
        status,
        created_at
    FROM complaint
    WHERE t_id = ? AND DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->bind_param("iss", $theatre_id, $start_date, $end_date);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_complaints' => (int)$total_complaints,
        'resolved_complaints' => (int)$resolved_complaints,
        'avg_resolution_time' => (float)$avg_resolution,
        'by_type' => $by_type,
        'by_status' => $by_status,
        'trend' => $trend,
        'recent_complaints' => $recent,
        'date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ]
    ]
]);
?>
