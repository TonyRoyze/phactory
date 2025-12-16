<?php
// This file is views/edit_user.php

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    echo '<div class="error-container">';
    echo '<h1>Access Denied</h1>';
    echo '<p>You need administrator privileges to access this page.</p>';
    echo '</div>';
    return;
}

require 'connector.php';

$user_id = $_GET['id'] ?? 0;
$user_data = null;
$success_message = '';
$error_message = '';

// Check for status messages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success_message = 'User updated successfully!';
    } elseif ($_GET['status'] === 'error') {
        $error_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'An error occurred while updating the user.';
    }
}

if ($user_id > 0) {
    $sql = "SELECT user_id, username, email, full_name, user_role, created_at, updated_at FROM users WHERE user_id = ?";
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
    <div class="edit-user-container">
        <div class="page-header">
            <h1>Edit User</h1>
            <div class="header-actions">
                <a href="app.php?view=users" class="btn btn-secondary">
                    <i class="icon-arrow-left"></i> Back to Users
                </a>
            </div>
        </div>
        
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

        <div class="user-info">
            <div class="info-card">
                <h3>User Information</h3>
                <div class="info-row">
                    <span class="label">User ID:</span>
                    <span class="value">#<?= htmlspecialchars($user_data['user_id']) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Created:</span>
                    <span class="value"><?= date('F j, Y g:i A', strtotime($user_data['created_at'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Last Updated:</span>
                    <span class="value"><?= date('F j, Y g:i A', strtotime($user_data['updated_at'])) ?></span>
                </div>
            </div>
        </div>

        <form action="actions/update_user.php" method="POST" class="edit-user-form" id="editUserForm">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['user_id']) ?>">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($user_data['full_name']) ?>" 
                           required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($user_data['email']) ?>" 
                           required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($user_data['username']) ?>" 
                           required maxlength="100" pattern="[a-zA-Z0-9_-]+" 
                           title="Username can only contain letters, numbers, underscores, and hyphens">
                </div>
            </div>

            <div class="form-section">
                <h3>Account Settings</h3>
                
                <div class="form-group">
                    <label for="user_role">User Role</label>
                    <select id="user_role" name="user_role" required>
                        <option value="CUSTOMER" <?= ($user_data['user_role'] === 'CUSTOMER' ? 'selected' : '') ?>>Customer</option>
                        <option value="ADMIN" <?= ($user_data['user_role'] === 'ADMIN' ? 'selected' : '') ?>>Administrator</option>
                    </select>
                    <small class="form-help">Administrators can manage tickets and users</small>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                    <small class="form-help">Leave blank to keep current password (minimum 6 characters)</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                    <small class="form-help">Re-enter the new password to confirm</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-save"></i> Update User
                </button>
                <a href="app.php?view=users" class="btn btn-secondary">
                    <i class="icon-cancel"></i> Cancel
                </a>
                <?php if ($user_data['user_id'] != $_SESSION['user_id']): ?>
                    <a href="actions/delete_user.php?id=<?= $user_data['user_id'] ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete user \'<?= htmlspecialchars($user_data['username']) ?>\'? This action cannot be undone.');">
                        <i class="icon-delete"></i> Delete User
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
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
    echo '<p>The requested user could not be found.</p>';
    echo '<a href="app.php?view=users" class="btn btn-primary">Back to Users</a>';
    echo '</div>';
}
