<?php
// Session Management Handler

session_start();

// Check if session exists and is valid
function isLoggedIn() {
    return isset($_SESSION['role']) && isset($_SESSION['user_id']);
}

// Check specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Set session for user
function setUserSession($role, $user_id, $extra_data = []) {
    $_SESSION['role'] = $role;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['login_time'] = time();
    
    foreach ($extra_data as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    session_regenerate_id(true);
}

// Clear session
function logout() {
    session_destroy();
    header('Location: ' . BASE_URL);
    exit();
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'pages/user/login.php');
        exit();
    }
}

// Redirect if not specific role
function requireRole($role) {
    if (!hasRole($role)) {
        header('HTTP/1.0 403 Forbidden');
        die(MSG_UNAUTHORIZED);
    }
}

// Return JSON response
function jsonResponse($status, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}
?>
