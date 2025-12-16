<?php
session_start();
require_once '../connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Configuration
$maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
$allowedMimeTypes = [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
];

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];

// Validate required parameters
if (!isset($_POST['ticket_id']) || !is_numeric($_POST['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

$ticketId = intval($_POST['ticket_id']);
$replyId = isset($_POST['reply_id']) && is_numeric($_POST['reply_id']) ? intval($_POST['reply_id']) : null;
$userId = $_SESSION['user_id'];

// Verify user has access to this ticket
$ticketCheckSql = "SELECT t.ticket_id, t.customer_id, u.user_role 
                   FROM tickets t 
                   JOIN users u ON u.user_id = ? 
                   WHERE t.ticket_id = ?";
$stmt = $conn->prepare($ticketCheckSql);
$stmt->bind_param("ii", $userId, $ticketId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

$ticketData = $result->fetch_assoc();

// Check if user has permission (customer can only access their own tickets, admins can access all)
if ($ticketData['user_role'] !== 'ADMIN' && $ticketData['customer_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'File upload failed';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'File is too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'File upload was interrupted';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No file was uploaded';
                break;
            default:
                $errorMessage = 'Unknown upload error';
        }
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit();
}

$uploadedFile = $_FILES['file'];
$originalFilename = $uploadedFile['name'];
$fileSize = $uploadedFile['size'];
$tempPath = $uploadedFile['tmp_name'];

// Validate file size
if ($fileSize > $maxFileSize) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit();
}

// Validate file extension
$fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions)]);
    exit();
}

// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tempPath);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type detected']);
    exit();
}

// Generate unique filename to prevent conflicts and directory traversal
$uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
$uploadPath = '../uploads/' . $uniqueFilename;

// Create uploads directory if it doesn't exist
if (!is_dir('../uploads')) {
    mkdir('../uploads', 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($tempPath, $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit();
}

// Insert attachment record into database
$insertSql = "INSERT INTO attachments (ticket_id, reply_id, filename, original_filename, file_size, mime_type, uploaded_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("iissisi", $ticketId, $replyId, $uniqueFilename, $originalFilename, $fileSize, $mimeType, $userId);

if ($stmt->execute()) {
    $attachmentId = $conn->insert_id;
    
    // Update ticket's last activity
    $updateTicketSql = "UPDATE tickets SET last_activity = CURRENT_TIMESTAMP WHERE ticket_id = ?";
    $updateStmt = $conn->prepare($updateTicketSql);
    $updateStmt->bind_param("i", $ticketId);
    $updateStmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'File uploaded successfully',
        'attachment_id' => $attachmentId,
        'filename' => $originalFilename,
        'file_size' => $fileSize
    ]);
} else {
    // If database insert fails, remove the uploaded file
    unlink($uploadPath);
    echo json_encode(['success' => false, 'message' => 'Failed to save attachment information']);
}

$conn->close();
?>