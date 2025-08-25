<?php
// This file is actions/update_user.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN') {
    $user_id = $_POST['user_id'];
    $user_name = $_POST['user_name'];
    $user_type = $_POST['user_type'];

    $sql = "UPDATE user SET user_name = ?, user_type = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $user_name, $user_type, $user_id);
    $stmt->execute();
}
header("Location: ../app.php?view=users");
exit();
