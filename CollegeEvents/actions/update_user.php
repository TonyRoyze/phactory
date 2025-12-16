<?php
// This file is actions/update_user.php

session_start();
require '../connector.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $role, $user_id);
    $stmt->execute();
}
header("Location: ../app.php?view=users");
exit();
