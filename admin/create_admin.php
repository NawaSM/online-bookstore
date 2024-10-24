<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if the user is logged in and is a senior admin
if (!is_admin_logged_in() || $_SESSION['admin_role'] !== 'senior') {
    redirect('login.php');
}

$error = '';
$success = '';

// Handle admin actions (create, revoke, change role)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $username = sanitize_input($_POST['username']);
                $password = $_POST['password'];
                $email = sanitize_input($_POST['email']);
                $role = sanitize_input($_POST['role']);

                try {
                    // Check if username or email already exists
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    $existing_admin = $stmt->fetch();

                    if ($existing_admin) {
                        $error = "Username or email already exists.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$username, $hashed_password, $email, $role]);
                        $success = "Admin account created successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error creating admin account: " . $e->getMessage();
                }
                break;

            case 'change_role':
                $admin_id = intval($_POST['admin_id']);
                $new_role = $_POST['new_role'];

                // Check if this would remove the last senior admin
                if ($new_role === 'regular') {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'senior'");
                    $senior_count = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    $current_role = $stmt->fetchColumn();
                    
                    if ($senior_count <= 1 && $current_role === 'senior') {
                        $error = "Cannot demote the last senior admin.";
                        break;
                    }
                }

                try {
                    $stmt = $pdo->prepare("UPDATE admins SET role = ? WHERE id = ?");
                    $stmt->execute([$new_role, $admin_id]);
                    $success = "Admin role updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating admin role: " . $e->getMessage();
                }
                break;

            case 'revoke':
                $admin_id = intval($_POST['admin_id']);
                
                // Check if this is a senior admin before revoking
                $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
                $stmt->execute([$admin_id]);
                $role = $stmt->fetchColumn();

                if ($role === 'senior') {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'senior'");
                    $senior_count = $stmt->fetchColumn();
                    
                    if ($senior_count <= 1) {
                        $error = "Cannot revoke access for the last senior admin.";
                        break;
                    }
                }

                try {
                    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    $success = "Admin access revoked successfully!";
                } catch (PDOException $e) {
                    $error = "Error revoking admin access: " . $e->getMessage();
                }
                break;
        }
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
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Admins</h1>
            
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

            <!-- Form to create new admin -->
            <div class="admin-form">
                <h2>Create New Admin</h2>
                <form action="manage_admins.php" method="post" class="form-group">
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
                            <form action="manage_admins.php" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()">
                                    <option value="regular" <?php echo $admin['role'] === 'regular' ? 'selected' : ''; ?>>Regular Admin</option>
                                    <option value="senior" <?php echo $admin['role'] === 'senior' ? 'selected' : ''; ?>>Senior Admin</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form action="manage_admins.php" method="post" style="display: inline;">
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