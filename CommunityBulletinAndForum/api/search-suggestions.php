<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 10) : 8;
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        exit;
    }
    
    $suggestions = getSearchSuggestions($query, $limit);
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'query' => htmlspecialchars($query)
    ]);
    
} catch (Exception $e) {
    error_log("Search suggestions API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch suggestions'
    ]);
}
?>