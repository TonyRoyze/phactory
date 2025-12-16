<?php
// This file is actions/delete_event.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $event_id = $_GET['id'] ?? 0;
    if ($event_id > 0) {
        // Check if user is author or admin
        $sql_check = "SELECT user_id FROM events WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $event_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows == 1) {
            $event_data = $result_check->fetch_assoc();
            if ($event_data['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'ADMIN') {
                // Delete comments
                $sql_delete_comments = "DELETE FROM comments WHERE event_id = ?";
                $stmt_delete_comments = $conn->prepare($sql_delete_comments);
                $stmt_delete_comments->bind_param("i", $event_id);
                $stmt_delete_comments->execute();

                // Delete RSVPs
                $sql_delete_rsvps = "DELETE FROM rsvps WHERE event_id = ?";
                $stmt_delete_rsvps = $conn->prepare($sql_delete_rsvps);
                $stmt_delete_rsvps->bind_param("i", $event_id);
                $stmt_delete_rsvps->execute();

                // Delete event
                $sql = "DELETE FROM events WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $event_id);
                $stmt->execute();
            }
        }
    }
}
header("Location: ../app.php?view=events");
exit();
