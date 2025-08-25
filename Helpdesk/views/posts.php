<?php
// This file is views/posts.php

echo '<div class="page-header">';
echo '<h1>Posts</h1>';
if (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'MEMBER'])) {
    echo '<a href="app.php?view=create_post" class="btn-create">New Post</a>';
}
echo '</div>';

require 'connector.php';

// Fetch categories for filter pills
$categories_sql = "SELECT DISTINCT category FROM posts ORDER BY category ASC";
$categories_result = $conn->query($categories_sql);
$current_category = $_GET['category'] ?? 'All';
$search_term = $_GET['search'] ?? '';

// --- Filter Bar (Search and Categories) ---
echo '<div class="filter-bar">';
// Search Form
echo '<form action="app.php" method="GET" class="search-form">';
echo '    <input type="hidden" name="view" value="posts">';
echo '    <input type="text" name="search" placeholder="Search posts..." value="' . htmlspecialchars($search_term) . '">';
echo '    <button type="submit">Search</button>';
echo '</form>';

// Category Pills
echo '<div class="category-pills">';
echo '<a href="app.php?view=posts" class="' . ($current_category === 'All' ? 'active' : '') . '">All</a>';
if ($categories_result->num_rows > 0) {
    while($cat_row = $categories_result->fetch_assoc()) {
        $category = htmlspecialchars($cat_row['category']);
        echo '<a href="app.php?view=posts&category=' . urlencode($category) . '" class="' . ($current_category === $category ? 'active' : '') . '">' . $category . '</a>';
    }
}
echo '</div>';
echo '</div>';


// --- Fetch Posts (with filtering) ---
$sql = "SELECT p.post_id, p.title, p.content, p.created_at, p.author_id, u.user_name FROM posts p JOIN user u ON p.author_id = u.user_id";
$params = [];
$types = '';

$where_clauses = [];
if ($current_category !== 'All') {
    $where_clauses[] = "p.category = ?";
    $params[] = $current_category;
    $types .= 's';
}
if (!empty($search_term)) {
    $where_clauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="posts-list">';
    while($row = $result->fetch_assoc()) {
        echo '<div class="post-card">';
        echo '<h2>' . htmlspecialchars($row['title']) . '</h2>';
        echo '<p class="post-meta">By ' . htmlspecialchars($row['user_name']) . ' on ' . date('F j, Y', strtotime($row['created_at'])) . '</p>';
        echo '<p>' . nl2br(htmlspecialchars(substr($row['content'], 0, 150))) . '...</p>';
        echo '<a href="app.php?view=post&id=' . $row['post_id'] . '" class="btn-reply">View Post</a>';
        
        // Edit/Delete buttons for author/admin
        if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $row['author_id'] || $_SESSION['user_type'] === 'ADMIN')) {
            echo '<div class="post-actions">';
            echo '<a href="app.php?view=edit_post&id=' . $row['post_id'] . '" class="post-action-btn">Edit</a>';
            echo '<a href="actions/delete_post.php?id=' . $row['post_id'] . '" class="post-action-btn btn-delete" onclick="return confirm(\'Are you sure you want to delete this post?\');">Delete</a>';
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p>No posts found.</p>';
}
