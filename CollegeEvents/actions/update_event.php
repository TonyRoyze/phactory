<?php
// This file is actions/update_event.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $event_id = $_POST['event_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $date = $_POST['date'];

    // Check if user is author or admin
    $sql_check = "SELECT user_id FROM events WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $event_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows == 1) {
        $event_data = $result_check->fetch_assoc();
        if ($event_data['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'ADMIN') {
            $sql = "UPDATE events SET title = ?, description = ?, location = ?, date = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $title, $description, $location, $date, $event_id);
            $stmt->execute();
        }
    }
}
header("Location: ../app.php?view=event&id=" . $event_id);
exit();
