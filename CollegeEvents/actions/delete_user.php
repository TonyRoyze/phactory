<?php
// This file is actions/delete_user.php

session_start();
require '../connector.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
    $user_id = $_GET['id'] ?? 0;
    if ($user_id > 0) {
        // Delete user's comments
        $sql_delete_comments = "DELETE FROM comments WHERE user_id = ?";
        $stmt_delete_comments = $conn->prepare($sql_delete_comments);
        $stmt_delete_comments->bind_param("i", $user_id);
        $stmt_delete_comments->execute();

        // Delete user's RSVPs
        $sql_delete_rsvps = "DELETE FROM rsvps WHERE user_id = ?";
        $stmt_delete_rsvps = $conn->prepare($sql_delete_rsvps);
        $stmt_delete_rsvps->bind_param("i", $user_id);
        $stmt_delete_rsvps->execute();

        // Delete user's events
        $sql_delete_events = "DELETE FROM events WHERE user_id = ?";
        $stmt_delete_events = $conn->prepare($sql_delete_events);
        $stmt_delete_events->bind_param("i", $user_id);
        $stmt_delete_events->execute();

        // Delete user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}
header("Location: ../app.php?view=users");
exit();
