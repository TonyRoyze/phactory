<?php
// This file is actions/delete_user.php

session_start();
require '../connector.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: ../app.php?view=users&status=error&message=" . urlencode("Access denied"));
    exit();
}

$user_id = $_GET['id'] ?? 0;

if ($user_id <= 0) {
    header("Location: ../app.php?view=users&status=error&message=" . urlencode("Invalid user ID"));
    exit();
}

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    header("Location: ../app.php?view=users&status=error&message=" . urlencode("You cannot delete your own account"));
    exit();
}

// Check if user exists and get their information
$check_sql = "SELECT username, user_role FROM users WHERE user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header("Location: ../app.php?view=users&status=error&message=" . urlencode("User not found"));
    exit();
}

$user_data = $check_result->fetch_assoc();

// Start transaction for data integrity
$conn->begin_transaction();

try {
    // Delete related data first (if any)
    // Delete user's tickets (reassign to null or delete based on business rules)
    $update_tickets_sql = "UPDATE tickets SET assigned_to = NULL WHERE assigned_to = ?";
    $update_tickets_stmt = $conn->prepare($update_tickets_sql);
    $update_tickets_stmt->bind_param("i", $user_id);
    $update_tickets_stmt->execute();
    
    // Update tickets created by this user to show they were created by a deleted user
    $update_customer_tickets_sql = "UPDATE tickets SET customer_id = NULL WHERE customer_id = ?";
    $update_customer_tickets_stmt = $conn->prepare($update_customer_tickets_sql);
    $update_customer_tickets_stmt->bind_param("i", $user_id);
    $update_customer_tickets_stmt->execute();
    
    // Delete user's replies (or mark as deleted)
    $delete_replies_sql = "DELETE FROM ticket_replies WHERE author_id = ?";
    $delete_replies_stmt = $conn->prepare($delete_replies_sql);
    $delete_replies_stmt->bind_param("i", $user_id);
    $delete_replies_stmt->execute();
    
    // Delete user's file attachments
    $delete_attachments_sql = "DELETE FROM attachments WHERE uploaded_by = ?";
    $delete_attachments_stmt = $conn->prepare($delete_attachments_sql);
    $delete_attachments_stmt->bind_param("i", $user_id);
    $delete_attachments_stmt->execute();
    
    // Finally, delete the user
    $delete_user_sql = "DELETE FROM users WHERE user_id = ?";
    $delete_user_stmt = $conn->prepare($delete_user_sql);
    $delete_user_stmt->bind_param("i", $user_id);
    $delete_user_stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    header("Location: ../app.php?view=users&status=success&message=" . urlencode("User '{$user_data['username']}' has been deleted successfully"));
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    header("Location: ../app.php?view=users&status=error&message=" . urlencode("Error deleting user: " . $e->getMessage()));
}

exit();
