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
$new_status = trim($_POST['status'] ?? '');

if (!$ticket_id) {
    $_SESSION['error'] = 'Invalid ticket ID.';
    header('Location: ../app.php');
    exit();
}

// Validate status value
$valid_statuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
if (!in_array($new_status, $valid_statuses)) {
    $_SESSION['error'] = 'Invalid status value.';
    header('Location: ../app.php?view=ticket&id=' . $ticket_id);
    exit();
}

// Get current ticket information
$ticket_sql = "SELECT ticket_id, status, assigned_to FROM tickets WHERE ticket_id = ?";
$ticket_stmt = $conn->prepare($ticket_sql);
$ticket_stmt->bind_param('i', $ticket_id);
$ticket_stmt->execute();
$ticket_result = $ticket_stmt->get_result();

if ($ticket_result->num_rows === 0) {
    $_SESSION['error'] = 'Ticket not found.';
    header('Location: ../app.php');
    exit();
}

$ticket = $ticket_result->fetch_assoc();
$old_status = $ticket['status'];

// Check if status is actually changing
if ($old_status === $new_status) {
    $_SESSION['error'] = 'Ticket is already in the selected status.';
    header('Location: ../app.php?view=ticket&id=' . $ticket_id);
    exit();
}

// Business rule validation: If changing to "In Progress", ticket must be assigned
if ($new_status === 'In Progress' && !$ticket['assigned_to']) {
    $_SESSION['error'] = 'Cannot set status to "In Progress" - ticket must be assigned to an admin first.';
    header('Location: ../app.php?view=ticket&id=' . $ticket_id);
    exit();
}

// Start transaction for atomic operation
$conn->begin_transaction();

try {
    // Update ticket status
    $update_sql = "UPDATE tickets SET status = ?, updated_at = CURRENT_TIMESTAMP, last_activity = CURRENT_TIMESTAMP WHERE ticket_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_status, $ticket_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update ticket status.');
    }

    // Log the status change as an internal note
    $log_message = "Status changed from '{$old_status}' to '{$new_status}' by " . $_SESSION['full_name'];
    
    $log_sql = "INSERT INTO ticket_replies (ticket_id, author_id, content, is_internal, created_at) VALUES (?, ?, ?, TRUE, CURRENT_TIMESTAMP)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param('iis', $ticket_id, $_SESSION['user_id'], $log_message);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log status change.');
    }

    // Commit transaction
    $conn->commit();

    // Set success message with appropriate context
    $success_message = "Ticket status changed from '{$old_status}' to '{$new_status}' successfully.";
    
    // Add contextual information for specific status changes
    if ($new_status === 'Resolved') {
        $success_message .= ' Customer will be notified and can reopen if needed.';
    } elseif ($new_status === 'Closed') {
        $success_message .= ' Ticket is now closed and no further customer replies are allowed.';
    }
    
    $_SESSION['success'] = $success_message;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = 'Status update failed: ' . $e->getMessage();
}

// Redirect back to ticket view
header('Location: ../app.php?view=ticket&id=' . $ticket_id);
exit();
?>