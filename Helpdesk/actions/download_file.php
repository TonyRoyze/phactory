<?php
// This file is actions/download_file.php - Handle secure file downloads

session_start();
require '../connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get attachment ID
$attachment_id = intval($_GET['id'] ?? 0);

if (!$attachment_id) {
    http_response_code(404);
    echo "File not found.";
    exit();
}

// Get attachment details and verify access permissions
$sql = "SELECT a.*, t.customer_id 
        FROM attachments a
        LEFT JOIN tickets t ON a.ticket_id = t.ticket_id
        WHERE a.attachment_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $attachment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo "File not found.";
    exit();
}

$attachment = $result->fetch_assoc();

// Check access permissions
$can_download = false;
if ($_SESSION['user_role'] === 'ADMIN') {
    $can_download = true; // Admins can download all files
} elseif ($_SESSION['user_role'] === 'CUSTOMER' && $attachment['customer_id'] == $_SESSION['user_id']) {
    $can_download = true; // Customers can only download files from their own tickets
}

if (!$can_download) {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

// Build file path
$file_path = '../uploads/' . $attachment['filename'];

// Check if file exists
if (!file_exists($file_path)) {
    http_response_code(404);
    echo "File not found on server.";
    exit();
}

// Set headers for file download
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
header('Content-Length: ' . $attachment['file_size']);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file content
readfile($file_path);
exit();
?>