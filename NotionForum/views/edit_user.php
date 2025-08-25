<?php
// This file is views/edit_user.php

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    echo '<h1>Access Denied</h1>';
    exit(); // Use exit() instead of break for included files
}
require 'connector.php';
$user_id = $_GET['id'] ?? 0;
$user_data = null;

if ($user_id > 0) {
    $sql = "SELECT user_id, user_name, user_type FROM user WHERE user_id = ?";
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
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['user_id']) ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="user_name" value="<?= htmlspecialchars($user_data['user_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="user_type">User Type</label>
            <select id="user_type" name="user_type">
                <option value="ADMIN" <?= ($user_data['user_type'] === 'ADMIN' ? 'selected' : '') ?>>ADMIN</option>
                <option value="MEMBER" <?= ($user_data['user_type'] === 'MEMBER' ? 'selected' : '') ?>>MEMBER</option>
            </select>
        </div>
        <button type="submit">Update User</button>
    </form>
    <?php
} else {
    echo '<h1>User not found.</h1>';
}
