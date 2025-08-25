<?php
session_start();
require '../connector.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'CUSTOMER') {
    header("Location: ../login.php");
    exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../app.php?view=create_ticket");
    exit();
}

// Sanitize and validate input data
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$priority = trim($_POST['priority'] ?? '');
$description = trim($_POST['description'] ?? '');
$customer_id = $_SESSION['user_id'];

// Validation
$errors = [];

// Validate title
if (empty($title)) {
    $errors[] = 'Title is required';
} elseif (strlen($title) < 5) {
    $errors[] = 'Title must be at least 5 characters long';
} elseif (strlen($title) > 255) {
    $errors[] = 'Title must be less than 255 characters';
}

// Validate category
$valid_categories = ['Technical', 'Billing', 'General'];
if (empty($category) || !in_array($category, $valid_categories)) {
    $errors[] = 'Please select a valid category';
}

// Validate priority
$valid_priorities = ['Low', 'Medium', 'High', 'Urgent'];
if (empty($priority) || !in_array($priority, $valid_priorities)) {
    $errors[] = 'Please select a valid priority level';
}

// Validate description
if (empty($description)) {
    $errors[] = 'Description is required';
} elseif (strlen($description) < 10) {
    $errors[] = 'Description must be at least 10 characters long';
}

// If there are validation errors, redirect back with error
if (!empty($errors)) {
    header("Location: ../app.php?view=create_ticket&error=validation");
    exit();
}

// Handle file uploads
$uploaded_files = [];
if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
    $upload_dir = '../uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $max_file_size = 5 * 1024 * 1024; // 5MB
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    $file_count = count($_FILES['attachments']['name']);
    
    // Validate file count
    if ($file_count > 5) {
        header("Location: ../app.php?view=create_ticket&error=upload");
        exit();
    }
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['attachments']['tmp_name'][$i];
            $file_name = $_FILES['attachments']['name'][$i];
            $file_size = $_FILES['attachments']['size'][$i];
            $file_type = $_FILES['attachments']['type'][$i];
            
            // Validate file size
            if ($file_size > $max_file_size) {
                header("Location: ../app.php?view=create_ticket&error=upload");
                exit();
            }
            
            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                header("Location: ../app.php?view=create_ticket&error=upload");
                exit();
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = uniqid('ticket_', true) . '.' . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $file_path)) {
                $uploaded_files[] = [
                    'filename' => $unique_filename,
                    'original_filename' => $file_name,
                    'file_size' => $file_size,
                    'mime_type' => $file_type
                ];
            } else {
                // Clean up any previously uploaded files on error
                foreach ($uploaded_files as $uploaded_file) {
                    unlink($upload_dir . $uploaded_file['filename']);
                }
                header("Location: ../app.php?view=create_ticket&error=upload");
                exit();
            }
        }
    }
}

// Begin database transaction
mysqli_begin_transaction($conn);

try {
    // Insert ticket into database
    $stmt = mysqli_prepare($conn, "INSERT INTO tickets (title, description, category, priority, customer_id, status, created_at, updated_at, last_activity) VALUES (?, ?, ?, ?, ?, 'Open', NOW(), NOW(), NOW())");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $category, $priority, $customer_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $ticket_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Insert file attachments if any
    if (!empty($uploaded_files)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO attachments (ticket_id, filename, original_filename, file_size, mime_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            throw new Exception("Prepare failed for attachments: " . mysqli_error($conn));
        }
        
        foreach ($uploaded_files as $file) {
            mysqli_stmt_bind_param($stmt, "issisi", 
                $ticket_id, 
                $file['filename'], 
                $file['original_filename'], 
                $file['file_size'], 
                $file['mime_type'], 
                $customer_id
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Execute failed for attachment: " . mysqli_stmt_error($stmt));
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect to success page
    header("Location: ../app.php?view=my_tickets&success=1&ticket_id=" . $ticket_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    // Clean up uploaded files on database error
    foreach ($uploaded_files as $uploaded_file) {
        $file_path = $upload_dir . $uploaded_file['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Log error (in production, use proper logging)
    error_log("Ticket creation error: " . $e->getMessage());
    
    // Redirect with error
    header("Location: ../app.php?view=create_ticket&error=database");
    exit();
}

mysqli_close($conn);
?>