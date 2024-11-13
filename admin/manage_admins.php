<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and is a senior admin
if (!is_admin_logged_in() || $_SESSION['admin_role'] !== 'senior') {
    redirect('login.php');
}

$error = '';
$success = '';

// Debug: Print POST data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log('POST Data: ' . print_r($_POST, true));
}

// Handle admin actions (create, revoke, change role)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            // Debug: Print received data
            error_log('Creating new admin...');
            error_log('Username: ' . $_POST['username']);
            error_log('Email: ' . $_POST['email']);

            $username = sanitize_input($_POST['username']);
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password'];
            $role = sanitize_input($_POST['role']);

            if (empty($username) || empty($email) || empty($password)) {
                $error = "All fields are required.";
                error_log('Validation failed: Empty fields');
            } else {
                try {
                    // Check for existing admin
                    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        $error = "Username or email already exists.";
                        error_log('Admin already exists');
                    } else {
                        // Create new admin
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
                        $result = $stmt->execute([$username, $hashed_password, $email, $role]);
                        
                        if ($result) {
                            $success = "Admin account created successfully!";
                            error_log('Admin created successfully');
                        } else {
                            $error = "Failed to create admin account.";
                            error_log('Failed to create admin: ' . print_r($stmt->errorInfo(), true));
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                    error_log('Database error: ' . $e->getMessage());
                }
            }
            break;
        case 'revoke':
            $admin_id = intval($_POST['admin_id']);
            
            try {
                // First check if this is a senior admin
                $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
                $stmt->execute([$admin_id]);
                $role = $stmt->fetchColumn();
        
                // Count total senior admins if trying to revoke a senior admin
                if ($role === 'senior') {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'senior'");
                    $senior_count = $stmt->fetchColumn();
                    
                    if ($senior_count <= 1) {
                        $error = "Cannot revoke access for the last senior admin.";
                        error_log('Attempted to revoke last senior admin');
                        break;
                    }
                }
        
                // If we get here, it's safe to revoke access
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                if ($stmt->execute([$admin_id])) {
                    $success = "Admin access revoked successfully!";
                    error_log('Admin access revoked for ID: ' . $admin_id);
        
                    // Refresh page to show updated list
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Failed to revoke admin access.";
                    error_log('Failed to revoke admin access for ID: ' . $admin_id);
                }
            } catch (PDOException $e) {
                $error = "Error revoking admin access: " . $e->getMessage();
                error_log('Database error while revoking access: ' . $e->getMessage());
            }
            break;
    }
}


// Fetch all admins except the current one
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id != ? ORDER BY username");
$stmt->execute([$_SESSION['admin_id']]);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Admins</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Form to create new admin -->
            <div class="admin-form">
                <h2>Create New Admin</h2>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role" required>
                            <option value="regular">Regular Admin</option>
                            <option value="senior">Senior Admin</option>
                        </select>
                    </div>

                    <button type="submit" class="btn">Create Admin</button>
                </form>
            </div>

            <!-- List of existing admins -->
            <h2>Existing Admins</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()">
                                    <option value="regular" <?php echo $admin['role'] === 'regular' ? 'selected' : ''; ?>>Regular Admin</option>
                                    <option value="senior" <?php echo $admin['role'] === 'senior' ? 'selected' : ''; ?>>Senior Admin</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to revoke this admin\'s access?')">Revoke Access</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>