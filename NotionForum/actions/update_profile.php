<?php
// This file is actions/update_profile.php

session_start();
require '../connector.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_POST['user_name'];
$password = $_POST['password'];

// Start building the SQL query
$sql = "UPDATE user SET user_name = ? ";
$types = "s";
$params = [$user_name];

// Add password to update if provided
if (!empty($password)) {
    $sql .= ", pass = ? ";
    $types .= "s";
    $params[] = $password;
}

$sql .= " WHERE user_id = ?";
$types .= "i";
$params[] = $user_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

// Update session username if it changed
$_SESSION['user_name'] = $user_name;

header("Location: ../app.php?view=edit_profile&status=success");
exit();
