<?php
// This file is views/users.php - Admin user management interface

// Ensure user is an admin
if ($_SESSION['user_role'] !== 'ADMIN') {
    header("Location: app.php?view=dashboard");
    exit();
}

require 'connector.php';

// Get filter parameters
$role_filter = $_GET['role'] ?? 'All';
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build the WHERE clause for filtering
$where_clauses = [];
$params = [];
$types = '';

if ($role_filter !== 'All') {
    $where_clauses[] = "user_role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($search_term)) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Validate sort parameters
$valid_sorts = ['user_id', 'username', 'full_name', 'email', 'user_role', 'created_at', 'ticket_count'];
$valid_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $valid_sorts)) $sort_by = 'created_at';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'DESC';

// Map sort fields to actual column names
$sort_mapping = [
    'user_id' => 'u.user_id',
    'username' => 'u.username',
    'full_name' => 'u.full_name',
    'email' => 'u.email',
    'user_role' => 'u.user_role',
    'created_at' => 'u.created_at',
    'ticket_count' => 'ticket_count'
];

$order_sql = "ORDER BY " . $sort_mapping[$sort_by] . " " . $sort_order;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM users u " . $where_sql;

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_users = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_users / $per_page);

// Get users with pagination and ticket count
$sql = "SELECT u.user_id, u.username, u.email, u.full_name, u.user_role, u.created_at,
               (SELECT COUNT(*) FROM tickets t WHERE t.customer_id = u.user_id) as ticket_count
        FROM users u
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

// Page header
echo '<div class="page-header">';
echo '<h1>Manage Users</h1>';
echo '<div class="header-actions">';
echo '<a href="app.php?view=dashboard" class="btn-secondary">← Dashboard</a>';
echo '<a href="app.php?view=create_user" class="btn-primary" style="margin-left: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Add New User
            </a>';
echo '</div>';
echo '</div>';

// Advanced filter bar
echo '<div class="admin-filter-bar">';

// Search form
echo '<form action="app.php" method="GET" class="admin-search-form">';
echo '    <input type="hidden" name="view" value="users">';
echo '    <div class="search-row">';
echo '        <input type="text" name="search" class="search-input" placeholder="Search by username, email, or name..." value="' . htmlspecialchars($search_term) . '" autocomplete="off">';
echo '        <button type="submit" class="search-widget-btn">';
echo '          <span class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg></span>';
// if (!$compact):
echo '          <span class="search-text">Search</span>';
// endif;
echo '         </button>';
echo '    </div>';

// Preserve other filters in search form
if ($role_filter !== 'All') echo '    <input type="hidden" name="role" value="' . htmlspecialchars($role_filter) . '">';
if ($sort_by !== 'created_at') echo '    <input type="hidden" name="sort" value="' . htmlspecialchars($sort_by) . '">';
if ($sort_order !== 'DESC') echo '    <input type="hidden" name="order" value="' . htmlspecialchars($sort_order) . '">';

echo '</form>';

// Filter pills container
echo '<div class="admin-filter-pills">';

// Build base URL for filters
function buildFilterUrl($new_params = []) {
    global $role_filter, $search_term, $sort_by, $sort_order;
    
    $params = ['view' => 'users'];
    
    if (isset($new_params['role'])) $params['role'] = $new_params['role'];
    elseif ($role_filter !== 'All') $params['role'] = $role_filter;
    
    if (!empty($search_term)) $params['search'] = $search_term;
    if ($sort_by !== 'created_at') $params['sort'] = $sort_by;
    if ($sort_order !== 'DESC') $params['order'] = $sort_order;
    
    return 'app.php?' . http_build_query($params);
}

// Role filter pills
echo '<div class="filter-group">';
echo '<span class="filter-label">Role:</span>';
$roles = ['All', 'ADMIN', 'CUSTOMER'];
foreach ($roles as $role) {
    $url = buildFilterUrl(['role' => $role === 'All' ? null : $role]);
    $active_class = ($role_filter === $role) ? 'active' : '';
    echo '<a href="' . $url . '" class="filter-pill ' . $active_class . '">' . htmlspecialchars(ucfirst(strtolower($role))) . '</a>';
}
echo '</div>';

echo '</div>'; // admin-filter-pills
echo '</div>'; // admin-filter-bar

// Results summary and sorting
echo '<div class="results-header">';
echo '<div class="results-summary">';
if (!empty($search_term) || $role_filter !== 'All') {
    echo '<p>Showing ' . $total_users . ' user' . ($total_users !== 1 ? 's' : '') . ' matching your filters</p>';
} else {
    echo '<p>Showing all ' . $total_users . ' user' . ($total_users !== 1 ? 's' : '') . '</p>';
}
echo '</div>';

// Sorting controls
echo '<div class="sort-controls">';
echo '<span class="sort-label">Sort by:</span>';
$sort_options = [
    'created_at' => 'Date Created',
    'full_name' => 'Full Name',
    'username' => 'Username',
    'user_role' => 'Role',
    'ticket_count' => 'Tickets',
    'user_id' => 'User ID'
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

// Users table
if ($result->num_rows > 0) {
    echo '<div class="admin-tickets-table-container">'; // Re-using tickets table style
    echo '<table class="admin-tickets-table">'; // Re-using tickets table style
    
    // Table header
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>User</th>';
    echo '<th>Role</th>';
    echo '<th>Contact</th>';
    echo '<th>Tickets</th>';
    echo '<th>Date Created</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr class="ticket-row">'; // Re-using ticket row style
        
        // User ID
        echo '<td class="ticket-id">#' . $row['user_id'] . '</td>';
        
        // User (Username and Full Name)
        echo '<td class="ticket-title">'; // Re-using ticket-title for consistent styling
        echo '<div class="title-container">';
        echo '<a href="app.php?view=edit_user&id=' . $row['user_id'] . '" class="ticket-title-link">' . htmlspecialchars($row['full_name']) . '</a>';
        echo '<div class="description-preview">Username: ' . htmlspecialchars($row['username']) . '</div>';
        echo '</div>';
        echo '</td>';
        
        // Role
        echo '<td class="status">'; // Re-using status for badge styling
        echo '<span class="role-badge role-' . strtolower($row['user_role']) . '">' . htmlspecialchars(ucfirst(strtolower($row['user_role']))) . '</span>';
        echo '</td>';
        
        // Contact
        echo '<td class="customer-info">'; // Re-using customer-info for styling
        echo '<a href="mailto:' . htmlspecialchars($row['email']) . '" class="email-link">' . htmlspecialchars($row['email']) . '</a>';
        echo '</td>';
        
        // Ticket Count
        echo '<td class="reply-counts">' . $row['ticket_count'] . '</td>';
        
        // Created date
        echo '<td class="created-date">';
        echo '<div class="date-container">';
        echo '<div class="date">' . date('M j, Y', strtotime($row['created_at'])) . '</div>';
        echo '<div class="time">' . date('g:i A', strtotime($row['created_at'])) . '</div>';
        echo '</div>';
        echo '</td>';
        
        // Actions
        echo '<td class="actions">';
        echo '<div class="action-buttons">';
        echo '<a href="app.php?view=edit_user&id=' . $row['user_id'] . '" class="btn-action btn-edit" title="Edit User"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>';
        if ($row['user_id'] != $_SESSION['user_id']) {
            echo '<a href="actions/delete_user.php?id=' . $row['user_id'] . '" class="btn-action btn-delete" title="Delete User" onclick="return confirm(\'Are you sure you want to delete user \''.htmlspecialchars($row['username']).'\'\'? This CANNOT be undone.\');"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>';
        }
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Pagination
    if ($total_pages > 1) {
        echo '<div class="admin-pagination">';
        
        $base_params = $_GET;
        unset($base_params['page']);
        $base_url = 'app.php?' . http_build_query($base_params);
        $separator = empty($base_params) ? (strpos($base_url, '?') ? '&' : '?') : '&';

        if ($page > 1) {
            echo '<a href="' . $base_url . $separator . 'page=' . ($page - 1) . '" class="pagination-link">&laquo; Previous</a>';
        }
        
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo '<a href="' . $base_url . $separator . 'page=1" class="pagination-link">1</a>';
            if ($start_page > 2) echo '<span class="pagination-ellipsis">...</span>';
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            echo ($i == $page) 
                ? '<span class="pagination-link active">' . $i . '</span>'
                : '<a href="' . $base_url . $separator . 'page=' . $i . '" class="pagination-link">' . $i . '</a>';
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) echo '<span class="pagination-ellipsis">...</span>';
            echo '<a href="' . $base_url . $separator . 'page=' . $total_pages . '" class="pagination-link">' . $total_pages . '</a>';
        }
        
        if ($page < $total_pages) {
            echo '<a href="' . $base_url . $separator . 'page=' . ($page + 1) . '" class="pagination-link">Next &raquo;</a>';
        }
        
        echo '</div>';
    }
    
} else {
    echo '<div class="no-tickets-admin">'; // Re-using no-tickets style
    echo '<div class="no-results-icon">👥</div>';
    echo '<h3>No users found</h3>';
    if (!empty($search_term) || $role_filter !== 'All') {
        echo '<p>No users match your current search criteria.</p>';
        echo '<p><a href="app.php?view=users" class="btn-secondary">Clear all filters</a></p>';
    } else {
        echo '<p>There are no users in the system yet. <a href="app.php?view=create_user">Add the first one!</a></p>';
    }
    echo '</div>';
}
?>