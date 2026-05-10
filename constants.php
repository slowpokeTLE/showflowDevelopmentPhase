<?php
// Application Constants

// Authentication
define('DEVELOPER_USERNAME', 'admin');
define('DEVELOPER_PASSWORD', '1234');

// Role Constants
define('ROLE_DEVELOPER', 'developer');
define('ROLE_MANAGER', 'manager');
define('ROLE_USER', 'user');

// Session Timeout (in seconds)
define('SESSION_TIMEOUT', 3600);

// Paths - Dynamic Base URL for different servers
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . $host . $script_path . '/');
define('ROOT_PATH', __DIR__ . '/../');

// Color Theme
define('PRIMARY_BG', '#0f0f0f');
define('SECONDARY_BG', '#1a1a1a');
define('ACCENT_COLOR', '#e50914');
define('TEXT_PRIMARY', '#ffffff');
define('TEXT_SECONDARY', '#808080');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'showflow');

// Messages
define('MSG_LOGIN_REQUIRED', 'Please log in to continue');
define('MSG_UNAUTHORIZED', 'You do not have permission to access this page');
?>
