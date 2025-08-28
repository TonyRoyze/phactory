<?php
session_start();
require '../connector.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: ../app.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../app.php');
    exit();
}

// Get and validate input
$ticket_id = intval($_POST['ticket_id'] ?? 0);
$assigned_to = intval($_POST['assigned_to'] ?? 0);

if (!$ticket_id) {
    $_SESSION['error'] = 'Invalid ticket ID.';
    header('Location: ../app.php');
    exit();
}

// Validate that the ticket exists
$ticket_check_sql = "SELECT ticket_id, status, assigned_to FROM tickets WHERE ticket_id = ?";
$ticket_stmt = $conn->prepare($ticket_check_sql);
$ticket_stmt->bind_param('i', $ticket_id);
$ticket_stmt->execute();
$ticket_result = $ticket_stmt->get_result();

if ($ticket_result->num_rows === 0) {
    $_SESSION['error'] = 'Ticket not found.';
    header('Location: ../app.php');
    exit();
}

$ticket = $ticket_result->fetch_assoc();

// If assigned_to is 0, we're unassigning the ticket
if ($assigned_to === 0) {
    $assigned_to = null;
    $assignee_name = 'Unassigned';
} else {
    // Validate that the assigned user exists and is an admin
    $user_check_sql = "SELECT user_id, full_name FROM users WHERE user_id = ? AND user_role = 'ADMIN'";
    $user_stmt = $conn->prepare($user_check_sql);
    $user_stmt->bind_param('i', $assigned_to);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid assignee. User must be an admin.';
        header('Location: ../app.php?view=ticket&id=' . $ticket_id);
        exit();
    }

    $assignee = $user_result->fetch_assoc();
    $assignee_name = $assignee['full_name'];
}

// Start transaction for atomic operation
$conn->begin_transaction();

try {
    // Update ticket assignment
    $update_sql = "UPDATE tickets SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP, last_activity = CURRENT_TIMESTAMP WHERE ticket_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $assigned_to, $ticket_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update ticket assignment.');
    }

    // If ticket is being assigned (not unassigned) and status is 'Open', change to 'In Progress'
    if ($assigned_to !== null && $ticket['status'] === 'Open') {
        $status_sql = "UPDATE tickets SET status = 'In Progress' WHERE ticket_id = ?";
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bind_param('i', $ticket_id);
        
        if (!$status_stmt->execute()) {
            throw new Exception('Failed to update ticket status.');
        }
    }

    // Log the assignment change as an internal note
    $current_assignee = 'Unassigned';
    if ($ticket['assigned_to']) {
        $current_assignee_sql = "SELECT full_name FROM users WHERE user_id = ?";
        $current_assignee_stmt = $conn->prepare($current_assignee_sql);
        $current_assignee_stmt->bind_param('i', $ticket['assigned_to']);
        $current_assignee_stmt->execute();
        $current_assignee_result = $current_assignee_stmt->get_result();
        if ($current_assignee_result->num_rows > 0) {
            $current_assignee = $current_assignee_result->fetch_assoc()['full_name'];
        }
    }
    
    $log_message = "Ticket assignment changed from '{$current_assignee}' to '{$assignee_name}' by " . $_SESSION['full_name'];
    
    // Add automatic status change note if applicable
    if ($assigned_to !== null && $ticket['status'] === 'Open') {
        $log_message .= ". Status automatically changed from 'Open' to 'In Progress'.";
    }

    $log_sql = "INSERT INTO ticket_replies (ticket_id, author_id, content, is_internal, created_at) VALUES (?, ?, ?, TRUE, CURRENT_TIMESTAMP)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param('iis', $ticket_id, $_SESSION['user_id'], $log_message);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log assignment change.');
    }

    // Commit transaction
    $conn->commit();

    // Set success message
    if ($assigned_to === null) {
        $_SESSION['success'] = 'Ticket has been unassigned successfully.';
    } else {
        if ($assigned_to == $_SESSION['user_id']) {
            $_SESSION['success'] = "Ticket has been assigned to you successfully.";
        } else {
            $_SESSION['success'] = "Ticket has been assigned to {$assignee_name} successfully.";
        }
        if ($ticket['status'] === 'Open') {
            $_SESSION['success'] .= ' Status changed to In Progress.';
        }
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = 'Assignment failed: ' . $e->getMessage();
}

// Redirect back to ticket view
header('Location: ../app.php?view=ticket&id=' . $ticket_id);
exit();
?>