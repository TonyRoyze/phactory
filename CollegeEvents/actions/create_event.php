<?php
// This file is actions/create_event.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $date = $_POST['date'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO events (title, description, location, date, user_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $title, $description, $location, $date, $user_id);
    $stmt->execute();

    // Redirect to the main events view
    header("Location: ../app.php?view=events");
    exit();
}
