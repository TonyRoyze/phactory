<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 10;
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'results' => []]);
        exit;
    }
    
    $db = Database::getInstance();
    $search_term = '%' . $query . '%';
    
    // Search posts with full-text search and fallback to LIKE
    $posts = $db->select("
        SELECT p.id, p.title, p.content, p.excerpt, p.created_at, 
               u.username, u.avatar,
               c.name as category_name, c.color as category_color,
               p.likes_count, p.comments_count,
               MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE (p.title LIKE ? OR p.content LIKE ? OR MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE))
        ORDER BY relevance_score DESC, p.created_at DESC
        LIMIT ?
    ", [$query, $search_term, $search_term, $query, $limit], 'ssssi');
    
    // Search users (if query is longer)
    $users = [];
    if (strlen($query) >= 3) {
        $remaining_limit = max(0, $limit - count($posts));
        if ($remaining_limit > 0) {
            $users = $db->select("
                SELECT u.id, u.username, u.bio, u.created_at,
                       COUNT(p.id) as post_count
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id
                WHERE u.username LIKE ? OR u.bio LIKE ?
                GROUP BY u.id, u.username, u.bio, u.created_at
                ORDER BY u.username ASC
                LIMIT ?
            ", [$search_term, $search_term, $remaining_limit], 'ssi');
        }
    }
    
    // Format results
    $formatted_posts = array_map(function($post) {
        return [
            'type' => 'post',
            'id' => $post['id'],
            'title' => $post['title'],
            'excerpt' => $post['excerpt'] ?: createExcerpt($post['content']),
            'username' => $post['username'],
            'category' => $post['category_name'],
            'category_color' => $post['category_color'],
            'created_at' => $post['created_at'],
            'likes_count' => $post['likes_count'],
            'comments_count' => $post['comments_count'],
            'url' => 'post.php?id=' . $post['id'],
            'relevance_score' => $post['relevance_score'] ?? 0
        ];
    }, $posts);
    
    $formatted_users = array_map(function($user) {
        return [
            'type' => 'user',
            'id' => $user['id'],
            'username' => $user['username'],
            'bio' => $user['bio'],
            'created_at' => $user['created_at'],
            'post_count' => $user['post_count'],
            'url' => 'profile.php?id=' . $user['id']
        ];
    }, $users);
    
    $results = array_merge($formatted_posts, $formatted_users);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => htmlspecialchars($query),
        'total' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Search failed'
    ]);
}
?>