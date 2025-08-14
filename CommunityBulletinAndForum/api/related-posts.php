<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 10) : 5;
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get the current post's category
    $current_post = $db->selectOne(
        "SELECT category_id FROM posts WHERE id = ?",
        [$post_id],
        'i'
    );
    
    if (!$current_post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    // Get related posts from the same category, excluding the current post
    $related_posts = $db->select("
        SELECT p.id, p.title, p.created_at, p.likes_count, p.comments_count, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.category_id = ? AND p.id != ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ", [$current_post['category_id'], $post_id, $limit], 'iii');
    
    echo json_encode([
        'success' => true,
        'posts' => $related_posts
    ]);
    
} catch (Exception $e) {
    error_log("Related posts API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch related posts'
    ]);
}
?>