<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

$error = '';
$tab = 'login';

// Check if redirect_to is passed as URL parameter
$redirect_to_param = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '';
if (!empty($redirect_to_param)) {
    $_SESSION['redirect_to'] = $redirect_to_param;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $user_id = $_POST['user_id'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (!empty($user_id) && !empty($password)) {
                $query = "SELECT u_id, name FROM user WHERE u_id = ? AND password = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $user_id, $password);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    setUserSession(ROLE_USER, $user['u_id'], [
                        'u_id' => $user['u_id'],
                        'name' => $user['name']
                    ]);
                    
                    $redirect = $_SESSION['redirect_to'] ?? 'index.php';
                    unset($_SESSION['redirect_to']);
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = 'Invalid user ID or password';
                }
                $stmt->close();
            } else {
                $error = 'Please fill all fields';
            }
        } elseif ($_POST['action'] === 'register') {
            $user_id = $_POST['new_user_id'] ?? '';
            $name = $_POST['name'] ?? '';
            $contact = $_POST['contact'] ?? '';
            $password = $_POST['new_password'] ?? '';
            
            if (!empty($user_id) && !empty($name) && !empty($contact) && !empty($password)) {
                // Check if user already exists
                $check = "SELECT u_id FROM user WHERE u_id = ? OR contact = ?";
                $stmt = $conn->prepare($check);
                $stmt->bind_param("ss", $user_id, $contact);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'User ID or contact already exists';
                } else {
                    $insert = "INSERT INTO user (u_id, name, contact, password) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert);
                    $stmt->bind_param("ssss", $user_id, $name, $contact, $password);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Account created successfully! Please login.';
                        $tab = 'login';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
                $stmt->close();
            } else {
                $error = 'Please fill all fields';
            }
        }
    }
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 2rem;
        }
        
        .login-title {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--accent-red);
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .tab-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 1rem;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            color: var(--accent-red);
            border-bottom-color: var(--accent-red);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-title">🎬 ShowFlow</div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-btn <?php echo $tab === 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">
                    Login
                </button>
                <button class="tab-btn <?php echo $tab === 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">
                    Sign Up
                </button>
            </div>
            
            <!-- Login Form -->
            <div class="tab-content <?php echo $tab === 'login' ? 'active' : ''; ?>" id="login-tab">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" name="user_id" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div class="tab-content <?php echo $tab === 'register' ? 'active' : ''; ?>" id="register-tab">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label>User ID (Username)</label>
                        <input type="text" name="new_user_id" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact (Email/Phone)</label>
                        <input type="text" name="contact" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </form>
            </div>
            
            <div style="text-align: center; margin-top: 1rem; color: var(--text-secondary); font-size: 12px;">
                <p>Back to <a href="index.php" style="color: var(--accent-red); text-decoration: none;">Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
