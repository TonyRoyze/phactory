<?php
// This file is actions/search_autocomplete.php - Auto-complete search suggestions

session_start();
require '../connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['suggestions' => []]);
    exit();
}

// Get search term
$search_term = trim($_GET['q'] ?? '');

// Validate search term
if (empty($search_term) || strlen($search_term) < 2) {
    echo json_encode(['suggestions' => []]);
    exit();
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Role-based access control
$role_condition = $user_role === 'CUSTOMER' ? 'AND t.customer_id = ?' : '';
$role_params = $user_role === 'CUSTOMER' ? [$user_id] : [];
$role_types = $user_role === 'CUSTOMER' ? 'i' : '';

$suggestions = [];
$search_param = "%{$search_term}%";

// Get ticket title suggestions
$title_query = "SELECT DISTINCT t.title, t.ticket_id, t.category, t.status
                FROM tickets t 
                WHERE t.title LIKE ? {$role_condition}
                ORDER BY t.last_activity DESC 
                LIMIT 5";

$title_params = array_merge([$search_param], $role_params);
$title_types = 's' . $role_types;

$title_stmt = $conn->prepare($title_query);
if (!empty($title_params)) {
    $title_stmt->bind_param($title_types, ...$title_params);
}
$title_stmt->execute();
$title_result = $title_stmt->get_result();

while ($row = $title_result->fetch_assoc()) {
    $suggestions[] = [
        'type' => 'ticket',
        'value' => $row['title'],
        'label' => $row['title'],
        'ticket_id' => $row['ticket_id'],
        'category' => $row['category'],
        'status' => $row['status'],
        'icon' => '🎫'
    ];
}

// Get customer name suggestions (admin only)
if ($user_role === 'ADMIN') {
    $customer_query = "SELECT DISTINCT u.full_name, u.email, u.user_id
                       FROM users u 
                       INNER JOIN tickets t ON u.user_id = t.customer_id
                       WHERE u.user_role = 'CUSTOMER' 
                       AND (u.full_name LIKE ? OR u.email LIKE ?)
                       ORDER BY u.full_name 
                       LIMIT 3";
    
    $customer_stmt = $conn->prepare($customer_query);
    $customer_stmt->bind_param('ss', $search_param, $search_param);
    $customer_stmt->execute();
    $customer_result = $customer_stmt->get_result();
    
    while ($row = $customer_result->fetch_assoc()) {
        $suggestions[] = [
            'type' => 'customer',
            'value' => $row['full_name'],
            'label' => $row['full_name'] . ' (' . $row['email'] . ')',
            'customer_id' => $row['user_id'],
            'email' => $row['email'],
            'icon' => '👤'
        ];
    }
}

// Get category suggestions
$categories = ['Technical', 'Billing', 'General'];
foreach ($categories as $category) {
    if (stripos($category, $search_term) !== false) {
        $suggestions[] = [
            'type' => 'category',
            'value' => $category,
            'label' => $category . ' tickets',
            'category' => $category,
            'icon' => '📁'
        ];
    }
}

// Get status suggestions
$statuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
foreach ($statuses as $status) {
    if (stripos($status, $search_term) !== false) {
        $suggestions[] = [
            'type' => 'status',
            'value' => $status,
            'label' => $status . ' tickets',
            'status' => $status,
            'icon' => '🏷️'
        ];
    }
}

// Get priority suggestions
$priorities = ['Low', 'Medium', 'High', 'Urgent'];
foreach ($priorities as $priority) {
    if (stripos($priority, $search_term) !== false) {
        $suggestions[] = [
            'type' => 'priority',
            'value' => $priority,
            'label' => $priority . ' priority tickets',
            'priority' => $priority,
            'icon' => '⚡'
        ];
    }
}

// Get common keywords from ticket descriptions and replies
$keyword_query = "SELECT t.description 
                  FROM tickets t 
                  WHERE t.description LIKE ? {$role_condition}
                  ORDER BY t.last_activity DESC 
                  LIMIT 10";

$keyword_stmt = $conn->prepare($keyword_query);
if (!empty($title_params)) {
    $keyword_stmt->bind_param($title_types, ...$title_params);
}
$keyword_stmt->execute();
$keyword_result = $keyword_stmt->get_result();

$keywords = [];
while ($row = $keyword_result->fetch_assoc()) {
    // Extract words that contain the search term
    $words = str_word_count(strtolower($row['description']), 1);
    foreach ($words as $word) {
        if (strlen($word) > 3 && stripos($word, $search_term) !== false && !in_array($word, $keywords)) {
            $keywords[] = $word;
            if (count($keywords) >= 3) break 2; // Limit to 3 keywords
        }
    }
}

foreach ($keywords as $keyword) {
    $suggestions[] = [
        'type' => 'keyword',
        'value' => $keyword,
        'label' => 'Search for "' . $keyword . '"',
        'keyword' => $keyword,
        'icon' => '🔍'
    ];
}

// Limit total suggestions
$suggestions = array_slice($suggestions, 0, 10);

header('Content-Type: application/json');
echo json_encode(['suggestions' => $suggestions]);
?>