<?php
// This file is actions/search_tickets.php - Advanced search functionality

session_start();
require '../connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search parameters
$search_term = trim($_POST['search'] ?? $_GET['search'] ?? '');
$search_type = $_POST['search_type'] ?? $_GET['search_type'] ?? 'all'; // all, titles, descriptions, replies
$category = $_POST['category'] ?? $_GET['category'] ?? '';
$priority = $_POST['priority'] ?? $_GET['priority'] ?? '';
$status = $_POST['status'] ?? $_GET['status'] ?? '';
$date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? '';
$assigned_to = $_POST['assigned_to'] ?? $_GET['assigned_to'] ?? '';
$customer_id = $_POST['customer_id'] ?? $_GET['customer_id'] ?? '';
$limit = min(50, max(1, intval($_POST['limit'] ?? $_GET['limit'] ?? 20)));
$offset = max(0, intval($_POST['offset'] ?? $_GET['offset'] ?? 0));

// Validate search term
if (empty($search_term) || strlen($search_term) < 2) {
    echo json_encode([
        'error' => 'Search term must be at least 2 characters long',
        'results' => [],
        'total' => 0,
        'suggestions' => []
    ]);
    exit();
}

// Build search query based on user role and search type
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Base query components
$select_fields = [
    't.ticket_id',
    't.title',
    't.description',
    't.category',
    't.priority',
    't.status',
    't.created_at',
    't.last_activity',
    'customer.full_name as customer_name',
    'customer.email as customer_email',
    'assigned.full_name as assigned_name'
];

$from_clause = 'FROM tickets t';
$joins = [
    'LEFT JOIN users customer ON t.customer_id = customer.user_id',
    'LEFT JOIN users assigned ON t.assigned_to = assigned.user_id'
];

$where_conditions = [];
$params = [];
$types = '';

// Role-based access control
if ($user_role === 'CUSTOMER') {
    $where_conditions[] = 't.customer_id = ?';
    $params[] = $user_id;
    $types .= 'i';
}

// Search conditions based on search type
$search_param = "%{$search_term}%";

switch ($search_type) {
    case 'titles':
        $where_conditions[] = 't.title LIKE ?';
        $params[] = $search_param;
        $types .= 's';
        break;
    
    case 'descriptions':
        $where_conditions[] = 't.description LIKE ?';
        $params[] = $search_param;
        $types .= 's';
        break;
    
    case 'replies':
        // Join with replies table and search in reply content
        $joins[] = 'INNER JOIN ticket_replies tr ON t.ticket_id = tr.ticket_id';
        if ($user_role === 'CUSTOMER') {
            $joins[] = 'AND tr.is_internal = FALSE'; // Customers can't see internal notes
        }
        $where_conditions[] = 'tr.content LIKE ?';
        $params[] = $search_param;
        $types .= 's';
        $select_fields[] = 'tr.content as reply_content';
        $select_fields[] = 'tr.created_at as reply_date';
        $select_fields[] = 'reply_author.full_name as reply_author';
        $joins[] = 'LEFT JOIN users reply_author ON tr.author_id = reply_author.user_id';
        break;
    
    default: // 'all'
        // Search in titles, descriptions, and replies
        $reply_subquery = "EXISTS (
            SELECT 1 FROM ticket_replies tr2 
            WHERE tr2.ticket_id = t.ticket_id 
            AND tr2.content LIKE ?
            " . ($user_role === 'CUSTOMER' ? 'AND tr2.is_internal = FALSE' : '') . "
        )";
        
        $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ? OR {$reply_subquery})";
        $params[] = $search_param; // for title
        $params[] = $search_param; // for description
        $params[] = $search_param; // for reply subquery
        $types .= 'sss';
        break;
}

// Additional filters
if (!empty($category)) {
    $where_conditions[] = 't.category = ?';
    $params[] = $category;
    $types .= 's';
}

if (!empty($priority)) {
    $where_conditions[] = 't.priority = ?';
    $params[] = $priority;
    $types .= 's';
}

if (!empty($status)) {
    $where_conditions[] = 't.status = ?';
    $params[] = $status;
    $types .= 's';
}

if (!empty($date_from)) {
    $where_conditions[] = 'DATE(t.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = 'DATE(t.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($assigned_to) && $user_role === 'ADMIN') {
    if ($assigned_to === 'unassigned') {
        $where_conditions[] = 't.assigned_to IS NULL';
    } else {
        $where_conditions[] = 't.assigned_to = ?';
        $params[] = $assigned_to;
        $types .= 'i';
    }
}

if (!empty($customer_id) && $user_role === 'ADMIN') {
    $where_conditions[] = 't.customer_id = ?';
    $params[] = $customer_id;
    $types .= 'i';
}

// Build the complete query
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Calculate relevance score for ranking
$relevance_score = "
    CASE 
        WHEN t.title LIKE ? THEN 10
        WHEN t.title LIKE ? THEN 8
        WHEN t.description LIKE ? THEN 6
        WHEN t.description LIKE ? THEN 4
        ELSE 2
    END as relevance_score
";

$select_fields[] = $relevance_score;

// Add relevance parameters
$exact_match = "%{$search_term}%";
$word_match = "% {$search_term} %";
$relevance_params = [$exact_match, $word_match, $exact_match, $word_match];
$relevance_types = 'ssss';

// Get total count
$count_query = "SELECT COUNT(DISTINCT t.ticket_id) as total 
                {$from_clause} 
                " . implode(' ', $joins) . " 
                {$where_clause}";

$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['total'];

// Get search results with relevance scoring
$main_query = "SELECT DISTINCT " . implode(', ', $select_fields) . "
               {$from_clause} 
               " . implode(' ', $joins) . " 
               {$where_clause}
               ORDER BY relevance_score DESC, t.last_activity DESC
               LIMIT ? OFFSET ?";

// Combine all parameters
$all_params = array_merge($params, $relevance_params, [$limit, $offset]);
$all_types = $types . $relevance_types . 'ii';

$stmt = $conn->prepare($main_query);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$result = $stmt->get_result();

$search_results = [];
while ($row = $result->fetch_assoc()) {
    // Highlight search terms in results
    $highlighted_title = highlightSearchTerm($row['title'], $search_term);
    $highlighted_description = highlightSearchTerm(
        strlen($row['description']) > 200 ? substr($row['description'], 0, 200) . '...' : $row['description'],
        $search_term
    );
    
    $search_results[] = [
        'ticket_id' => $row['ticket_id'],
        'title' => $row['title'],
        'highlighted_title' => $highlighted_title,
        'description' => $row['description'],
        'highlighted_description' => $highlighted_description,
        'category' => $row['category'],
        'priority' => $row['priority'],
        'status' => $row['status'],
        'customer_name' => $row['customer_name'],
        'customer_email' => $row['customer_email'],
        'assigned_name' => $row['assigned_name'],
        'created_at' => $row['created_at'],
        'last_activity' => $row['last_activity'],
        'relevance_score' => $row['relevance_score'],
        'reply_content' => $row['reply_content'] ?? null,
        'reply_date' => $row['reply_date'] ?? null,
        'reply_author' => $row['reply_author'] ?? null
    ];
}

// Generate search suggestions if no results found
$suggestions = [];
if (empty($search_results)) {
    $suggestions = generateSearchSuggestions($search_term, $conn, $user_role, $user_id);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'results' => $search_results,
    'total' => $total_results,
    'search_term' => $search_term,
    'search_type' => $search_type,
    'suggestions' => $suggestions,
    'has_more' => ($offset + $limit) < $total_results
]);

/**
 * Highlight search terms in text
 */
function highlightSearchTerm($text, $search_term) {
    if (empty($search_term) || empty($text)) {
        return htmlspecialchars($text);
    }
    
    $highlighted = preg_replace(
        '/(' . preg_quote($search_term, '/') . ')/i',
        '<mark class="search-highlight">$1</mark>',
        htmlspecialchars($text)
    );
    
    return $highlighted;
}

/**
 * Generate search suggestions based on existing ticket data
 */
function generateSearchSuggestions($search_term, $conn, $user_role, $user_id) {
    $suggestions = [];
    
    // Role-based access control for suggestions
    $role_condition = $user_role === 'CUSTOMER' ? 'AND t.customer_id = ?' : '';
    $role_params = $user_role === 'CUSTOMER' ? [$user_id] : [];
    $role_types = $user_role === 'CUSTOMER' ? 'i' : '';
    
    // Get similar titles
    $title_query = "SELECT DISTINCT t.title 
                    FROM tickets t 
                    WHERE t.title LIKE ? {$role_condition}
                    ORDER BY t.created_at DESC 
                    LIMIT 5";
    
    $similar_param = "%{$search_term}%";
    $title_params = array_merge([$similar_param], $role_params);
    $title_types = 's' . $role_types;
    
    $title_stmt = $conn->prepare($title_query);
    if (!empty($title_params)) {
        $title_stmt->bind_param($title_types, ...$title_params);
    }
    $title_stmt->execute();
    $title_result = $title_stmt->get_result();
    
    while ($row = $title_result->fetch_assoc()) {
        $suggestions[] = [
            'type' => 'title',
            'text' => $row['title'],
            'category' => 'Similar Tickets'
        ];
    }
    
    // Get common keywords from descriptions
    $keyword_query = "SELECT t.description 
                      FROM tickets t 
                      WHERE t.description LIKE ? {$role_condition}
                      ORDER BY t.created_at DESC 
                      LIMIT 10";
    
    $keyword_stmt = $conn->prepare($keyword_query);
    if (!empty($title_params)) {
        $keyword_stmt->bind_param($title_types, ...$title_params);
    }
    $keyword_stmt->execute();
    $keyword_result = $keyword_stmt->get_result();
    
    $common_words = [];
    while ($row = $keyword_result->fetch_assoc()) {
        $words = str_word_count(strtolower($row['description']), 1);
        foreach ($words as $word) {
            if (strlen($word) > 3 && stripos($word, $search_term) !== false) {
                $common_words[$word] = ($common_words[$word] ?? 0) + 1;
            }
        }
    }
    
    arsort($common_words);
    $top_words = array_slice(array_keys($common_words), 0, 3);
    
    foreach ($top_words as $word) {
        $suggestions[] = [
            'type' => 'keyword',
            'text' => $word,
            'category' => 'Related Keywords'
        ];
    }
    
    return $suggestions;
}
?>