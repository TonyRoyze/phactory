<?php
// This file is actions/add_comment.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $comment = $_POST['comment'];
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO comments (comment, event_id, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $comment, $event_id, $user_id);
    $stmt->execute();

    // Redirect back to the event view
    header("Location: ../app.php?view=event&id=" . $event_id);
    exit();
}
