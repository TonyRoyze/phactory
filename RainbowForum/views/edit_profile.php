<?php
// This file is views/edit_profile.php

if (!isset($_SESSION['user_id'])) {
    echo '<h1>Access Denied</h1>';
    exit();
}

require 'connector.php';

$user_id = $_SESSION['user_id'];
$user_data = null;

if ($user_id > 0) {
    $sql = "SELECT user_id, user_name FROM user WHERE user_id = ?";
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
    <h1>Edit Your Profile</h1>
    <form action="actions/update_profile.php" method="POST" class="user-form">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['user_id']) ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="user_name" value="<?= htmlspecialchars($user_data['user_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password">
        </div>
        <button type="submit">Update Profile</button>
    </form>
    <?php
} else {
    echo '<h1>User not found.</h1>';
}
