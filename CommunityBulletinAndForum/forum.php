<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Initialize session
initializeSession();

// Get parameters from URL
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$topic = isset($_GET['topic']) ? sanitizeInput($_GET['topic']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Pagination settings
$posts_per_page = 10;
$offset = ($page - 1) * $posts_per_page;

// Validate sort parameter
$valid_sorts = ['newest', 'oldest', 'most_liked', 'most_commented'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'newest';
}

// Build sort clause
$sort_clause = '';
switch ($sort) {
    case 'oldest':
        $sort_clause = 'ORDER BY p.is_pinned DESC, p.created_at ASC';
        break;
    case 'most_liked':
        $sort_clause = 'ORDER BY p.is_pinned DESC, p.likes_count DESC, p.created_at DESC';
        break;
    case 'most_commented':
        $sort_clause = 'ORDER BY p.is_pinned DESC, p.comments_count DESC, p.created_at DESC';
        break;
    case 'newest':
    default:
        $sort_clause = 'ORDER BY p.is_pinned DESC, p.created_at DESC';
        break;
}

// Get category information
$category = null;
$posts = [];
$total_posts = 0;
$page_title = 'Forum';

$db = Database::getInstance();

if ($category_id > 0) {
    $category = $db->selectOne(
        "SELECT * FROM categories WHERE id = ?",
        [$category_id],
        'i'
    );
    
    if ($category) {
        $page_title = $category['name'];
        
        // Get total posts count for pagination
        $total_posts = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM posts p 
            WHERE p.category_id = ?
        ", [$category_id], 'i')['count'];
        
        // Use cached category posts for better performance
        $sort_map = [
            'newest' => 'newest',
            'oldest' => 'oldest', 
            'most_liked' => 'likes',
            'most_commented' => 'comments'
        ];
        $cache_sort = $sort_map[$sort] ?? 'newest';
        
        $posts = getCachedCategoryPosts($category_id, $page, $posts_per_page, $cache_sort);
        
        // Add category info to posts for compatibility
        foreach ($posts as &$post) {
            $post['category_name'] = $category['name'];
            $post['category_color'] = $category['color'];
        }
    }
} elseif ($topic) {
    $page_title = "Topic: #$topic";
    
    // Get total posts count for pagination
    $total_posts = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM posts p 
        WHERE p.content LIKE ? OR p.title LIKE ?
    ", ['%#' . $topic . '%', '%#' . $topic . '%'], 'ss')['count'];
    
    // Get posts for this topic with pagination and sorting
    $posts = $db->select("
        SELECT p.*, 
               u.username, u.avatar,
               c.name as category_name, c.color as category_color
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.content LIKE ? OR p.title LIKE ?
        $sort_clause
        LIMIT ? OFFSET ?
    ", ['%#' . $topic . '%', '%#' . $topic . '%', $posts_per_page, $offset], 'ssii');
} else {
    // Show all posts when no specific category or topic is selected
    $page_title = 'All Posts';
    
    // Get total posts count for pagination
    $total_posts = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM posts p
    ")['count'];
    
    // Get all posts with pagination and sorting
    $posts = $db->select("
        SELECT p.*, 
               u.username, u.avatar,
               c.name as category_name, c.color as category_color
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        $sort_clause
        LIMIT ? OFFSET ?
    ", [$posts_per_page, $offset], 'ii');
}

// Calculate pagination info
$total_pages = ceil($total_posts / $posts_per_page);
$has_prev = $page > 1;
$has_next = $page < $total_pages;

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_title . ' - ' . SITE_DESCRIPTION); ?>">
    <title><?php echo htmlspecialchars($page_title . ' - ' . SITE_NAME); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Site Header -->
    <?php renderHeader('forum'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Breadcrumb Navigation -->
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="index.php">Home</a>
                    </li>
                    <?php if ($category): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </li>
                    <?php elseif ($topic): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            Topic: #<?php echo htmlspecialchars($topic); ?>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            Forum
                        </li>
                    <?php endif; ?>
                </ol>
            </nav>
            
            <!-- Page Header -->
            <?php if ($category): ?>
                <header class="forum-header">
                    <div class="category-header-info">
                        <div class="category-icon-large" style="color: <?php echo htmlspecialchars($category['color']); ?>">
                            <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                        </div>
                        <div class="category-details">
                            <h1 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h1>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="forum-actions">
                            <a href="post.php?action=create&category=<?php echo $category['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                New Post
                            </a>
                        </div>
                    <?php endif; ?>
                </header>
            <?php else: ?>
                <header class="forum-header">
                    <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                </header>
            <?php endif; ?>
            
            <!-- Sorting and Filter Controls -->
            <?php if (!empty($posts) || $total_posts > 0): ?>
                <div class="forum-controls">
                    <div class="forum-info">
                        <span class="posts-count">
                            <?php echo number_format($total_posts); ?> 
                            <?php echo $total_posts === 1 ? 'post' : 'posts'; ?>
                        </span>
                        <?php if ($total_pages > 1): ?>
                            <span class="page-info">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sort-controls">
                        <label for="sort-select" class="sort-label">Sort by:</label>
                        <select id="sort-select" class="sort-select" onchange="changeSortOrder(this.value)">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="most_liked" <?php echo $sort === 'most_liked' ? 'selected' : ''; ?>>Most Liked</option>
                            <option value="most_commented" <?php echo $sort === 'most_commented' ? 'selected' : ''; ?>>Most Commented</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Posts List -->
            <section class="forum-posts" aria-labelledby="posts-heading">
                <h2 id="posts-heading" class="sr-only">Posts</h2>
                
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments empty-icon" aria-hidden="true"></i>
                        <h3 class="empty-title">No posts yet</h3>
                        <?php if ($category): ?>
                            <p class="empty-description">Be the first to start a discussion in <?php echo htmlspecialchars($category['name']); ?>!</p>
                            <?php if (isLoggedIn()): ?>
                                <a href="post.php?action=create&category=<?php echo $category['id']; ?>" class="btn btn-primary">Create First Post</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Post</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="empty-description">No posts found for this topic.</p>
                            <a href="index.php" class="btn btn-primary">Back to Home</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="posts-list">
                        <?php foreach ($posts as $post): ?>
                            <article class="forum-post-card card">
                                <div class="post-header">
                                    <div class="post-author">
                                        <div class="author-avatar avatar">
                                            <?php if ($post['avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($post['avatar']); ?>" 
                                                     alt="<?php echo htmlspecialchars($post['username']); ?>'s avatar"
                                                     class="avatar-img">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="author-info">
                                            <span class="author-name"><?php echo htmlspecialchars($post['username']); ?></span>
                                            <time datetime="<?php echo $post['created_at']; ?>" class="post-time">
                                                <?php echo timeAgo($post['created_at']); ?>
                                            </time>
                                        </div>
                                    </div>
                                    
                                    <?php if ($post['is_pinned']): ?>
                                        <div class="post-pinned">
                                            <i class="fas fa-thumbtack" aria-hidden="true"></i>
                                            <span class="sr-only">Pinned post</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-content">
                                    <h3 class="post-title">
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="post-link">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <p class="post-excerpt">
                                        <?php echo $post['excerpt'] ? htmlspecialchars($post['excerpt']) : createExcerpt($post['content']); ?>
                                    </p>
                                </div>
                                
                                <div class="post-engagement">
                                    <div class="engagement-stats">
                                        <span class="stat-item">
                                            <i class="fas fa-heart" aria-hidden="true"></i>
                                            <span class="sr-only">Likes:</span>
                                            <?php echo number_format($post['likes_count']); ?>
                                        </span>
                                        <span class="stat-item">
                                            <i class="fas fa-comment" aria-hidden="true"></i>
                                            <span class="sr-only">Comments:</span>
                                            <?php echo number_format($post['comments_count']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="post-actions">
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline">
                                            Read More
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="pagination-nav" aria-label="Posts pagination">
                            <div class="pagination">
                                <?php if ($has_prev): ?>
                                    <a href="<?php echo buildForumUrl($category_id, $topic, $sort, $page - 1); ?>" 
                                       class="pagination-btn pagination-prev" 
                                       aria-label="Go to previous page">
                                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <div class="pagination-pages">
                                    <?php
                                    // Calculate page range to show
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    // Show first page if not in range
                                    if ($start_page > 1): ?>
                                        <a href="<?php echo buildForumUrl($category_id, $topic, $sort, 1); ?>" 
                                           class="pagination-page" aria-label="Go to page 1">1</a>
                                        <?php if ($start_page > 2): ?>
                                            <span class="pagination-ellipsis" aria-hidden="true">...</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <?php if ($i === $page): ?>
                                            <span class="pagination-page pagination-current" aria-current="page" aria-label="Current page, page <?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="<?php echo buildForumUrl($category_id, $topic, $sort, $i); ?>" 
                                               class="pagination-page" aria-label="Go to page <?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php
                                    // Show last page if not in range
                                    if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <span class="pagination-ellipsis" aria-hidden="true">...</span>
                                        <?php endif; ?>
                                        <a href="<?php echo buildForumUrl($category_id, $topic, $sort, $total_pages); ?>" 
                                           class="pagination-page" aria-label="Go to page <?php echo $total_pages; ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($has_next): ?>
                                    <a href="<?php echo buildForumUrl($category_id, $topic, $sort, $page + 1); ?>" 
                                       class="pagination-btn pagination-next" 
                                       aria-label="Go to next page">
                                        Next
                                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pagination-info">
                                Showing <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $posts_per_page, $total_posts)); ?> 
                                of <?php echo number_format($total_posts); ?> posts
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>
    
    <!-- JavaScript -->
    <script src="js/ajax.js"></script>
    <script src="js/mobile-nav.js"></script>
    <script src="js/main.js"></script>
</body>
</html>