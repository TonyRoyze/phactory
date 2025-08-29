<?php
// This file is views/users.php

// Check for admin privileges
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN') {
    echo '<h1>Manage Users</h1>';
    
    require 'connector.php';
    $sql = "SELECT user_id, user_name, user_type FROM user";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<div class="table-container">';
        echo '<table class="user-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="table-header-id">ID</th>';
        echo '<th class="table-header-name">Username</th>';
        echo '<th class="table-header-role">Role</th>';
        echo '<th class="table-header-actions">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while($row = $result->fetch_assoc()) {
            echo '<tr class="table-row">';
            echo '<td class="table-cell-id">' . $row['user_id'] . '</td>';
            echo '<td class="table-cell-name">';
            echo '<div class="user-info">';
            echo '<span class="username">' . htmlspecialchars($row['user_name']) . '</span>';
            echo '</div>';
            echo '</td>';
            echo '<td class="table-cell-role">';
            echo '<span class="role-badge role-' . strtolower($row['user_type']) . '">' . htmlspecialchars($row['user_type']) . '</span>';
            echo '</td>';
            echo '<td class="table-cell-actions">';
            echo '<div class="action-buttons">';
            echo '<a href="app.php?view=edit_user&id=' . $row['user_id'] . '" class="btn-action btn-edit">';
            echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            echo '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>';
            echo '<path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>';
            echo '</svg>';
            echo 'Edit';
            echo '</a>';
            echo '<a href="actions/delete_user.php?id=' . $row['user_id'] . '" class="btn-action btn-delete" onclick="return confirm(\'Are you sure you want to delete this user?\');">';
            echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            echo '<polyline points="3,6 5,6 21,6"></polyline>';
            echo '<path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>';
            echo '<line x1="10" y1="11" x2="10" y2="17"></line>';
            echo '<line x1="14" y1="11" x2="14" y2="17"></line>';
            echo '</svg>';
            echo 'Delete';
            echo '</a>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="empty-state">';
        echo '<p>No users found.</p>';
        echo '</div>';
    }

} else {
    echo '<h1>Access Denied</h1>';
}
