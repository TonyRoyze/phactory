<?php
// This file is views/posts.php
require 'connector.php';


// Bulletin Section
$bulletin_sql = "SELECT p.post_id, p.title, p.content, p.created_at, p.author_id, u.user_name FROM posts p JOIN user u ON p.author_id = u.user_id WHERE p.post_type = 'BULLETIN' ORDER BY p.created_at DESC";
$bulletin_result = $conn->query($bulletin_sql);

if ($bulletin_result->num_rows > 0) {
    echo '<div class="bulletin-section">';
    echo '<div class="page-header">';
    echo '<h1>Latest Bulletins</h1>';
    if (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'MEMBER'])) {
        echo '<a href="app.php?view=create_post" class="btn-create">New Post</a>';
    }
    echo '</div>';

    echo '<div class="bulletin-carousel">';
    echo '<div class="carousel-container">';
    echo '<div class="carousel-track">';
    while($bulletin_row = $bulletin_result->fetch_assoc()) {
        echo '<div class="bulletin-card carousel-slide">';
        echo '<h3>' . htmlspecialchars($bulletin_row['title']) . '</h3>';
        echo '<p class="bulletin-meta">By ' . htmlspecialchars($bulletin_row['user_name']) . ' on ' . date('M j, Y', strtotime($bulletin_row['created_at'])) . '</p>';
        echo '<p>' . nl2br(htmlspecialchars(substr($bulletin_row['content'], 0, 100))) . '...</p>';
        echo '<a href="app.php?view=post&id=' . $bulletin_row['post_id'] . '" class="bulletin-link">Read More</a>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '<button class="carousel-btn carousel-prev" onclick="moveCarousel(-1)">‹</button>';
    echo '<button class="carousel-btn carousel-next" onclick="moveCarousel(1)">›</button>';
    echo '<div class="carousel-dots">';
    for ($i = 0; $i < $bulletin_result->num_rows; $i++) {
        echo '<span class="carousel-dot' . ($i === 0 ? ' active' : '') . '" onclick="currentSlide(' . ($i + 1) . ')"></span>';
    }
echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<script>';
    echo 'let currentSlideIndex = 0;';
    echo 'const slides = document.querySelectorAll(".carousel-slide");';
    echo 'const dots = document.querySelectorAll(".carousel-dot");';
    echo 'const totalSlides = slides.length;';
    echo '';
    echo 'function showSlide(index) {';
    echo '    slides.forEach(slide => slide.style.display = "none");';
    echo '    dots.forEach(dot => dot.classList.remove("active"));';
    echo '    slides[index].style.display = "block";';
    echo '    dots[index].classList.add("active");';
    echo '}';
    echo '';
    echo 'function moveCarousel(direction) {';
    echo '    currentSlideIndex += direction;';
    echo '    if (currentSlideIndex >= totalSlides) currentSlideIndex = 0;';
    echo '    if (currentSlideIndex < 0) currentSlideIndex = totalSlides - 1;';
    echo '    showSlide(currentSlideIndex);';
    echo '}';
    echo '';
    echo 'function currentSlide(index) {';
    echo '    currentSlideIndex = index - 1;';
    echo '    showSlide(currentSlideIndex);';
    echo '}';
    echo '';
    echo 'showSlide(0);';
    echo '</script>';
}

echo '<div class="page-header">';
echo '<h1>Posts</h1>';
echo '</div>';


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


// --- Fetch Posts (with filtering) ---
$sql = "SELECT p.post_id, p.title, p.content, p.created_at, p.author_id, u.user_name FROM posts p JOIN user u ON p.author_id = u.user_id";
$params = [];
$types = '';

$where_clauses = [];
$where_clauses[] = "p.post_type != 'BULLETIN'";
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
        echo '<a href="app.php?view=post&id=' . $row['post_id'] . '" class="bulletin-link">View Post</a>';
        // Edit/Delete buttons for author/admin
        if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $row['author_id'] || $_SESSION['user_type'] === 'ADMIN')) {
            echo '<div class="post-actions">';
            echo '<a href="app.php?view=edit_post&id=' . $row['post_id'] . '" class="btn-action btn-edit">';
            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            echo '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>';
            echo '<path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>';
            echo '</svg>';
            echo 'Edit';
            echo '</a>';
            echo '<a href="actions/delete_post.php?id=' . $row['post_id'] . '" class="btn-action btn-delete" onclick="return confirm(\'Are you sure you want to delete this post?\');">';
            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            echo '<polyline points="3,6 5,6 21,6"></polyline>';
            echo '<path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>';
            echo '<line x1="10" y1="11" x2="10" y2="17"></line>';
            echo '<line x1="14" y1="11" x2="14" y2="17"></line>';
            echo '</svg>';
            echo 'Delete';
            echo '</a>';
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p>No posts found.</p>';
}
