<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getBaseUrl() . 'pages/login.php');
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    exit('An error occurred while fetching user data.');
}
?>

<div class="details-section">
    <div class="section-header">
        <h2>Personal Details</h2>
        <div class="edit-buttons">
            <button type="button" id="editButton" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button type="button" id="cancelButton" class="btn btn-secondary" style="display: none;">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>

    <form id="profileForm" class="profile-form">
        <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" class="form-control view-mode"
                   value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                   required disabled>
        </div>
        
        <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" class="form-control view-mode"
                   value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                   required disabled>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control view-mode"
                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                   required disabled readonly>
        </div>
        
        <h3>Address Information</h3>
        
        <div class="form-group">
            <label for="houseNumber">House Number</label>
            <input type="text" id="houseNumber" name="houseNumber" class="form-control view-mode"
                   value="<?php echo htmlspecialchars($user['house_number']); ?>" 
                   required disabled>
        </div>
        
        <div class="form-group">
            <label for="streetName">Street Name</label>
            <input type="text" id="streetName" name="streetName" class="form-control view-mode"
                   value="<?php echo htmlspecialchars($user['street_name']); ?>" 
                   required disabled>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" class="form-control view-mode"
                       value="<?php echo htmlspecialchars($user['city']); ?>" 
                       required disabled>
            </div>
            
            <div class="form-group">
                <label for="district">District</label>
                <input type="text" id="district" name="district" class="form-control view-mode"
                       value="<?php echo htmlspecialchars($user['district']); ?>" 
                       required disabled>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="state">State</label>
                <input type="text" id="state" name="state" class="form-control view-mode"
                       value="<?php echo htmlspecialchars($user['state']); ?>" 
                       required disabled>
            </div>
            
            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" class="form-control view-mode"
                       value="<?php echo htmlspecialchars($user['country']); ?>" 
                       required disabled>
            </div>
        </div>
        
        <button type="submit" id="saveButton" class="btn btn-success" style="display: none;">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </form>
</div>

<!-- Password Verification Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Verify Password</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Please enter your current password to continue:</p>
            <div class="form-group">
                <div class="password-input-group">
                    <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                    <button type="button" class="password-toggle" data-target="currentPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <span class="password-error" style="display: none; color: red;"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-close">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmPassword">Confirm</button>
        </div>
    </div>
</div>

<!-- Password Change Form -->
<div class="password-section">
    <h3>Change Password</h3>
    <form id="passwordForm" class="password-form">
        <div class="form-group">
            <label for="newPassword">New Password</label>
            <div class="password-input-group">
                <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                <button type="button" class="password-toggle" data-target="newPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        
        <div class="form-group">
            <label for="confirmNewPassword">Confirm New Password</label>
            <div class="password-input-group">
                <input type="password" id="confirmNewPassword" name="confirmNewPassword" class="form-control" required>
                <button type="button" class="password-toggle" data-target="confirmNewPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div id="passwordMismatch" class="error-message" style="display: none;">
                Passwords do not match
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-key"></i> Change Password
        </button>
    </form>
</div>