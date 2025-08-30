<?php
// This file is views/edit_user.php

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo '<h1>Access Denied</h1>';
    exit(); // Use exit() instead of break for included files
}
require 'connector.php';
$user_id = $_GET['id'] ?? 0;
$user_data = null;

if ($user_id > 0) {
    $sql = "SELECT id, username, role FROM users WHERE id = ?";
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
    <h1>Edit User</h1>
    <form action="actions/update_user.php" method="POST" class="user-form">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['id']) ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_data['username']) ?>" required>
        </div>
        <div class="form-group">
            <label for="role">User Role</label>
            <select id="role" name="role">
                <option value="ADMIN" <?= ($user_data['role'] === 'ADMIN' ? 'selected' : '') ?>>ADMIN</option>
                <option value="USER" <?= ($user_data['role'] === 'USER' ? 'selected' : '') ?>>USER</option>
            </select>
        </div>
        <button type="submit">Update User</button>
    </form>
    <?php
} else {
    echo '<h1>User not found.</h1>';
}
