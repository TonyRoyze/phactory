<?php
// This file is actions/update_profile.php

session_start();
require '../connector.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$username = trim($_POST['username']);
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// Validation
$errors = [];

// Validate required fields
if (empty($full_name)) {
    $errors[] = "Full name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email address is required";
}

if (empty($username) || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, underscores, and hyphens";
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

// If changing password, validate current password
if (!empty($new_password)) {
    if (empty($current_password)) {
        $errors[] = "Current password is required to set a new password";
    } else {
        // Verify current password
        $verify_sql = "SELECT password FROM users WHERE user_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 1) {
            $user_row = $verify_result->fetch_assoc();
            // Note: In a real application, you should use password_verify() with hashed passwords
            // For this demo, we're assuming plain text passwords as per the existing system
            if ($user_row['password'] !== $current_password) {
                $errors[] = "Current password is incorrect";
            }
        }
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long";
    }
}

// If there are validation errors, redirect back with error message
if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    header("Location: ../app.php?view=edit_profile&status=error&message=" . urlencode($error_message));
    exit();
}

// Start building the SQL query
$sql = "UPDATE users SET full_name = ?, email = ?, username = ?";
$types = "sss";
$params = [$full_name, $email, $username];

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
    // Update session variables
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    
    header("Location: ../app.php?view=edit_profile&status=success");
} else {
    header("Location: ../app.php?view=edit_profile&status=error&message=" . urlencode("Database error occurred"));
}

exit();
