<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

function resetUsageCount($promoId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE promo_codes SET times_used = 0 WHERE id = ?");
        $stmt->execute([$promoId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

$error = '';
$success = '';

// Check for success or error messages in the session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle form submission for adding/editing promo codes
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Handle activation/deactivation
        if ($_POST['action'] == 'toggle_active') {
            $id = intval($_POST['id']);
            $is_active = intval($_POST['is_active']);
            try {
                $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
                $stmt->execute([$is_active, $id]);
                $_SESSION['success'] = "Promo code status updated successfully.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating promo code status: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'reset_usage') {
            $id = intval($_POST['id']);
            if (resetUsageCount($id)) {
                $_SESSION['success'] = "Usage count reset successfully.";
            } else {
                $_SESSION['error'] = "Error resetting usage count.";
            }
        }
    } else {
        // Handle adding/editing promo code
        $code = sanitize_input($_POST['code']);
        $discount_type = sanitize_input($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $min_purchase = floatval($_POST['min_purchase']);
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = sanitize_input($_POST['end_date']);
        $usage_limit = empty($_POST['usage_limit']) ? null : intval($_POST['usage_limit']);
        $per_customer_limit = empty($_POST['per_customer_limit']) ? null : intval($_POST['per_customer_limit']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($code) || empty($discount_type) || $discount_value <= 0) {
            $_SESSION['error'] = "Please fill all required fields.";
        } else {
            try {
                if (isset($_POST['id'])) {
                    // Update existing promo code
                    $stmt = $pdo->prepare("UPDATE promo_codes SET code = ?, discount_type = ?, discount_value = ?, min_purchase = ?, start_date = ?, end_date = ?, usage_limit = ?, per_customer_limit = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_purchase, $start_date, $end_date, $usage_limit, $per_customer_limit, $is_active, $_POST['id']]);
                    $_SESSION['success'] = "Promo code updated successfully.";
                } else {
                    // Add new promo code
                    $stmt = $pdo->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, min_purchase, start_date, end_date, usage_limit, per_customer_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_purchase, $start_date, $end_date, $usage_limit, $per_customer_limit, $is_active]);
                    $_SESSION['success'] = "Promo code added successfully.";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        }
    }
    // Redirect to prevent form resubmission
    header("Location: manage_promo_codes.php");
    exit();
}

// Fetch all promo codes
$stmt = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC");
$promo_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promo Codes</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Promo Codes</h1>
            <?php if ($error): ?>
                <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
            <?php endif; ?>

            <form method="post" class="add-form">
                <input type="text" name="code" placeholder="Promo Code" required>
                <select name="discount_type" required>
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
                <input type="number" name="discount_value" step="0.01" placeholder="Discount Value" required>
                <input type="number" name="min_purchase" step="0.01" placeholder="Minimum Purchase">
                <input type="date" name="start_date" required>
                <input type="date" name="end_date" required>
                <input type="number" name="usage_limit" placeholder="Usage Limit (optional)">
                <input type="number" name="per_customer_limit" placeholder="Per Customer Limit (optional)" value="<?php echo htmlspecialchars($promo['per_customer_limit'] ?? ''); ?>">
                <label><input type="checkbox" name="is_active" checked> Active</label>
                <button type="submit" class="btn">Add Promo Code</button>
            </form>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Min Purchase</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Usage</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promo_codes as $promo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($promo['code']); ?></td>
                        <td><?php echo ucfirst($promo['discount_type']); ?></td>
                        <td><?php echo $promo['discount_value']; ?><?php echo $promo['discount_type'] == 'percentage' ? '%' : '$'; ?></td>
                        <td>$<?php echo number_format($promo['min_purchase'], 2); ?></td>
                        <td><?php echo $promo['start_date']; ?></td>
                        <td><?php echo $promo['end_date']; ?></td>
                        <td>
                            <?php echo $promo['times_used']; ?> 
                            <?php if ($promo['usage_limit']): ?>
                                / <?php echo $promo['usage_limit']; ?> (total)
                            <?php endif; ?>
                            <?php if ($promo['per_customer_limit']): ?>
                                <br><?php echo $promo['per_customer_limit']; ?> per customer
                            <?php endif; ?>
                        </td>
                        <td><?php echo $promo['is_active'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <button onclick="editPromoCode(<?php echo $promo['id']; ?>)" class="btn btn-small">Edit</button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $promo['is_active'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn btn-small"><?php echo $promo['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="reset_usage">
                                <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                <button type="submit" class="btn btn-small" onclick="return confirm('Are you sure you want to reset the usage count?')">Reset Usage</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all edit buttons
    document.querySelectorAll('.edit-promo-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const promoId = this.getAttribute('data-id');
            editPromoCode(promoId);
        });
    });
});

function editPromoCode(promoId) {
    // Fetch the promo code data
    fetch(`get_promo_code.php?id=${promoId}`)
        .then(response => response.json())
        .then(promo => {
            if (promo.error) {
                console.error('Error:', promo.error);
                return;
            }
            
            document.querySelector('input[name="code"]').value = promo.code;
            document.querySelector('select[name="discount_type"]').value = promo.discount_type;
            document.querySelector('input[name="discount_value"]').value = promo.discount_value;
            document.querySelector('input[name="min_purchase"]').value = promo.min_purchase;
            document.querySelector('input[name="start_date"]').value = promo.start_date;
            document.querySelector('input[name="end_date"]').value = promo.end_date;
            document.querySelector('input[name="usage_limit"]').value = promo.usage_limit || '';
            document.querySelector('input[name="per_customer_limit"]').value = promo.per_customer_limit || '';
            document.querySelector('input[name="is_active"]').checked = promo.is_active == 1;
            
            let form = document.querySelector('.add-form');
            let idInput = form.querySelector('input[name="id"]');
            if (idInput) {
                idInput.value = promo.id;
            } else {
                form.insertAdjacentHTML('beforeend', `<input type="hidden" name="id" value="${promo.id}">`);
            }
            form.querySelector('button[type="submit"]').textContent = 'Update Promo Code';

            // Scroll to the form
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>