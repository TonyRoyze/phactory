<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 10) : 5;
    
    if ($category_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get recent posts for the category
    $posts = $db->select("
        SELECT p.id, p.title, p.created_at, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.category_id = ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ", [$category_id, $limit], 'ii');
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    error_log("Recent posts API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch recent posts'
    ]);
}
?>