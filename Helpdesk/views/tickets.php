<?php
// This file is views/tickets.php - Admin ticket management interface

// Ensure user is an admin
if ($_SESSION['user_role'] !== 'ADMIN') {
    header("Location: app.php?view=my_tickets");
    exit();
}

require 'connector.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'All';
$category_filter = $_GET['category'] ?? 'All';
$priority_filter = $_GET['priority'] ?? 'All';
$assigned_filter = $_GET['assigned'] ?? 'All';
$search_term = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort'] ?? 'last_activity';
$sort_order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build the WHERE clause for filtering
$where_clauses = [];
$params = [];
$types = '';

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

if ($priority_filter !== 'All') {
    $where_clauses[] = "t.priority = ?";
    $params[] = $priority_filter;
    $types .= 's';
}

if ($assigned_filter !== 'All') {
    if ($assigned_filter === 'Unassigned') {
        $where_clauses[] = "t.assigned_to IS NULL";
    } else {
        $where_clauses[] = "t.assigned_to = ?";
        $params[] = $assigned_filter;
        $types .= 'i';
    }
}

if (!empty($search_term)) {
    // Enhanced search including replies
    $reply_subquery = "EXISTS (
        SELECT 1 FROM ticket_replies tr 
        WHERE tr.ticket_id = t.ticket_id 
        AND tr.content LIKE ?
    )";
    
    $where_clauses[] = "(t.title LIKE ? OR t.description LIKE ? OR customer.full_name LIKE ? OR customer.email LIKE ? OR {$reply_subquery})";
    $search_param = "%{$search_term}%";
    $params[] = $search_param; // for title
    $params[] = $search_param; // for description
    $params[] = $search_param; // for customer name
    $params[] = $search_param; // for customer email
    $params[] = $search_param; // for reply subquery
    $types .= 'sssss';
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Validate sort parameters
$valid_sorts = ['ticket_id', 'title', 'status', 'priority', 'category', 'created_at', 'last_activity', 'customer_name'];
$valid_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $valid_sorts)) $sort_by = 'last_activity';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'DESC';

// Map sort fields to actual column names
$sort_mapping = [
    'ticket_id' => 't.ticket_id',
    'title' => 't.title',
    'status' => 't.status',
    'priority' => 't.priority',
    'category' => 't.category',
    'created_at' => 't.created_at',
    'last_activity' => 't.last_activity',
    'customer_name' => 'customer.full_name'
];

$order_sql = "ORDER BY " . $sort_mapping[$sort_by] . " " . $sort_order;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM tickets t 
              LEFT JOIN users customer ON t.customer_id = customer.user_id 
              " . $where_sql;

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_tickets = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_tickets = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_tickets / $per_page);

// Get tickets with pagination
$sql = "SELECT t.ticket_id, t.title, t.description, t.category, t.priority, t.status, 
               t.created_at, t.last_activity, t.customer_id, t.assigned_to,
               customer.full_name as customer_name, customer.email as customer_email,
               assigned.full_name as assigned_name,
               (SELECT COUNT(*) FROM ticket_replies tr WHERE tr.ticket_id = t.ticket_id AND tr.is_internal = FALSE) as reply_count,
               (SELECT COUNT(*) FROM ticket_replies tr WHERE tr.ticket_id = t.ticket_id AND tr.is_internal = TRUE) as internal_count
        FROM tickets t 
        LEFT JOIN users customer ON t.customer_id = customer.user_id
        LEFT JOIN users assigned ON t.assigned_to = assigned.user_id 
        " . $where_sql . " " . $order_sql . "
        LIMIT ? OFFSET ?";

$final_params = $params;
$final_params[] = $per_page;
$final_params[] = $offset;
$final_types = $types . 'ii';

$stmt = $conn->prepare($sql);
if (!empty($final_params)) {
    $stmt->bind_param($final_types, ...$final_params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get admin users for assignment filter
$admin_sql = "SELECT user_id, full_name FROM users WHERE user_role = 'ADMIN' ORDER BY full_name";
$admin_result = $conn->query($admin_sql);
$admin_users = [];
while ($admin = $admin_result->fetch_assoc()) {
    $admin_users[] = $admin;
}

// Page header
echo '<div class="page-header">';
echo '<h1>All Tickets</h1>';
echo '<div class="header-actions">';
echo '<a href="app.php?view=dashboard" class="btn-secondary">← Dashboard</a>';
echo '</div>';
echo '</div>';

// Advanced filter bar
echo '<div class="admin-filter-bar">';

// Search form
echo '<form action="app.php" method="GET" class="admin-search-form">';
echo '    <input type="hidden" name="view" value="tickets">';
echo '    <div class="search-row">';
echo '        <input type="text" name="search" class="search-input" placeholder="Search tickets, customers, or content..." value="' . htmlspecialchars($search_term) . '" autocomplete="off">';
echo '        <button type="submit" class="btn-search">Search</button>';
echo '        <a href="app.php?view=search" class="btn-advanced-search-link">Advanced Search</a>';
echo '    </div>';

// Date range filters
echo '    <div class="date-filters">';
echo '        <div class="date-group">';
echo '            <label for="date_from">From:</label>';
echo '            <input type="date" id="date_from" name="date_from" value="' . htmlspecialchars($date_from) . '">';
echo '        </div>';
echo '        <div class="date-group">';
echo '            <label for="date_to">To:</label>';
echo '            <input type="date" id="date_to" name="date_to" value="' . htmlspecialchars($date_to) . '">';
echo '        </div>';
echo '    </div>';

// Preserve other filters in search form
if ($status_filter !== 'All') echo '    <input type="hidden" name="status" value="' . htmlspecialchars($status_filter) . '">';
if ($category_filter !== 'All') echo '    <input type="hidden" name="category" value="' . htmlspecialchars($category_filter) . '">';
if ($priority_filter !== 'All') echo '    <input type="hidden" name="priority" value="' . htmlspecialchars($priority_filter) . '">';
if ($assigned_filter !== 'All') echo '    <input type="hidden" name="assigned" value="' . htmlspecialchars($assigned_filter) . '">';
if ($sort_by !== 'last_activity') echo '    <input type="hidden" name="sort" value="' . htmlspecialchars($sort_by) . '">';
if ($sort_order !== 'DESC') echo '    <input type="hidden" name="order" value="' . htmlspecialchars($sort_order) . '">';

echo '</form>';

// Filter pills container
echo '<div class="admin-filter-pills">';

// Build base URL for filters
function buildFilterUrl($new_params = []) {
    global $status_filter, $category_filter, $priority_filter, $assigned_filter, $search_term, $date_from, $date_to, $sort_by, $sort_order;
    
    $params = ['view' => 'tickets'];
    
    // Preserve existing filters unless overridden
    if (isset($new_params['status'])) $params['status'] = $new_params['status'];
    elseif ($status_filter !== 'All') $params['status'] = $status_filter;
    
    if (isset($new_params['category'])) $params['category'] = $new_params['category'];
    elseif ($category_filter !== 'All') $params['category'] = $category_filter;
    
    if (isset($new_params['priority'])) $params['priority'] = $new_params['priority'];
    elseif ($priority_filter !== 'All') $params['priority'] = $priority_filter;
    
    if (isset($new_params['assigned'])) $params['assigned'] = $new_params['assigned'];
    elseif ($assigned_filter !== 'All') $params['assigned'] = $assigned_filter;
    
    if (!empty($search_term)) $params['search'] = $search_term;
    if (!empty($date_from)) $params['date_from'] = $date_from;
    if (!empty($date_to)) $params['date_to'] = $date_to;
    if ($sort_by !== 'last_activity') $params['sort'] = $sort_by;
    if ($sort_order !== 'DESC') $params['order'] = $sort_order;
    
    return 'app.php?' . http_build_query($params);
}

// Status filter pills
echo '<div class="filter-group">';
echo '<span class="filter-label">Status:</span>';
$statuses = ['All', 'Open', 'In Progress', 'Resolved', 'Closed'];
foreach ($statuses as $status) {
    $url = buildFilterUrl(['status' => $status === 'All' ? null : $status]);
    $active_class = ($status_filter === $status) ? 'active' : '';
    echo '<a href="' . $url . '" class="filter-pill ' . $active_class . '">' . htmlspecialchars($status) . '</a>';
}
echo '</div>';

// Category filter pills
echo '<div class="filter-group">';
echo '<span class="filter-label">Category:</span>';
$categories = ['All', 'Technical', 'Billing', 'General'];
foreach ($categories as $category) {
    $url = buildFilterUrl(['category' => $category === 'All' ? null : $category]);
    $active_class = ($category_filter === $category) ? 'active' : '';
    echo '<a href="' . $url . '" class="filter-pill ' . $active_class . '">' . htmlspecialchars($category) . '</a>';
}
echo '</div>';

// Priority filter pills
echo '<div class="filter-group">';
echo '<span class="filter-label">Priority:</span>';
$priorities = ['All', 'Low', 'Medium', 'High', 'Urgent'];
foreach ($priorities as $priority) {
    $url = buildFilterUrl(['priority' => $priority === 'All' ? null : $priority]);
    $active_class = ($priority_filter === $priority) ? 'active' : '';
    echo '<a href="' . $url . '" class="filter-pill ' . $active_class . '">' . htmlspecialchars($priority) . '</a>';
}
echo '</div>';

// Assignment filter pills
echo '<div class="filter-group">';
echo '<span class="filter-label">Assigned:</span>';
echo '<a href="' . buildFilterUrl(['assigned' => null]) . '" class="filter-pill ' . (($assigned_filter === 'All') ? 'active' : '') . '">All</a>';
echo '<a href="' . buildFilterUrl(['assigned' => 'Unassigned']) . '" class="filter-pill ' . (($assigned_filter === 'Unassigned') ? 'active' : '') . '">Unassigned</a>';
foreach ($admin_users as $admin) {
    $url = buildFilterUrl(['assigned' => $admin['user_id']]);
    $active_class = ($assigned_filter == $admin['user_id']) ? 'active' : '';
    echo '<a href="' . $url . '" class="filter-pill ' . $active_class . '">' . htmlspecialchars($admin['full_name']) . '</a>';
}
echo '</div>';

echo '</div>'; // admin-filter-pills
echo '</div>'; // admin-filter-bar

// Results summary and sorting
echo '<div class="results-header">';
echo '<div class="results-summary">';
if (!empty($search_term) || $status_filter !== 'All' || $category_filter !== 'All' || $priority_filter !== 'All' || $assigned_filter !== 'All' || !empty($date_from) || !empty($date_to)) {
    echo '<p>Showing ' . $total_tickets . ' ticket' . ($total_tickets !== 1 ? 's' : '') . ' matching your filters';
    if (!empty($search_term)) echo ' for "' . htmlspecialchars($search_term) . '"';
    echo '</p>';
} else {
    echo '<p>Showing all ' . $total_tickets . ' ticket' . ($total_tickets !== 1 ? 's' : '') . '</p>';
}
echo '</div>';

// Sorting controls
echo '<div class="sort-controls">';
echo '<span class="sort-label">Sort by:</span>';
$sort_options = [
    'last_activity' => 'Last Activity',
    'created_at' => 'Created Date',
    'priority' => 'Priority',
    'status' => 'Status',
    'customer_name' => 'Customer',
    'ticket_id' => 'Ticket ID'
];

foreach ($sort_options as $sort_key => $sort_label) {
    $new_order = ($sort_by === $sort_key && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    $url_params = $_GET;
    $url_params['sort'] = $sort_key;
    $url_params['order'] = $new_order;
    $url = 'app.php?' . http_build_query($url_params);
    
    $active_class = ($sort_by === $sort_key) ? 'active' : '';
    $arrow = '';
    if ($sort_by === $sort_key) {
        $arrow = $sort_order === 'ASC' ? ' ↑' : ' ↓';
    }
    
    echo '<a href="' . $url . '" class="sort-link ' . $active_class . '">' . $sort_label . $arrow . '</a>';
}
echo '</div>';

echo '</div>'; // results-header

// Tickets table
if ($result->num_rows > 0) {
    echo '<div class="admin-tickets-table-container">';
    echo '<table class="admin-tickets-table">';
    
    // Table header
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Title</th>';
    echo '<th>Customer</th>';
    echo '<th>Category</th>';
    echo '<th>Priority</th>';
    echo '<th>Status</th>';
    echo '<th>Assigned To</th>';
    echo '<th>Replies</th>';
    echo '<th>Created</th>';
    echo '<th>Last Activity</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr class="ticket-row">';
        
        // Ticket ID
        echo '<td class="ticket-id">';
        echo '<a href="app.php?view=ticket&id=' . $row['ticket_id'] . '" class="ticket-link">#' . $row['ticket_id'] . '</a>';
        echo '</td>';
        
        // Title with description preview
        echo '<td class="ticket-title">';
        echo '<div class="title-container">';
        echo '<a href="app.php?view=ticket&id=' . $row['ticket_id'] . '" class="ticket-title-link">' . htmlspecialchars($row['title']) . '</a>';
        $description_preview = strlen($row['description']) > 100 ? 
            substr($row['description'], 0, 100) . '...' : 
            $row['description'];
        echo '<div class="description-preview">' . htmlspecialchars($description_preview) . '</div>';
        echo '</div>';
        echo '</td>';
        
        // Customer
        echo '<td class="customer-info">';
        echo '<div class="customer-name">' . htmlspecialchars($row['customer_name']) . '</div>';
        echo '<div class="customer-email">' . htmlspecialchars($row['customer_email']) . '</div>';
        echo '</td>';
        
        // Category
        echo '<td class="category">';
        echo '<span class="category-badge category-' . strtolower($row['category']) . '">' . htmlspecialchars($row['category']) . '</span>';
        echo '</td>';
        
        // Priority
        echo '<td class="priority">';
        echo '<span class="priority-badge priority-' . strtolower($row['priority']) . '">' . htmlspecialchars($row['priority']) . '</span>';
        echo '</td>';
        
        // Status
        echo '<td class="status">';
        echo '<span class="status-badge status-' . strtolower(str_replace(' ', '-', $row['status'])) . '">' . htmlspecialchars($row['status']) . '</span>';
        echo '</td>';
        
        // Assigned To
        echo '<td class="assigned-to">';
        if ($row['assigned_name']) {
            echo '<span class="assigned-name">' . htmlspecialchars($row['assigned_name']) . '</span>';
        } else {
            echo '<span class="unassigned">Unassigned</span>';
        }
        echo '</td>';
        
        // Reply counts
        echo '<td class="reply-counts">';
        echo '<div class="reply-count-container">';
        echo '<span class="public-replies" title="Public replies">' . $row['reply_count'] . '</span>';
        if ($row['internal_count'] > 0) {
            echo '<span class="internal-replies" title="Internal notes">(' . $row['internal_count'] . ')</span>';
        }
        echo '</div>';
        echo '</td>';
        
        // Created date
        echo '<td class="created-date">';
        echo '<div class="date-container">';
        echo '<div class="date">' . date('M j, Y', strtotime($row['created_at'])) . '</div>';
        echo '<div class="time">' . date('g:i A', strtotime($row['created_at'])) . '</div>';
        echo '</div>';
        echo '</td>';
        
        // Last activity
        echo '<td class="last-activity">';
        echo '<div class="date-container">';
        if ($row['last_activity'] !== $row['created_at']) {
            echo '<div class="date">' . date('M j, Y', strtotime($row['last_activity'])) . '</div>';
            echo '<div class="time">' . date('g:i A', strtotime($row['last_activity'])) . '</div>';
        } else {
            echo '<div class="no-activity">No activity</div>';
        }
        echo '</div>';
        echo '</td>';
        
        // Actions
        echo '<td class="actions">';
        echo '<div class="action-buttons">';
        echo '<a href="app.php?view=ticket&id=' . $row['ticket_id'] . '" class="btn-action btn-view" title="View Ticket">View</a>';
        // Future: Add assign and status change buttons here
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // admin-tickets-table-container
    
    // Pagination
    if ($total_pages > 1) {
        echo '<div class="admin-pagination">';
        
        // Build base URL for pagination
        $base_params = $_GET;
        unset($base_params['page']);
        $base_url = 'app.php?' . http_build_query($base_params);
        $separator = empty($base_params) ? '?' : '&';
        
        // Previous page
        if ($page > 1) {
            echo '<a href="' . $base_url . $separator . 'page=' . ($page - 1) . '" class="pagination-link">&laquo; Previous</a>';
        }
        
        // Page numbers
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo '<a href="' . $base_url . $separator . 'page=1" class="pagination-link">1</a>';
            if ($start_page > 2) echo '<span class="pagination-ellipsis">...</span>';
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
                echo '<span class="pagination-link active">' . $i . '</span>';
            } else {
                echo '<a href="' . $base_url . $separator . 'page=' . $i . '" class="pagination-link">' . $i . '</a>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) echo '<span class="pagination-ellipsis">...</span>';
            echo '<a href="' . $base_url . $separator . 'page=' . $total_pages . '" class="pagination-link">' . $total_pages . '</a>';
        }
        
        // Next page
        if ($page < $total_pages) {
            echo '<a href="' . $base_url . $separator . 'page=' . ($page + 1) . '" class="pagination-link">Next &raquo;</a>';
        }
        
        echo '</div>'; // admin-pagination
    }
    
} else {
    // No tickets found
    echo '<div class="no-tickets-admin">';
    if (!empty($search_term) || $status_filter !== 'All' || $category_filter !== 'All' || $priority_filter !== 'All' || $assigned_filter !== 'All' || !empty($date_from) || !empty($date_to)) {
        echo '<div class="no-results-icon">🔍</div>';
        echo '<h3>No tickets found</h3>';
        echo '<p>No tickets match your current search criteria.</p>';
        echo '<p><a href="app.php?view=tickets" class="btn-secondary">Clear all filters</a></p>';
    } else {
        echo '<div class="no-results-icon">📋</div>';
        echo '<h3>No tickets yet</h3>';
        echo '<p>There are no support tickets in the system yet.</p>';
    }
    echo '</div>';
}
?>