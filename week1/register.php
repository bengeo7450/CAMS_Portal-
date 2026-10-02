<?php
session_start();

// Redirect if already logged in based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../week2/admin_dashboard.php");
    } else {
        header("Location: booking.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'client';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $host = 'localhost';
        $dbname = 'cams_db';
        $db_user = 'root';
        $db_pass = '';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                $error = "An account with this email address already exists.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
                $insertStmt->execute([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $hashedPassword,
                    'role'     => $role
                ]);

                $success = "Registration successful! You can now log in.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAMS Portal - Register</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .register-card { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        .register-card h2 { margin-top: 0; margin-bottom: 20px; text-align: center; color: #1c1e21; }
        .error-msg { color: #dc3545; font-size: 14px; margin-bottom: 15px; }
        .success-msg { color: #198754; font-size: 14px; margin-bottom: 15px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #1c1e21; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #0066ff; }
        .btn-submit { width: 100%; padding: 10px; background-color: #0066ff; border: none; border-radius: 4px; color: white; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background-color: #0052cc; }
        .login-link { margin-top: 20px; text-align: center; font-size: 14px; color: #606770; }
        .login-link a { color: #0066ff; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="register-card">
    <h2>Create CAMS Account</h2>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required placeholder="John Doe">
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="name@example.com">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="role">Account Type</label>
            <select id="role" name="role">
                <option value="client">Client</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">Register</button>
    </form>

    <div class="login-link">
        <p>Already have an account? <a href="login.php">Log in here</a></p>
    </div>
</div>

</body>
</html>