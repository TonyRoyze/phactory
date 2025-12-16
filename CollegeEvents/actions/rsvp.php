<?php
session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO rsvps (event_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();

    // Redirect back to the event view
    header("Location: ../app.php?view=event&id=" . $event_id);
    exit();
}
