<?php
// This file is views/users.php

// Check for admin privileges
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN') {
    echo '<h1>Manage Users</h1>';
    
    require 'connector.php';
    $sql = "SELECT user_id, user_name, user_type FROM user";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<table class="user-table">';
        echo '<thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['user_id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['user_type']) . '</td>';
            echo '<td>';
            echo '<a href="app.php?view=edit_user&id=' . $row['user_id'] . '" class="btn-action">Edit</a> ';
            echo '<a href="actions/delete_user.php?id=' . $row['user_id'] . '" class="btn-action btn-delete" onclick="return confirm(\'Are you sure you want to delete this user?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No users found.</p>';
    }

} else {
    echo '<h1>Access Denied</h1>';
}
