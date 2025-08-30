<?php
// This file is actions/update_profile.php

session_start();
require '../connector.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

// Start building the SQL query
$sql = "UPDATE users SET username = ?, email = ? ";
$types = "ss";
$params = [$username, $email];

// Add password to update if provided
if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql .= ", password = ? ";
    $types .= "s";
    $params[] = $hashed_password;
}

$sql .= " WHERE id = ?";
$types .= "i";
$params[] = $user_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

// Update session username if it changed
$_SESSION['username'] = $username;

header("Location: ../app.php?view=edit_profile&status=success");
exit();
