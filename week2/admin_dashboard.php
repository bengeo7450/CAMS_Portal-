<?php
/**
 * File: admin_dashboard.php
 * Path: ProjectFiles/week2/admin_dashboard.php
 * Module: Admin CRM
 * Author: Ben George
 */

session_start();

// Disable Browser Caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once dirname(__DIR__) . '/db.php';

// Security Guard: Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../week1/login.php");
    exit();
}

$message = '';
$error = '';

// ==========================================
// 1. BACKEND ACTION HANDLERS (POST Actions)
// ==========================================

// Action: Delete Appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $appt_id = intval($_POST['appointment_id'] ?? 0);
    if ($appt_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
            $stmt->execute([':id' => $appt_id]);
            $message = "Appointment #{$appt_id} deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting record: " . $e->getMessage();
        }
    }
}

// Action: Update Appointment (Inline Edit: Status & Staff Notes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $appt_id     = intval($_POST['appointment_id'] ?? 0);
    $status      = trim($_POST['status'] ?? '');
    $staff_notes = trim($_POST['staff_notes'] ?? '');

    if ($appt_id > 0 && !empty($status)) {
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = :status, staff_notes = :notes WHERE id = :id");
            $stmt->execute([
                ':status' => $status,
                ':notes'  => $staff_notes,
                ':id'     => $appt_id
            ]);
            $message = "Appointment #{$appt_id} updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating record: " . $e->getMessage();
        }
    }
}

// Action: Add New Appointment (with Double-Booking & Business Hours Check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $user_id      = intval($_POST['user_id'] ?? $_SESSION['user_id']);
    $service_type = trim($_POST['service_type'] ?? '');
    $appt_date    = trim($_POST['appointment_date'] ?? '');
    $v_make       = trim($_POST['vehicle_make'] ?? '');
    $v_model      = trim($_POST['vehicle_model'] ?? '');
    $v_year       = trim($_POST['vehicle_year'] ?? '');
    $staff_notes  = trim($_POST['staff_notes'] ?? '');

    if (!empty($service_type) && !empty($appt_date)) {
        $timestamp = strtotime($appt_date);
        $hour      = intval(date('H', $timestamp));
        $dayOfWeek = date('N', $timestamp); // 1 (Mon) to 7 (Sun)

        // Business Hours Guard: Mon-Fri, 8 AM - 5 PM (17:00)
        if ($dayOfWeek > 5 || $hour < 8 || $hour >= 17) {
            $error = "Appointments must be scheduled within business hours (Mon-Fri, 8:00 AM - 5:00 PM).";
        } else {
            $formatted_date = date('Y-m-d H:i:s', $timestamp);

            // Double-Booking Guard: Check 30-minute window overlap
            $check_sql = "SELECT COUNT(*) FROM appointments 
                          WHERE status != 'Cancelled' 
                            AND appointment_date BETWEEN DATE_SUB(:app_date, INTERVAL 29 MINUTE) 
                                                     AND DATE_ADD(:app_date2, INTERVAL 29 MINUTE)";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':app_date' => $formatted_date, ':app_date2' => $formatted_date]);

            if ($check_stmt->fetchColumn() > 0) {
                $error = "Conflict detected: The selected time slot overlaps with an existing appointment.";
            } else {
                try {
                    $insert_sql = "INSERT INTO appointments (user_id, service_type, appointment_date, vehicle_make, vehicle_model, vehicle_year, staff_notes, status) 
                                   VALUES (:u_id, :service, :app_date, :make, :model, :v_year, :notes, 'Scheduled')";
                    $stmt = $pdo->prepare($insert_sql);
                    $stmt->execute([
                        ':u_id'     => $user_id,
                        ':service'  => $service_type,
                        ':app_date' => $formatted_date,
                        ':make'     => $v_make,
                        ':model'    => $v_model,
                        ':v_year'   => $v_year,
                        ':notes'    => $staff_notes
                    ]);
                    $message = "New appointment created successfully.";
                } catch (PDOException $e) {
                    $error = "Error adding record: " . $e->getMessage();
                }
            }
        }
    } else {
        $error = "Please fill in all required fields (Service Type and Appointment Date).";
    }
}

// ==========================================
// 2. SEARCH & DATA RETRIEVAL
// ==========================================
$search_query = trim($_GET['search'] ?? '');

try {
    if ($search_query !== '') {
        $stmtAll = $pdo->prepare("
            SELECT a.*, u.name AS username, u.email 
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE u.name LIKE :q 
               OR u.email LIKE :q 
               OR a.service_type LIKE :q 
               OR a.vehicle_make LIKE :q 
               OR a.vehicle_model LIKE :q 
               OR a.staff_notes LIKE :q
               OR a.status LIKE :q
            ORDER BY a.appointment_date DESC
        ");
        $stmtAll->execute([':q' => "%{$search_query}%"]);
    } else {
        $stmtAll = $pdo->query("
            SELECT a.*, u.name AS username, u.email 
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.appointment_date DESC
        ");
    }
    $allAppointments = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin CRM & Service Portal - CAMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        h2, h3 { margin: 0; color: #111; }
        .btn { padding: 7px 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.85em; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; }

        /* Search Bar & Forms */
        .search-container { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-container input[type="text"] { flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; }
        
        .add-form-card { background: #f8f9fa; border: 1px solid #e0e0e0; padding: 15px; border-radius: 6px; margin-bottom: 25px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 10px; }
        .form-row input, .form-row select { padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85em; }

        /* CRM Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e0e0e0; vertical-align: middle; font-size: 0.88em; }
        th { background: #f8f9fa; color: #444; font-weight: bold; }
        .inline-form { display: flex; gap: 5px; align-items: center; }
        .inline-form select, .inline-form input[type="text"] { padding: 4px; font-size: 0.85em; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div>
            <h2>Admin CRM & Service Portal</h2>
            <small style="color: #666;">Search, add, edit, and track client appointments and interaction records</small>
        </div>
        <div class="header-actions">
            <a href="calendar.php" class="btn btn-secondary">View Calendar</a>
            <a href="../week1/logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?><div class="alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <!-- Goal A: Search CRM Interface -->
    <form method="GET" action="admin_dashboard.php" class="search-container">
        <input type="text" name="search" placeholder="Search by Client Name, Email, Service Type, Vehicle, or Notes..." value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn btn-primary">Search CRM</button>
        <?php if ($search_query !== ''): ?>
            <a href="admin_dashboard.php" class="btn btn-secondary">Reset Search</a>
        <?php endif; ?>
    </form>

    <!-- Goal Requirement: Add Item Directly from Search/CRM Screen -->
    <details class="add-form-card" open>
        <summary style="font-weight: bold; cursor: pointer; color: #007bff;">+ Schedule New Appointment (Admin Override)</summary>
        <form method="POST" action="admin_dashboard.php" style="margin-top: 15px;">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <input type="number" name="user_id" placeholder="Client User ID" value="<?php echo htmlspecialchars((string)$_SESSION['user_id'], ENT_QUOTES, 'UTF-8'); ?>" required>
                <select name="service_type" required>
                    <option value="">-- Select Service --</option>
                    <option value="Oil Change & Filter">Oil Change &amp; Filter</option>
                    <option value="Brake Service & Inspection">Brake Service &amp; Inspection</option>
                    <option value="Tire Rotation & Balance">Tire Rotation &amp; Balance</option>
                    <option value="Engine Diagnostics">Engine Diagnostics</option>
                </select>
                <input type="datetime-local" name="appointment_date" required>
                <input type="text" name="vehicle_make" placeholder="Make (e.g. Ford)">
                <input type="text" name="vehicle_model" placeholder="Model (e.g. F-350)">
                <input type="number" name="vehicle_year" placeholder="Year (e.g. 2026)">
            </div>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="staff_notes" placeholder="Initial Staff Notes / Service Instructions" style="flex: 1; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="submit" class="btn btn-success">Schedule Appointment</button>
            </div>
        </form>
    </details>

    <!-- Goal B: CRM Records Table with Inline Edit & Delete -->
    <h3>Client Service Records (<?php echo count($allAppointments); ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client Name</th>
                <th>Service Details</th>
                <th>Date & Time</th>
                <th>Vehicle</th>
                <th>Status & Staff Notes (Inline Edit)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($allAppointments)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #777; padding: 20px;">No matching records found in CRM database.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($allAppointments as $row): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['username'] ?? 'User #' . $row['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['service_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo date('M d, Y @ h:i A', strtotime($row['appointment_date'])); ?></td>
                        <td><?php echo htmlspecialchars(trim(($row['vehicle_year'] ?? '') . ' ' . ($row['vehicle_make'] ?? '') . ' ' . ($row['vehicle_model'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                        
                        <!-- Inline Edit Form for Status & Staff Notes -->
                        <td>
                            <form method="POST" action="admin_dashboard.php" class="inline-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                <select name="status">
                                    <option value="Scheduled" <?php echo $row['status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="In-Progress" <?php echo $row['status'] === 'In-Progress' ? 'selected' : ''; ?>>In-Progress</option>
                                    <option value="Completed" <?php echo $row['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $row['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <input type="text" name="staff_notes" value="<?php echo htmlspecialchars($row['staff_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Add staff notes...">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                        </td>

                        <!-- Delete Action Form -->
                        <td>
                            <form method="POST" action="admin_dashboard.php" onsubmit="return confirm('Are you sure you want to delete appointment #<?php echo $row['id']; ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>