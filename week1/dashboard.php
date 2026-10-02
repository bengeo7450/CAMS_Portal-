<?php
/**
 * File: dashboard.php
 * Path: ProjectFiles/week2/dashboard.php
 * Module: Client Portal & Service Records
 * Author: Ben George
 */

session_start();
require_once dirname(__DIR__) . '/db.php';

// Security Guard: Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../week1/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Client Appointments
try {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = :user_id ORDER BY appointment_date DESC");
    $stmt->execute([':user_id' => $user_id]);
    $user_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal - CAMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        h2, h3 { margin: 0; color: #111; }
        .btn { padding: 9px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9em; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0069d9; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e0e0e0; vertical-align: top; font-size: 0.9em; }
        th { background: #f8f9fa; color: #444; font-weight: bold; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; }
        .status-Scheduled { background: #cce5ff; color: #004085; }
        .status-In-Progress { background: #fff3cd; color: #856404; }
        .status-Completed { background: #d4edda; color: #155724; }
        .status-Cancelled { background: #f8f9fa; color: #721c24; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div>
            <h2>Client Service Portal</h2>
            <small style="color: #666;">Review your vehicle maintenance appointments and service records</small>
        </div>
        <div class="header-actions">
            <a href="../week1/booking.php" class="btn btn-primary">+ Book New Appointment</a>
            <a href="../week1/logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <!-- User Appointment History -->
    <h3>My Appointments</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Type</th>
                <th>Date &amp; Time</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Staff Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($user_appointments)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #777; padding: 20px;">You have no scheduled appointments.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($user_appointments as $row): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['service_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo date('M d, Y @ h:i A', strtotime($row['appointment_date'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars(trim(($row['vehicle_year'] ?? '') . ' ' . ($row['vehicle_make'] ?? '') . ' ' . ($row['vehicle_model'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '-', $row['status']); ?>">
                                <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['staff_notes'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>