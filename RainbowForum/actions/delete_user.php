<?php
// This file is actions/delete_user.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN') {
    $user_id = $_GET['id'] ?? 0;
    if ($user_id > 0) {
        $sql = "DELETE FROM user WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}
header("Location: ../app.php?view=users");
exit();
