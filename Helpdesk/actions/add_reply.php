<?php
// This file is actions/add_reply.php - Handle ticket reply submissions

session_start();
require '../connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../app.php");
    exit();
}

// Get and validate form data
$ticket_id = intval($_POST['ticket_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$is_internal = ($_SESSION['user_role'] === 'ADMIN' && isset($_POST['is_internal'])) ? 1 : 0;

// Validate required fields
if (!$ticket_id || empty($content)) {
    $_SESSION['error'] = "Ticket ID and reply content are required.";
    header("Location: ../app.php?view=ticket&id=" . $ticket_id);
    exit();
}

// Verify ticket exists and user has permission to reply
$ticket_sql = "SELECT ticket_id, customer_id, status FROM tickets WHERE ticket_id = ?";
$ticket_stmt = $conn->prepare($ticket_sql);
$ticket_stmt->bind_param('i', $ticket_id);
$ticket_stmt->execute();
$ticket_result = $ticket_stmt->get_result();

if ($ticket_result->num_rows === 0) {
    $_SESSION['error'] = "Ticket not found.";
    header("Location: ../app.php");
    exit();
}

$ticket = $ticket_result->fetch_assoc();

// Check permissions
$can_reply = false;
if ($_SESSION['user_role'] === 'ADMIN') {
    $can_reply = true; // Admins can reply to any ticket
} elseif ($_SESSION['user_role'] === 'CUSTOMER' && $ticket['customer_id'] == $_SESSION['user_id']) {
    $can_reply = true; // Customers can only reply to their own tickets
}

if (!$can_reply) {
    $_SESSION['error'] = "You do not have permission to reply to this ticket.";
    header("Location: ../app.php");
    exit();
}

// Check if ticket is closed
if ($ticket['status'] === 'Closed') {
    $_SESSION['error'] = "Cannot reply to a closed ticket.";
    header("Location: ../app.php?view=ticket&id=" . $ticket_id);
    exit();
}

// Handle file upload if present
$attachment_id = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_result = handleFileUpload($_FILES['attachment'], $ticket_id, null);
    if ($upload_result['success']) {
        $attachment_id = $upload_result['attachment_id'];
    } else {
        $_SESSION['error'] = $upload_result['error'];
        header("Location: ../app.php?view=ticket&id=" . $ticket_id);
        exit();
    }
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Insert the reply
    $reply_sql = "INSERT INTO ticket_replies (ticket_id, author_id, content, is_internal, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
    $reply_stmt = $conn->prepare($reply_sql);
    $reply_stmt->bind_param('iisi', $ticket_id, $_SESSION['user_id'], $content, $is_internal);
    
    if (!$reply_stmt->execute()) {
        throw new Exception("Failed to insert reply");
    }
    
    $reply_id = $conn->insert_id;
    
    // Update attachment with reply_id if file was uploaded
    if ($attachment_id) {
        $update_attachment_sql = "UPDATE attachments SET reply_id = ? WHERE attachment_id = ?";
        $update_attachment_stmt = $conn->prepare($update_attachment_sql);
        $update_attachment_stmt->bind_param('ii', $reply_id, $attachment_id);
        $update_attachment_stmt->execute();
    }
    
    // Update ticket's last_activity timestamp
    $update_ticket_sql = "UPDATE tickets SET last_activity = NOW()";
    
    // Handle status changes based on business rules
    $new_status = null;
    if ($_SESSION['user_role'] === 'CUSTOMER') {
        // If customer replies to a resolved ticket, reopen it
        if ($ticket['status'] === 'Resolved') {
            $new_status = 'Open';
            $update_ticket_sql = "UPDATE tickets SET last_activity = NOW(), status = 'Open'";
        }
    } elseif ($_SESSION['user_role'] === 'ADMIN' && !$is_internal) {
        // If admin makes a public reply to an open ticket, mark as in progress
        if ($ticket['status'] === 'Open') {
            $new_status = 'In Progress';
            $update_ticket_sql = "UPDATE tickets SET last_activity = NOW(), status = 'In Progress'";
        }
    }
    
    $update_ticket_sql .= " WHERE ticket_id = ?";
    $update_ticket_stmt = $conn->prepare($update_ticket_sql);
    $update_ticket_stmt->bind_param('i', $ticket_id);
    
    if (!$update_ticket_stmt->execute()) {
        throw new Exception("Failed to update ticket");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Set success message
    $_SESSION['success'] = "Reply added successfully.";
    if ($new_status) {
        $_SESSION['success'] .= " Ticket status updated to " . $new_status . ".";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Failed to add reply. Please try again.";
}

// Redirect back to ticket
header("Location: ../app.php?view=ticket&id=" . $ticket_id);
exit();

/**
 * Handle file upload with validation and security checks
 */
function handleFileUpload($file, $ticket_id, $reply_id) {
    global $conn;
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed.'];
    }
    
    // Check file size (5MB limit)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
    }
    
    // Get file info
    $original_filename = $file['name'];
    $file_size = $file['size'];
    $temp_path = $file['tmp_name'];
    
    // Validate file type
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $temp_path);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'File type not allowed. Please upload images, PDF, Word documents, or text files only.'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $unique_filename = uniqid('attachment_') . '.' . $file_extension;
    
    // Ensure uploads directory exists
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $destination_path = $upload_dir . $unique_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($temp_path, $destination_path)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }
    
    // Insert attachment record
    $attachment_sql = "INSERT INTO attachments (ticket_id, reply_id, filename, original_filename, file_size, mime_type, uploaded_by, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $attachment_stmt = $conn->prepare($attachment_sql);
    $attachment_stmt->bind_param('iissisi', $ticket_id, $reply_id, $unique_filename, $original_filename, $file_size, $mime_type, $_SESSION['user_id']);
    
    if (!$attachment_stmt->execute()) {
        // Clean up uploaded file if database insert fails
        unlink($destination_path);
        return ['success' => false, 'error' => 'Failed to save attachment information.'];
    }
    
    return ['success' => true, 'attachment_id' => $conn->insert_id];
}
?>