<?php
/**
 * File: login.php
 * Path: ProjectFiles/week1/login.php
 * Module: Authenticated Login with CSRF Protection & Brute-Force Rate Limiting
 * Author: Ben George
 */

session_start();

// 1. Generate CSRF Token if not already present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Initialize Login Attempt Counter in Session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_failed_attempt'] = null;
}

// 2. Lockout Counter / Rate Limiting Check (Block for 15 minutes after 5 failed attempts)
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 minutes in seconds

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_passed = time() - $_SESSION['last_failed_attempt'];
    if ($time_passed < $lockout_time) {
        $remaining_minutes = ceil(($lockout_time - $time_passed) / 60);
        $error = "Too many failed login attempts. Your account is temporarily locked. Please try again in {$remaining_minutes} minute(s).";
    } else {
        // Reset counter after lockout period expires
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_failed_attempt'] = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // 3. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=cams_db;charset=utf8", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login: Reset attempt counters and regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['login_attempts'] = 0;
                $_SESSION['last_failed_attempt'] = null;

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = strtolower($user['role']);

                // 4. Role-Based Access Control (RBAC) Routing
                if ($_SESSION['role'] === 'admin') {
                    header("Location: ../week2/admin_dashboard.php");
                    exit();
                } else {
                    header("Location: ../week1/dashboard.php");
                    exit();
                }
            } else {
                // Increment failed attempts on wrong credentials
                $_SESSION['login_attempts'] += 1;
                $_SESSION['last_failed_attempt'] = time();
                
                $remaining = $max_attempts - $_SESSION['login_attempts'];
                if ($remaining > 0) {
                    $error = "Invalid email or password. You have {$remaining} attempt(s) remaining.";
                } else {
                    $error = "Too many failed login attempts. Your account is locked for 15 minutes.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CAMS Portal - Secure Login</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 100%; max-width: 360px; }
        .login-card h2 { margin-top: 0; color: #1a202c; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #3182ce; color: #fff; border: none; padding: 10px; border-radius: 4px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-submit:disabled { background: #a0aec0; cursor: not-allowed; }
        .error-msg { background: #fed7d7; color: #9b2c2c; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .auth-footer { margin-top: 15px; text-align: center; font-size: 13px; color: #718096; }
        .auth-footer a { color: #3182ce; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Login to CAMS</h2>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <!-- Hidden CSRF Token Field -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="user@example.com">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn-submit" <?= ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_failed_attempt']) < $lockout_time) ? 'disabled' : '' ?>>Sign In</button>
    </form>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>