<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $pdo->beginTransaction();
                    
                    // Get the form data
                    $title = sanitize_input($_POST['title']);
                    $type = sanitize_input($_POST['type']);
                    $display_order = intval($_POST['display_order']);
            
                    if ($type === 'image') {
                        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                            throw new Exception('No image uploaded or upload error');
                        }
            
                        $file = $_FILES['image'];
                        $allowedTypes = ['image/jpeg', 'image/png'];
                        $maxSize = 2 * 1024 * 1024; // 2MB
            
                        if (!in_array($file['type'], $allowedTypes)) {
                            throw new Exception('Invalid file type. Only JPG and PNG are allowed.');
                        }
            
                        if ($file['size'] > $maxSize) {
                            throw new Exception('File size too large. Maximum size is 2MB.');
                        }
            
                        // Read image data
                        $imageData = file_get_contents($file['tmp_name']);
                        $imageType = $file['type'];
            
                        $stmt = $pdo->prepare("INSERT INTO banner_ads (title, type, image_data, image_type, display_order) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $type, $imageData, $imageType, $display_order]);
                        
                    } else {
                        // Handle promo banner
                        $promo_heading = sanitize_input($_POST['promo_heading']);
                        $promo_text = sanitize_input($_POST['promo_text']);
                        $stmt = $pdo->prepare("INSERT INTO banner_ads (title, type, promo_heading, promo_text, display_order) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $type, $promo_heading, $promo_text, $display_order]);
                    }
                    
                    $pdo->commit();
                    $success = "Banner ad added successfully!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'update_order':
                $id = intval($_POST['id']);
                $new_order = intval($_POST['new_order']);
                $stmt = $pdo->prepare("UPDATE banner_ads SET display_order = ? WHERE id = ?");
                $stmt->execute([$new_order, $id]);
                $success = "Display order updated!";
                break;

            case 'toggle_active':
                $id = intval($_POST['id']);
                $is_active = intval($_POST['is_active']);
                $stmt = $pdo->prepare("UPDATE banner_ads SET is_active = ? WHERE id = ?");
                $stmt->execute([$is_active, $id]);
                $success = "Status updated successfully!";
                break;

            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM banner_ads WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Banner ad deleted successfully!";
                break;
        }
    }
}

// Fetch all banner ads
$stmt = $pdo->query("SELECT * FROM banner_ads ORDER BY display_order ASC");
$banner_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Banner Ads</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Banner Ads</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="info-box">
                <h3>Image Guidelines:</h3>
                <ul>
                    <li>Recommended resolution: 1920x600 pixels (desktop)</li>
                    <li>Minimum resolution: 1200x375 pixels</li>
                    <li>Maximum file size: 2MB</li>
                    <li>Supported formats: JPG, PNG</li>
                    <li>Aspect ratio: 3.2:1</li>
                </ul>
            </div>

            <!-- Add New Banner Ad Form -->
            <div class="form-section">
                <h2>Add New Banner Ad</h2>
                <form action="" method="post" enctype="multipart/form-data" class="form-group">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="title">Title (Internal Reference):</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Type:</label>
                        <select id="type" name="type" required onchange="toggleFields()">
                            <option value="image">Image Banner</option>
                            <option value="promo">Promo Banner</option>
                        </select>
                    </div>

                    <div id="image-fields" class="form-group">
                        <label for="image">Banner Image:</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>

                    <div id="promo-fields" style="display:none;">
                        <div class="form-group">
                            <label for="promo_heading">Promo Heading:</label>
                            <input type="text" id="promo_heading" name="promo_heading">
                        </div>
                        <div class="form-group">
                            <label for="promo_text">Promo Text:</label>
                            <textarea id="promo_text" name="promo_text"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order:</label>
                        <input type="number" id="display_order" name="display_order" min="0" value="0" required>
                    </div>

                    <button type="submit" class="btn">Add Banner Ad</button>
                </form>
            </div>

            <!-- List of Existing Banner Ads -->
            <div class="table-section">
                <h2>Existing Banner Ads</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Preview</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banner_ads as $ad): ?>
                        <tr>
                            <td>
                                <input type="number" 
                                       value="<?php echo $ad['display_order']; ?>" 
                                       onchange="updateOrder(<?php echo $ad['id']; ?>, this.value)"
                                       min="0"
                                       class="order-input">
                            </td>
                            <td><?php echo htmlspecialchars($ad['title']); ?></td>
                            <td><?php echo ucfirst($ad['type']); ?></td>
                            <td>
                                <?php if ($ad['type'] === 'image'): ?>
                                    <img src="serve_image.php?type=banner&id=<?php echo $ad['id']; ?>" 
                                        alt="Banner Preview" 
                                        style="max-width: 200px; height: auto;">
                                <?php else: ?>
                                    <div class="promo-preview">
                                        <strong><?php echo htmlspecialchars($ad['promo_heading']); ?></strong><br>
                                        <?php echo htmlspecialchars($ad['promo_text']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" 
                                           <?php echo $ad['is_active'] ? 'checked' : ''; ?>
                                           onchange="toggleActive(<?php echo $ad['id']; ?>, this.checked)">
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td>
                                <button onclick="deleteBanner(<?php echo $ad['id']; ?>)" 
                                        class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function toggleFields() {
        const type = document.getElementById('type').value;
        document.getElementById('image-fields').style.display = type === 'image' ? 'block' : 'none';
        document.getElementById('promo-fields').style.display = type === 'promo' ? 'block' : 'none';
    }

    function updateOrder(id, newOrder) {
        fetch('manage_banner_ads.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_order&id=${id}&new_order=${newOrder}`
        })
        .then(response => window.location.reload());
    }

    function toggleActive(id, isActive) {
        fetch('manage_banner_ads.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle_active&id=${id}&is_active=${isActive ? 1 : 0}`
        })
        .then(response => window.location.reload());
    }

    function deleteBanner(id) {
        if (confirm('Are you sure you want to delete this banner ad?')) {
            fetch('manage_banner_ads.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&id=${id}`
            })
            .then(response => window.location.reload());
        }
    }
    </script>
</body>
</html>