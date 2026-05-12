<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $manager_id = $_POST['manager_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($manager_id) && !empty($password)) {
        $query = "SELECT m_id, manager_name, t_id, password FROM manager WHERE m_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $manager_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $manager = $result->fetch_assoc();
            if (password_verify($password, $manager['password'])) {
                setUserSession(ROLE_MANAGER, $manager['m_id'], [
                    'm_id' => $manager['m_id'],
                    't_id' => $manager['t_id'],
                    'name' => $manager['manager_name']
                ]);
                header('Location: manager-dashboard.php');
                exit();
            } else {
                $error = 'Invalid manager ID or password';
            }
        } else {
            $error = 'Invalid manager ID or password';
        }
        $stmt->close();
    } else {
        $error = 'Please fill all fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Login - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
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
        
        .login-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-title">🎬 ShowFlow</div>
            <div class="login-subtitle">Manager Portal</div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Manager ID</label>
                    <input type="text" name="manager_id" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div style="text-align: center; margin-top: 1rem; color: var(--text-secondary); font-size: 12px;">
                <p>Back to <a href="index.php" style="color: var(--accent-red); text-decoration: none;">Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
