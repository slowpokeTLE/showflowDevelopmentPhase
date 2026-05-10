<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

header('Content-Type: application/json');

if (!hasRole(ROLE_MANAGER)) {
    jsonResponse('error', 'Unauthorized access');
}

$t_id = $_SESSION['t_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ADD MOVIE
    if ($action === 'add_movie') {
        $mov_name = trim($_POST['mov_name'] ?? '');
        $mov_poster = trim($_POST['mov_poster'] ?? '');
        $mov_trailer = trim($_POST['mov_trailer'] ?? '');
        $mov_synopsis = trim($_POST['mov_synopsis'] ?? '');
        $mov_genre = trim($_POST['mov_genre'] ?? '');
        $mov_duration = intval($_POST['mov_duration'] ?? 0);
        $mov_release_date = trim($_POST['mov_release_date'] ?? '');
        
        if (empty($mov_name)) {
            jsonResponse('error', 'Movie name is required');
        }
        
        $insert_query = "INSERT INTO movie (mov_name, mov_poster, mov_trailer, mov_synopsis, mov_genre, mov_duration, mov_release_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssssss", $mov_name, $mov_poster, $mov_trailer, $mov_synopsis, $mov_genre, $mov_duration, $mov_release_date);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            jsonResponse('success', 'Movie added successfully', ['mov_id' => $new_id]);
        } else {
            jsonResponse('error', 'Failed to add movie: ' . $conn->error);
        }
        $stmt->close();
    }
    
    // EDIT MOVIE
    else if ($action === 'edit_movie') {
        $mov_id = intval($_POST['mov_id'] ?? 0);
        $mov_name = trim($_POST['mov_name'] ?? '');
        $mov_poster = trim($_POST['mov_poster'] ?? '');
        $mov_trailer = trim($_POST['mov_trailer'] ?? '');
        $mov_synopsis = trim($_POST['mov_synopsis'] ?? '');
        $mov_genre = trim($_POST['mov_genre'] ?? '');
        $mov_duration = intval($_POST['mov_duration'] ?? 0);
        $mov_release_date = trim($_POST['mov_release_date'] ?? '');
        
        if ($mov_id <= 0 || empty($mov_name)) {
            jsonResponse('error', 'Valid Movie ID and name are required');
        }
        
        $update_query = "UPDATE movie SET mov_name = ?, mov_poster = ?, mov_trailer = ?, mov_synopsis = ?, mov_genre = ?, mov_duration = ?, mov_release_date = ? WHERE mov_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssssi", $mov_name, $mov_poster, $mov_trailer, $mov_synopsis, $mov_genre, $mov_duration, $mov_release_date, $mov_id);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Movie updated successfully');
        } else {
            jsonResponse('error', 'Failed to update movie: ' . $conn->error);
        }
        $stmt->close();
    }

    // ADD HALL
    else if ($action === 'add_hall') {
        $hall_name = trim($_POST['hall_name'] ?? '');
        $total_rows = intval($_POST['total_rows'] ?? 0);
        $total_columns = intval($_POST['total_columns'] ?? 0);
        
        if (empty($hall_name) || $total_rows <= 0 || $total_columns <= 0) {
            jsonResponse('error', 'All fields required with valid numbers');
        }
        
        $insert_query = "INSERT INTO hall (t_id, hall_name, total_rows, total_columns) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isii", $t_id, $hall_name, $total_rows, $total_columns);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            jsonResponse('success', 'Hall added successfully', ['h_id' => $new_id]);
        } else {
            jsonResponse('error', 'Failed to add hall: ' . $conn->error);
        }
        $stmt->close();
    }
    
    // EDIT HALL
    else if ($action === 'edit_hall') {
        $h_id = intval($_POST['hall_id'] ?? 0);
        $hall_name = trim($_POST['hall_name'] ?? '');
        $total_rows = intval($_POST['total_rows'] ?? 0);
        $total_columns = intval($_POST['total_columns'] ?? 0);
        
        if ($h_id <= 0 || empty($hall_name) || $total_rows <= 0 || $total_columns <= 0) {
            jsonResponse('error', 'All fields required');
        }
        
        $verify_query = "SELECT h_id FROM hall WHERE h_id = ? AND t_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $h_id, $t_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            jsonResponse('error', 'Hall not found or unauthorized');
        }
        $stmt->close();
        
        $update_query = "UPDATE hall SET hall_name = ?, total_rows = ?, total_columns = ? WHERE h_id = ? AND t_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("siiii", $hall_name, $total_rows, $total_columns, $h_id, $t_id);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Hall updated successfully');
        } else {
            jsonResponse('error', 'Failed to update hall: ' . $conn->error);
        }
        $stmt->close();
    }
    
    // DELETE HALL
    else if ($action === 'delete_hall') {
        $h_id = intval($_POST['hall_id'] ?? 0);
        
        if ($h_id <= 0) {
            jsonResponse('error', 'Invalid hall ID');
        }
        
        $verify_query = "SELECT h_id FROM hall WHERE h_id = ? AND t_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $h_id, $t_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            jsonResponse('error', 'Hall not found or unauthorized');
        }
        $stmt->close();
        
        $check_shows = "SELECT COUNT(*) as count FROM show_schedule WHERE h_id = ?";
        $stmt = $conn->prepare($check_shows);
        $stmt->bind_param("i", $h_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] > 0) {
            jsonResponse('error', 'Cannot delete hall with existing shows. Delete shows first.');
        }
        
        $delete_query = "DELETE FROM hall WHERE h_id = ? AND t_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $h_id, $t_id);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Hall deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete hall: ' . $conn->error);
        }
        $stmt->close();
    }
    
    // CREATE SHOW
    else if ($action === 'create_show') {
        $mov_id = intval($_POST['mov_id'] ?? 0);
        $h_id = intval($_POST['h_id'] ?? 0);
        $show_date = trim($_POST['show_date'] ?? '');
        $show_time = trim($_POST['show_time'] ?? '');
        $ticket_price = floatval($_POST['ticket_price'] ?? 0);
        
        if ($mov_id <= 0 || $h_id <= 0 || empty($show_date) || empty($show_time) || $ticket_price <= 0) {
            jsonResponse('error', 'All fields required');
        }
        
        // Verify hall belongs to this theatre
        $verify_query = "SELECT h_id FROM hall WHERE h_id = ? AND t_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $h_id, $t_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            jsonResponse('error', 'Invalid hall for this theatre');
        }
        $stmt->close();
        
        $insert_query = "INSERT INTO show_schedule (mov_id, t_id, h_id, show_date, show_time, ticket_price) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiissd", $mov_id, $t_id, $h_id, $show_date, $show_time, $ticket_price);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            jsonResponse('success', 'Show created successfully', ['s_id' => $new_id]);
        } else {
            jsonResponse('error', 'Failed to create show: ' . $conn->error);
        }
        $stmt->close();
    }
    
    // ADD EXPENSE
    else if ($action === 'add_expense') {
        $ex_date = trim($_POST['ex_date'] ?? '');
        $ex_reason = trim($_POST['ex_reason'] ?? '');
        $cost = floatval($_POST['cost'] ?? 0);
        
        if (empty($ex_date) || empty($ex_reason) || $cost <= 0) {
            jsonResponse('error', 'All fields required');
        }
        
        $insert_query = "INSERT INTO expense (t_id, ex_date, ex_reason, cost) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issd", $t_id, $ex_date, $ex_reason, $cost);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            jsonResponse('success', 'Expense added successfully', ['ex_id' => $new_id]);
        } else {
            jsonResponse('error', 'Failed to add expense: ' . $conn->error);
        }
        $stmt->close();
    }
}
?>
