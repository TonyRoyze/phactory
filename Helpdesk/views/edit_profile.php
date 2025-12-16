<?php
// This file is views/edit_profile.php

if (!isset($_SESSION['user_id'])) {
    echo '<h1>Access Denied</h1>';
    exit();
}

require 'connector.php';

$user_id = $_SESSION['user_id'];
$user_data = null;
$success_message = '';
$error_message = '';

// Check for status messages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success_message = 'Profile updated successfully!';
    } elseif ($_GET['status'] === 'error') {
        $error_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'An error occurred while updating your profile.';
    }
}

if ($user_id > 0) {
    $sql = "SELECT user_id, username, email, full_name, user_role, created_at FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user_data = $result->fetch_assoc();
    }
}

if ($user_data) {
    ?>
    <div class="profile-container">
        <h1>Edit Your Profile</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="icon-check"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="icon-warning"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="profile-info">
            <div class="info-card">
                <h3>Account Information</h3>
                <div class="info-row">
                    <span class="label">User ID:</span>
                    <span class="value">#<?= htmlspecialchars($user_data['user_id']) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Account Type:</span>
                    <span class="value role-<?= strtolower($user_data['user_role']) ?>">
                        <?= htmlspecialchars($user_data['user_role']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Member Since:</span>
                    <span class="value"><?= date('F j, Y', strtotime($user_data['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <form action="actions/update_profile.php" method="POST" class="profile-form" id="profileForm">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['user_id']) ?>">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($user_data['full_name']) ?>" 
                           required maxlength="255">
                    <small class="form-help">Your full name as it appears on official documents</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($user_data['email']) ?>" 
                           required maxlength="255">
                    <small class="form-help">Used for notifications and account recovery</small>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($user_data['username']) ?>" 
                           required maxlength="100" pattern="[a-zA-Z0-9_-]+" 
                           title="Username can only contain letters, numbers, underscores, and hyphens">
                    <small class="form-help">Used for login and identification</small>
                </div>
            </div>

            <div class="form-section">
                <h3>Security Settings</h3>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password">
                    <small class="form-help">Required to change password or sensitive information</small>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                    <small class="form-help">Leave blank to keep current password (minimum 6 characters)</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                    <small class="form-help">Re-enter your new password to confirm</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-save"></i> Update Profile
                </button>
                <a href="app.php?view=dashboard" class="btn btn-secondary">
                    <i class="icon-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Validate password confirmation
        if (newPassword && newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New password and confirmation do not match.');
            return false;
        }
        
        // Validate password strength
        if (newPassword && newPassword.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            return false;
        }
        
        return true;
    });

    // Real-time password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        
        if (confirmPassword && newPassword !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    </script>
    <?php
} else {
    echo '<div class="error-container">';
    echo '<h1>User Not Found</h1>';
    echo '<p>Unable to load your profile information. Please try logging in again.</p>';
    echo '<a href="login.php" class="btn btn-primary">Login</a>';
    echo '</div>';
}
