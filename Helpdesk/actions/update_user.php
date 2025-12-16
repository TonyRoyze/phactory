<?php
// This file is actions/update_user.php

session_start();
require '../connector.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: ../app.php?view=users&status=error&message=" . urlencode("Access denied"));
    exit();
}

$user_id = $_POST['user_id'] ?? 0;
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$user_role = $_POST['user_role'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// Validation
$errors = [];

if (empty($full_name)) {
    $errors[] = "Full name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email address is required";
}

if (empty($username) || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, underscores, and hyphens";
}

if (!in_array($user_role, ['CUSTOMER', 'ADMIN'])) {
    $errors[] = "Invalid user role";
}

// Check if username or email already exists (excluding current user)
$check_sql = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ssi", $username, $email, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $errors[] = "Username or email already exists";
}

// Validate password if provided
if (!empty($new_password) && strlen($new_password) < 6) {
    $errors[] = "Password must be at least 6 characters long";
}

// If there are validation errors, redirect back with error message
if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    header("Location: ../app.php?view=edit_user&id={$user_id}&status=error&message=" . urlencode($error_message));
    exit();
}

// Build update query
$sql = "UPDATE users SET full_name = ?, email = ?, username = ?, user_role = ?";
$types = "ssss";
$params = [$full_name, $email, $username, $user_role];

// Add password to update if provided
if (!empty($new_password)) {
    $sql .= ", password = ?";
    $types .= "s";
    $params[] = $new_password;
}

$sql .= ", updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
$types .= "i";
$params[] = $user_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    header("Location: ../app.php?view=edit_user&id={$user_id}&status=success");
} else {
    header("Location: ../app.php?view=edit_user&id={$user_id}&status=error&message=" . urlencode("Database error occurred"));
}

exit();
