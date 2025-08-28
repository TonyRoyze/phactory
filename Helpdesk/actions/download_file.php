<?php
session_start();
require_once '../connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized access');
}

// Check if attachment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Invalid attachment ID');
}

$attachmentId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// Get attachment information and verify access
$sql = "SELECT a.*, t.customer_id, u.user_role 
        FROM attachments a 
        JOIN tickets t ON a.ticket_id = t.ticket_id 
        JOIN users u ON u.user_id = ? 
        WHERE a.attachment_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $attachmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die('Attachment not found');
}

$attachment = $result->fetch_assoc();

// Check if user has permission to download this file
// Customers can only download files from their own tickets, admins can download any file
if ($attachment['user_role'] !== 'ADMIN' && $attachment['customer_id'] != $userId) {
    http_response_code(403);
    die('Access denied');
}

// Build file path
$filePath = '../uploads/' . $attachment['filename'];

// Check if file exists on disk
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found on server');
}

// Verify file integrity (optional security check)
if (filesize($filePath) !== intval($attachment['file_size'])) {
    http_response_code(500);
    die('File integrity check failed');
}

// Set appropriate headers for file download
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
header('Content-Length: ' . $attachment['file_size']);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Prevent any output before file content
ob_clean();
flush();

// Output file content
readfile($filePath);

$conn->close();
exit();
?>