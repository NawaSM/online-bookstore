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

// Handle admin actions (create, revoke, etc.)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // Create new admin logic
                break;
            case 'revoke':
                // Revoke admin access logic
                break;
            // Add other actions as needed
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
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Admins</h1>
            
            <!-- Display error/success messages -->
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

            <!-- Form to create new admin -->
            <h2>Create New Admin</h2>
            <form action="manage_admins.php" method="post">
                <input type="hidden" name="action" value="create">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role">
                    <option value="regular">Regular Admin</option>
                    <option value="senior">Senior Admin</option>
                </select>
                <button type="submit">Create Admin</button>
            </form>

            <!-- List of existing admins -->
            <h2>Existing Admins</h2>
            <table>
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
                        <td><?php echo htmlspecialchars($admin['role']); ?></td>
                        <td>
                            <form action="manage_admins.php" method="post">
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to revoke this admin\'s access?')">Revoke Access</button>
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