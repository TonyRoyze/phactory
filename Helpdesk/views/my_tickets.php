<?php
// This file is views/my_tickets.php - Customer ticket dashboard

// Ensure user is a customer
if ($_SESSION['user_role'] !== 'CUSTOMER') {
    header("Location: app.php?view=dashboard");
    exit();
}

require 'connector.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'All';
$category_filter = $_GET['category'] ?? 'All';
$search_term = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the WHERE clause for filtering
$where_clauses = ["t.customer_id = ?"];
$params = [$_SESSION['user_id']];
$types = 'i';

if ($status_filter !== 'All') {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($category_filter !== 'All') {
    $where_clauses[] = "t.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($search_term)) {
    // Enhanced search including replies (only public replies for customers)
    $reply_subquery = "EXISTS (
        SELECT 1 FROM ticket_replies tr 
        WHERE tr.ticket_id = t.ticket_id 
        AND tr.content LIKE ?
        AND tr.is_internal = FALSE
    )";
    
    $where_clauses[] = "(t.title LIKE ? OR t.description LIKE ? OR {$reply_subquery})";
    $search_param = "%{$search_term}%";
    $params[] = $search_param; // for title
    $params[] = $search_param; // for description
    $params[] = $search_param; // for reply subquery
    $types .= 'sss';
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM tickets t WHERE " . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_tickets = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_tickets / $per_page);

// Get tickets with pagination
$sql = "SELECT t.ticket_id, t.title, t.description, t.category, t.priority, t.status, 
               t.created_at, t.last_activity, u.full_name as assigned_name,
               (SELECT COUNT(*) FROM ticket_replies tr WHERE tr.ticket_id = t.ticket_id AND tr.is_internal = FALSE) as reply_count
        FROM tickets t 
        LEFT JOIN users u ON t.assigned_to = u.user_id 
        WHERE " . $where_sql . "
        ORDER BY t.last_activity DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Page header
echo '<div class="page-header">';
echo '<h1>My Tickets</h1>';
echo '<a href="app.php?view=create_ticket" class="btn-create">Create New Ticket</a>';
echo '</div>';

// Filter and search bar
echo '<div class="filter-bar">';

// Search form
echo '<form action="app.php" method="GET" class="search-form">';
echo '    <input type="hidden" name="view" value="my_tickets">';
if ($status_filter !== 'All') echo '    <input type="hidden" name="status" value="' . htmlspecialchars($status_filter) . '">';
if ($category_filter !== 'All') echo '    <input type="hidden" name="category" value="' . htmlspecialchars($category_filter) . '">';
echo '    <input type="text" name="search" class="search-input" placeholder="Search your tickets..." value="' . htmlspecialchars($search_term) . '" autocomplete="off">';
echo '    <button type="submit">Search</button>';
echo '    <a href="app.php?view=search" class="btn-advanced-search-link">Advanced Search</a>';
echo '</form>';

// Filter pills container
echo '<div class="filter-pills-container">';

// Status filter pills
echo '<div class="category-pills">';
echo '<span class="filter-label">Status:</span>';
$statuses = ['All', 'Open', 'In Progress', 'Resolved', 'Closed'];
foreach ($statuses as $status) {
    $url_params = [];
    if ($category_filter !== 'All') $url_params[] = 'category=' . urlencode($category_filter);
    if (!empty($search_term)) $url_params[] = 'search=' . urlencode($search_term);
    if ($status !== 'All') $url_params[] = 'status=' . urlencode($status);
    $url_params[] = 'view=my_tickets';
    
    $url = 'app.php?' . implode('&', $url_params);
    $active_class = ($status_filter === $status) ? 'active' : '';
    echo '<a href="' . $url . '" class="' . $active_class . '">' . htmlspecialchars($status) . '</a>';
}
echo '</div>';

// Category filter pills
echo '<div class="category-pills">';
echo '<span class="filter-label">Category:</span>';
$categories = ['All', 'Technical', 'Billing', 'General'];
foreach ($categories as $category) {
    $url_params = [];
    if ($status_filter !== 'All') $url_params[] = 'status=' . urlencode($status_filter);
    if (!empty($search_term)) $url_params[] = 'search=' . urlencode($search_term);
    if ($category !== 'All') $url_params[] = 'category=' . urlencode($category);
    $url_params[] = 'view=my_tickets';
    
    $url = 'app.php?' . implode('&', $url_params);
    $active_class = ($category_filter === $category) ? 'active' : '';
    echo '<a href="' . $url . '" class="' . $active_class . '">' . htmlspecialchars($category) . '</a>';
}
echo '</div>';

echo '</div>'; // filter-pills-container
echo '</div>'; // filter-bar

// Results summary
echo '<div class="results-summary">';
if (!empty($search_term) || $status_filter !== 'All' || $category_filter !== 'All') {
    echo '<p>Showing ' . $total_tickets . ' ticket' . ($total_tickets !== 1 ? 's' : '') . ' matching your filters';
    if (!empty($search_term)) echo ' for "' . htmlspecialchars($search_term) . '"';
    echo '</p>';
} else {
    echo '<p>You have ' . $total_tickets . ' ticket' . ($total_tickets !== 1 ? 's' : '') . ' total</p>';
}
echo '</div>';

// Tickets list
if ($result->num_rows > 0) {
    echo '<div class="tickets-list">';
    while ($row = $result->fetch_assoc()) {
        echo '<div class="ticket-card">';
        
        // Ticket header with ID and title
        echo '<div class="ticket-header">';
        echo '<h3><a href="app.php?view=ticket&id=' . $row['ticket_id'] . '">#' . $row['ticket_id'] . ' - ' . htmlspecialchars($row['title']) . '</a></h3>';
        echo '<div class="ticket-badges">';
        echo '<span class="badge priority-' . strtolower($row['priority']) . '">' . htmlspecialchars($row['priority']) . '</span>';
        echo '<span class="badge status-' . strtolower(str_replace(' ', '-', $row['status'])) . '">' . htmlspecialchars($row['status']) . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Ticket meta information
        echo '<div class="ticket-meta">';
        echo '<span class="category category-' . strtolower($row['category']) . '">' . htmlspecialchars($row['category']) . '</span>';
        echo '<span class="created-date">Created: ' . date('M j, Y g:i A', strtotime($row['created_at'])) . '</span>';
        if ($row['last_activity'] !== $row['created_at']) {
            echo '<span class="last-activity">Last activity: ' . date('M j, Y g:i A', strtotime($row['last_activity'])) . '</span>';
        }
        if ($row['assigned_name']) {
            echo '<span class="assigned-to">Assigned to: ' . htmlspecialchars($row['assigned_name']) . '</span>';
        }
        echo '</div>';
        
        // Ticket description preview
        echo '<div class="ticket-description">';
        $description_preview = strlen($row['description']) > 200 ? 
            substr($row['description'], 0, 200) . '...' : 
            $row['description'];
        echo '<p>' . nl2br(htmlspecialchars($description_preview)) . '</p>';
        echo '</div>';
        
        // Ticket footer with reply count and actions
        echo '<div class="ticket-footer">';
        echo '<span class="reply-count">' . $row['reply_count'] . ' ' . ($row['reply_count'] == 1 ? 'reply' : 'replies') . '</span>';
        echo '<a href="app.php?view=ticket&id=' . $row['ticket_id'] . '" class="btn-view-ticket">View Details</a>';
        echo '</div>';
        
        echo '</div>'; // ticket-card
    }
    echo '</div>'; // tickets-list
    
    // Pagination
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        
        // Build base URL for pagination
        $base_params = [];
        $base_params[] = 'view=my_tickets';
        if ($status_filter !== 'All') $base_params[] = 'status=' . urlencode($status_filter);
        if ($category_filter !== 'All') $base_params[] = 'category=' . urlencode($category_filter);
        if (!empty($search_term)) $base_params[] = 'search=' . urlencode($search_term);
        $base_url = 'app.php?' . implode('&', $base_params);
        
        // Previous page
        if ($page > 1) {
            echo '<a href="' . $base_url . '&page=' . ($page - 1) . '" class="pagination-link">&laquo; Previous</a>';
        }
        
        // Page numbers
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo '<a href="' . $base_url . '&page=1" class="pagination-link">1</a>';
            if ($start_page > 2) echo '<span class="pagination-ellipsis">...</span>';
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
                echo '<span class="pagination-link active">' . $i . '</span>';
            } else {
                echo '<a href="' . $base_url . '&page=' . $i . '" class="pagination-link">' . $i . '</a>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) echo '<span class="pagination-ellipsis">...</span>';
            echo '<a href="' . $base_url . '&page=' . $total_pages . '" class="pagination-link">' . $total_pages . '</a>';
        }
        
        // Next page
        if ($page < $total_pages) {
            echo '<a href="' . $base_url . '&page=' . ($page + 1) . '" class="pagination-link">Next &raquo;</a>';
        }
        
        echo '</div>'; // pagination
    }
    
} else {
    // No tickets found
    echo '<div class="no-tickets">';
    if (!empty($search_term) || $status_filter !== 'All' || $category_filter !== 'All') {
        echo '<p>No tickets found matching your search criteria.</p>';
        echo '<p><a href="app.php?view=my_tickets">View all tickets</a> or <a href="app.php?view=create_ticket">create a new ticket</a>.</p>';
    } else {
        echo '<p>You haven\'t created any tickets yet.</p>';
        echo '<p><a href="app.php?view=create_ticket" class="btn-create">Create your first ticket</a> to get started.</p>';
    }
    echo '</div>';
}
?>