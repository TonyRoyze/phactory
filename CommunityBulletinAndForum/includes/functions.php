<?php

require_once 'database.php';

/**
 * Sanitize and validate input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate username format
 */
function validateUsername($username) {
    // Username must be 3-20 characters, alphanumeric and underscores only
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    // Password must be at least 8 characters with at least one letter and one number
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

/**
 * Check if username exists
 */
function usernameExists($username) {
    $db = Database::getInstance();
    $result = $db->selectOne(
        "SELECT id FROM users WHERE username = ?",
        [$username],
        's'
    );
    return $result !== null;
}

/**
 * Check if email exists
 */
function emailExists($email) {
    $db = Database::getInstance();
    $result = $db->selectOne(
        "SELECT id FROM users WHERE email = ?",
        [$email],
        's'
    );
    return $result !== null;
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->selectOne(
        "SELECT id, username, email, avatar, bio, last_active FROM users WHERE id = ?",
        [$_SESSION['user_id']],
        'i'
    );
}

/**
 * Update user last active timestamp
 */
function updateUserActivity($user_id) {
    $db = Database::getInstance();
    $db->execute(
        "UPDATE users SET last_active = NOW() WHERE id = ?",
        [$user_id],
        'i'
    );
}

/**
 * Create new user account
 */
function createUser($username, $email, $password) {
    $db = Database::getInstance();
    
    // Validate input
    if (!validateUsername($username)) {
        return ['success' => false, 'error' => 'Invalid username format'];
    }
    
    if (!validateEmail($email)) {
        return ['success' => false, 'error' => 'Invalid email format'];
    }
    
    if (!validatePassword($password)) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters with letters and numbers'];
    }
    
    // Check if username or email already exists
    if (usernameExists($username)) {
        return ['success' => false, 'error' => 'Username already exists'];
    }
    
    if (emailExists($email)) {
        return ['success' => false, 'error' => 'Email already exists'];
    }
    
    // Hash password and create user
    $hashedPassword = hashPassword($password);
    $result = $db->execute(
        "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
        [$username, $email, $hashedPassword],
        'sss'
    );
    
    if ($result && $result['success']) {
        return ['success' => true, 'user_id' => $result['insert_id']];
    }
    
    return ['success' => false, 'error' => 'Failed to create user account'];
}

/**
 * Authenticate user login
 */
function authenticateUser($username, $password) {
    $db = Database::getInstance();
    
    // Get user by username or email
    $user = $db->selectOne(
        "SELECT id, username, email, password FROM users WHERE username = ? OR email = ?",
        [$username, $username],
        'ss'
    );
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Update last active and return success
    updateUserActivity($user['id']);
    
    return [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ];
}

/**
 * Login user and create session
 */
function loginUser($user_id, $username) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return true;
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    return true;
}

/**
 * Get categories with post counts and latest post info
 */
function getCategories() {
    $db = Database::getInstance();
    return $db->select("
        SELECT c.*, 
               COUNT(p.id) as actual_post_count,
               MAX(p.created_at) as latest_post_date,
               (SELECT p2.title FROM posts p2 WHERE p2.category_id = c.id ORDER BY p2.created_at DESC LIMIT 1) as latest_post_title
        FROM categories c 
        LEFT JOIN posts p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.id
    ");
}

/**
 * Get recent posts with user and category information
 */
function getRecentPosts($limit = 10) {
    $db = Database::getInstance();
    return $db->select("
        SELECT p.*, 
               u.username, u.avatar,
               c.name as category_name, c.color as category_color
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
        LIMIT ?
    ", [$limit], 'i');
}

/**
 * Get trending topics
 */
function getTrendingTopics($limit = 10) {
    $db = Database::getInstance();
    return $db->select("
        SELECT * FROM trending_topics 
        ORDER BY post_count DESC, last_updated DESC 
        LIMIT ?
    ", [$limit], 'i');
}

/**
 * Extract hashtags from text content
 */
function extractHashtags($content) {
    preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $matches);
    return array_unique($matches[1]);
}

/**
 * Update trending topics based on post content
 */
function updateTrendingTopics($content) {
    $hashtags = extractHashtags($content);
    
    if (empty($hashtags)) {
        return;
    }
    
    $db = Database::getInstance();
    
    foreach ($hashtags as $hashtag) {
        // Insert or update trending topic
        $db->execute("
            INSERT INTO trending_topics (topic, post_count, last_updated) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
            post_count = post_count + 1, 
            last_updated = NOW()
        ", [$hashtag], 's');
    }
}

/**
 * Get posts by trending topic
 */
function getPostsByTopic($topic, $limit = 20) {
    $db = Database::getInstance();
    return $db->select("
        SELECT p.*, 
               u.username, u.avatar,
               c.name as category_name, c.color as category_color
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.content LIKE ? OR p.title LIKE ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ", ['%#' . $topic . '%', '%#' . $topic . '%', $limit], 'ssi');
}

/**
 * Get upcoming events
 */
function getUpcomingEvents($limit = 5) {
    $db = Database::getInstance();
    return $db->select("
        SELECT e.*, u.username as created_by_name
        FROM events e
        JOIN users u ON e.created_by = u.id
        WHERE e.event_date > NOW()
        ORDER BY e.event_date ASC
        LIMIT ?
    ", [$limit], 'i');
}

/**
 * Get community statistics
 */
function getCommunityStats() {
    $db = Database::getInstance();
    
    // Total members
    $total_members = $db->selectOne("SELECT COUNT(*) as count FROM users")['count'];
    
    // Posts today
    $posts_today = $db->selectOne("
        SELECT COUNT(*) as count FROM posts 
        WHERE DATE(created_at) = CURDATE()
    ")['count'];
    
    // Active users (users who were active in the last 24 hours)
    $active_users = $db->selectOne("
        SELECT COUNT(*) as count FROM users 
        WHERE last_active > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ")['count'];
    
    return [
        'total_members' => $total_members,
        'posts_today' => $posts_today,
        'active_users' => $active_users
    ];
}

/**
 * Create post excerpt from content
 */
function createExcerpt($content, $length = 150) {
    $content = strip_tags($content);
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . '...';
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Get post by ID with full details
 */
function getPostById($post_id) {
    $db = Database::getInstance();
    return $db->selectOne("
        SELECT p.*, 
               u.username, u.avatar, u.bio,
               c.name as category_name, c.color as category_color, c.icon as category_icon
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ", [$post_id], 'i');
}

/**
 * Get comments for a post
 */
function getPostComments($post_id) {
    $db = Database::getInstance();
    return $db->select("
        SELECT c.*, u.username, u.avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ", [$post_id], 'i');
}

/**
 * Check if user has liked a post
 */
function hasUserLikedPost($user_id, $post_id) {
    if (!$user_id) return false;
    
    $db = Database::getInstance();
    $result = $db->selectOne(
        "SELECT id FROM post_likes WHERE user_id = ? AND post_id = ?",
        [$user_id, $post_id],
        'ii'
    );
    return $result !== null;
}

/**
 * Require authentication - redirect if not logged in
 */
function requireAuth($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        redirect($redirect_url, 'Please log in to access this page', 'warning');
    }
}

/**
 * Check session validity and auto-logout if expired
 */
function checkSessionValidity() {
    if (isLoggedIn()) {
        // Check if session has expired
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            logoutUser();
            redirect('login.php', 'Your session has expired. Please log in again.', 'warning');
        }
        
        // Update user activity if logged in
        updateUserActivity($_SESSION['user_id']);
    }
}

/**
 * Initialize session and check validity
 */
function initializeSession() {
    // Session is already started in config.php
    checkSessionValidity();
}

/**
 * Render site navigation
 */
function renderNavigation($current_page = '') {
    $current_user = getCurrentUser();
    $nav_items = [];
    
    // Always show home link
    $nav_items[] = [
        'url' => 'index.php',
        'text' => 'Home',
        'active' => $current_page === 'home' || $current_page === ''
    ];
    
    if ($current_user) {
        // User is logged in
        $nav_items[] = [
            'url' => 'profile.php',
            'text' => 'Profile',
            'active' => $current_page === 'profile'
        ];
        $nav_items[] = [
            'url' => 'create-post.php',
            'text' => 'New Post',
            'active' => $current_page === 'create-post'
        ];
        $nav_items[] = [
            'url' => '#',
            'text' => 'Welcome, ' . htmlspecialchars($current_user['username']),
            'class' => 'user-greeting'
        ];
        $nav_items[] = [
            'url' => 'logout.php',
            'text' => 'Logout',
            'active' => $current_page === 'logout'
        ];
    } else {
        // User is not logged in
        $nav_items[] = [
            'url' => 'login.php',
            'text' => 'Login',
            'active' => $current_page === 'login'
        ];
        $nav_items[] = [
            'url' => 'register.php',
            'text' => 'Register',
            'active' => $current_page === 'register'
        ];
    }
    
    return $nav_items;
}

/**
 * Build forum URL with parameters
 */
function buildForumUrl($category_id = 0, $topic = '', $sort = 'newest', $page = 1) {
    $params = [];
    
    if ($category_id > 0) {
        $params['category'] = $category_id;
    }
    
    if (!empty($topic)) {
        $params['topic'] = $topic;
    }
    
    if ($sort !== 'newest') {
        $params['sort'] = $sort;
    }
    
    if ($page > 1) {
        $params['page'] = $page;
    }
    
    $url = 'forum.php';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Get posts for category with pagination and sorting
 */
function getCategoryPosts($category_id, $sort = 'newest', $page = 1, $posts_per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $posts_per_page;
    
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
    
    // Get total count
    $total = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM posts p 
        WHERE p.category_id = ?
    ", [$category_id], 'i')['count'];
    
    // Get posts
    $posts = $db->select("
        SELECT p.*, 
               u.username, u.avatar,
               c.name as category_name, c.color as category_color
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ?
        $sort_clause
        LIMIT ? OFFSET ?
    ", [$category_id, $posts_per_page, $offset], 'iii');
    
    return [
        'posts' => $posts,
        'total' => $total,
        'total_pages' => ceil($total / $posts_per_page),
        'current_page' => $page,
        'has_prev' => $page > 1,
        'has_next' => $page < ceil($total / $posts_per_page)
    ];
}

/**
 * Get posts by topic with pagination and sorting
 */
function getPostsByTopicPaginated($topic, $sort = 'newest', $page = 1, $posts_per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $posts_per_page;
    
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
    
    // Get total count
    $total = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM posts p 
        WHERE p.content LIKE ? OR p.title LIKE ?
    ", ['%#' . $topic . '%', '%#' . $topic . '%'], 'ss')['count'];
    
    // Get posts
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
    
    return [
        'posts' => $posts,
        'total' => $total,
        'total_pages' => ceil($total / $posts_per_page),
        'current_page' => $page,
        'has_prev' => $page > 1,
        'has_next' => $page < ceil($total / $posts_per_page)
    ];
}

/**
 * Render site header HTML
 */
function renderHeader($current_page = '', $title = 'CommunityHub') {
    $nav_items = renderNavigation($current_page);
    $current_user = getCurrentUser();
    
    echo '<header class="site-header">';
    echo '<div class="container">';
    
    // Logo and mobile menu button
    echo '<div class="header-left">';
    echo '<a href="index.php" class="site-logo">' . htmlspecialchars($title) . '</a>';
    echo '</div>';
    
    // Desktop navigation
    echo '<nav class="site-nav desktop-nav">';
    foreach ($nav_items as $item) {
        $class = '';
        if (isset($item['active']) && $item['active']) {
            $class .= ' active';
        }
        if (isset($item['class'])) {
            $class .= ' ' . $item['class'];
        }
        
        if ($item['url'] === '#') {
            echo '<span class="nav-item' . $class . '">' . $item['text'] . '</span>';
        } else {
            echo '<a href="' . htmlspecialchars($item['url']) . '" class="nav-item' . $class . '">' . $item['text'] . '</a>';
        }
    }
    echo '</nav>';
    
    // Mobile menu button and search
    echo '<div class="header-right">';
    echo '<div class="search-container mobile-hidden">';
    echo '<input type="text" id="search-input" class="search-input" placeholder="Search posts, users...">';
    echo '<button class="search-btn" type="button"><i class="fas fa-search"></i></button>';
    echo '<div id="search-results" class="search-results"></div>';
    echo '</div>';
    
    echo '<button class="mobile-menu-toggle" aria-label="Toggle mobile menu" aria-expanded="false">';
    echo '<span class="hamburger-line"></span>';
    echo '<span class="hamburger-line"></span>';
    echo '<span class="hamburger-line"></span>';
    echo '</button>';
    echo '</div>';
    
    // Mobile navigation overlay
    echo '<div class="mobile-nav-overlay"></div>';
    echo '<nav class="mobile-nav">';
    echo '<div class="mobile-nav-header">';
    echo '<span class="mobile-nav-title">' . htmlspecialchars($title) . '</span>';
    echo '<button class="mobile-nav-close" aria-label="Close mobile menu">';
    echo '<i class="fas fa-times"></i>';
    echo '</button>';
    echo '</div>';
    
    // Mobile search
    echo '<div class="mobile-search">';
    echo '<input type="text" class="mobile-search-input" placeholder="Search...">';
    echo '<button class="mobile-search-btn" type="button"><i class="fas fa-search"></i></button>';
    echo '</div>';
    
    // Mobile navigation items
    echo '<div class="mobile-nav-items">';
    foreach ($nav_items as $item) {
        $class = 'mobile-nav-item';
        if (isset($item['active']) && $item['active']) {
            $class .= ' active';
        }
        if (isset($item['class'])) {
            $class .= ' ' . $item['class'];
        }
        
        if ($item['url'] === '#') {
            echo '<span class="' . $class . '">' . $item['text'] . '</span>';
        } else {
            echo '<a href="' . htmlspecialchars($item['url']) . '" class="' . $class . '">' . $item['text'] . '</a>';
        }
    }
    echo '</div>';
    
    // Mobile user info (if logged in)
    if ($current_user) {
        echo '<div class="mobile-user-info">';
        echo '<div class="mobile-user-avatar">';
        echo '<i class="fas fa-user-circle"></i>';
        echo '</div>';
        echo '<div class="mobile-user-details">';
        echo '<span class="mobile-username">' . htmlspecialchars($current_user['username']) . '</span>';
        echo '<span class="mobile-user-email">' . htmlspecialchars($current_user['email']) . '</span>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</nav>';
    
    echo '</div>';
    echo '</header>';
}

?>/**
 * Get
 user posts with pagination
 */
function getUserPosts($user_id, $page = 1, $posts_per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $posts_per_page;
    
    return $db->select("
        SELECT p.*, 
               c.name as category_name, c.color as category_color, c.icon as category_icon
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ", [$user_id, $posts_per_page, $offset], 'iii');
}

/**
 * Get user activity statistics
 */
function getUserActivityStats($user_id) {
    $db = Database::getInstance();
    
    // Total posts
    $total_posts = $db->selectOne("
        SELECT COUNT(*) as count FROM posts WHERE user_id = ?
    ", [$user_id], 'i')['count'];
    
    // Total likes received on user's posts
    $total_likes = $db->selectOne("
        SELECT COUNT(*) as count FROM post_likes pl
        JOIN posts p ON pl.post_id = p.id
        WHERE p.user_id = ?
    ", [$user_id], 'i')['count'];
    
    // Total comments made by user
    $total_comments = $db->selectOne("
        SELECT COUNT(*) as count FROM comments WHERE user_id = ?
    ", [$user_id], 'i')['count'];
    
    // Recent activity (posts in last 30 days)
    $recent_posts = $db->selectOne("
        SELECT COUNT(*) as count FROM posts 
        WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ", [$user_id], 'i')['count'];
    
    return [
        'total_posts' => $total_posts,
        'total_likes' => $total_likes,
        'total_comments' => $total_comments,
        'recent_posts' => $recent_posts
    ];
}

/**
 * Update user profile
 */
function updateUserProfile($user_id, $bio, $avatar = null) {
    $db = Database::getInstance();
    
    // Validate bio length
    if (strlen($bio) > 500) {
        return ['success' => false, 'error' => 'Bio must be 500 characters or less'];
    }
    
    // Validate avatar URL if provided
    if (!empty($avatar) && !filter_var($avatar, FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Please enter a valid avatar URL'];
    }
    
    $result = $db->execute(
        "UPDATE users SET bio = ?, avatar = ? WHERE id = ?",
        [$bio, $avatar, $user_id],
        'ssi'
    );
    
    if ($result && $result['success']) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to update profile'];
}

/**
 * Get user by ID with full profile information
 */
function getUserById($user_id) {
    $db = Database::getInstance();
    return $db->selectOne(
        "SELECT id, username, email, avatar, bio, last_active, created_at FROM users WHERE id = ?",
        [$user_id],
        'i'
    );
}

/**
 * Get user's recent activity for profile display
 */
function getUserRecentActivity($user_id, $limit = 10) {
    $db = Database::getInstance();
    
    // Get recent posts
    $recent_posts = $db->select("
        SELECT 'post' as type, p.id, p.title as content, p.created_at,
               c.name as category_name, c.color as category_color
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ", [$user_id, $limit], 'ii');
    
    // Get recent comments
    $recent_comments = $db->select("
        SELECT 'comment' as type, c.id, 
               CONCAT('Commented on \"', p.title, '\"') as content, 
               c.created_at,
               cat.name as category_name, cat.color as category_color
        FROM comments c
        JOIN posts p ON c.post_id = p.id
        JOIN categories cat ON p.category_id = cat.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT ?
    ", [$user_id, $limit], 'ii');
    
    // Combine and sort activities
    $activities = array_merge($recent_posts, $recent_comments);
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($activities, 0, $limit);
}

/**
 * Check if user can edit profile (for future role-based permissions)
 */
function canEditProfile($user_id, $profile_user_id) {
    // For now, users can only edit their own profiles
    return $user_id === $profile_user_id;
}

/**
 * Get user's favorite categories based on post activity
 */
function getUserFavoriteCategories($user_id, $limit = 3) {
    $db = Database::getInstance();
    
    return $db->select("
        SELECT c.name, c.icon, c.color, COUNT(p.id) as post_count
        FROM categories c
        JOIN posts p ON c.id = p.category_id
        WHERE p.user_id = ?
        GROUP BY c.id, c.name, c.icon, c.color
        ORDER BY post_count DESC
        LIMIT ?
    ", [$user_id, $limit], 'ii');
}

/**
 * Format user join date
 */
function formatJoinDate($created_at) {
    $date = new DateTime($created_at);
    return $date->format('F Y');
}

/**
 * Get user engagement rate (likes per post average)
 */
function getUserEngagementRate($user_id) {
    $db = Database::getInstance();
    
    $stats = $db->selectOne("
        SELECT 
            COUNT(p.id) as total_posts,
            COALESCE(SUM(p.likes_count), 0) as total_likes
        FROM posts p
        WHERE p.user_id = ?
    ", [$user_id], 'i');
    
    if ($stats['total_posts'] > 0) {
        return round($stats['total_likes'] / $stats['total_posts'], 1);
    }
    
    return 0;
}

/**
 * Perform comprehensive search across posts and users
 */
function performSearch($query, $type = 'all', $category_id = 0, $sort = 'relevance', $page = 1, $per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    $search_term = '%' . $query . '%';
    $results = [];
    $total = 0;
    
    // Build WHERE clauses based on filters
    $category_filter = '';
    $params = [];
    $types = '';
    
    if ($category_id > 0) {
        $category_filter = ' AND p.category_id = ?';
    }
    
    // Search posts
    if ($type === 'all' || $type === 'posts') {
        $post_query = "
            SELECT 'post' as type, p.id, p.title, p.content, p.excerpt, p.created_at, 
                   p.likes_count, p.comments_count,
                   u.username, u.avatar,
                   c.name as category_name, c.color as category_color,
                   MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN categories c ON p.category_id = c.id
            WHERE (p.title LIKE ? OR p.content LIKE ? OR MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE))
            $category_filter
        ";
        
        $post_params = [$query, $search_term, $search_term, $query];
        $post_types = 'ssss';
        
        if ($category_id > 0) {
            $post_params[] = $category_id;
            $post_types .= 'i';
        }
        
        // Add sorting
        switch ($sort) {
            case 'newest':
                $post_query .= " ORDER BY p.created_at DESC";
                break;
            case 'oldest':
                $post_query .= " ORDER BY p.created_at ASC";
                break;
            case 'most_liked':
                $post_query .= " ORDER BY p.likes_count DESC, p.created_at DESC";
                break;
            case 'most_commented':
                $post_query .= " ORDER BY p.comments_count DESC, p.created_at DESC";
                break;
            case 'relevance':
            default:
                $post_query .= " ORDER BY relevance_score DESC, p.created_at DESC";
                break;
        }
        
        if ($type === 'posts') {
            $post_query .= " LIMIT ? OFFSET ?";
            $post_params[] = $per_page;
            $post_params[] = $offset;
            $post_types .= 'ii';
        }
        
        $post_results = $db->select($post_query, $post_params, $post_types);
        
        // Format post results
        foreach ($post_results as $post) {
            $results[] = [
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
        }
    }
    
    // Search users (only if not filtering by category)
    if (($type === 'all' || $type === 'users') && $category_id === 0) {
        $user_query = "
            SELECT 'user' as type, u.id, u.username, u.bio, u.created_at,
                   COUNT(p.id) as post_count
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.username LIKE ? OR u.bio LIKE ?
            GROUP BY u.id, u.username, u.bio, u.created_at
            ORDER BY u.username ASC
        ";
        
        $user_params = [$search_term, $search_term];
        $user_types = 'ss';
        
        if ($type === 'users') {
            $user_query .= " LIMIT ? OFFSET ?";
            $user_params[] = $per_page;
            $user_params[] = $offset;
            $user_types .= 'ii';
        }
        
        $user_results = $db->select($user_query, $user_params, $user_types);
        
        // Format user results
        foreach ($user_results as $user) {
            $results[] = [
                'type' => 'user',
                'id' => $user['id'],
                'username' => $user['username'],
                'bio' => $user['bio'],
                'created_at' => $user['created_at'],
                'post_count' => $user['post_count'],
                'url' => 'profile.php?id=' . $user['id'],
                'relevance_score' => 0
            ];
        }
    }
    
    // Sort combined results by relevance if needed
    if ($type === 'all' && $sort === 'relevance') {
        usort($results, function($a, $b) {
            if ($a['relevance_score'] === $b['relevance_score']) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            }
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM (";
    $count_params = [];
    $count_types = '';
    
    if ($type === 'all' || $type === 'posts') {
        $count_query .= "
            SELECT p.id FROM posts p
            JOIN categories c ON p.category_id = c.id
            WHERE (p.title LIKE ? OR p.content LIKE ? OR MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE))
            $category_filter
        ";
        $count_params = array_merge($count_params, [$search_term, $search_term, $query]);
        $count_types .= 'sss';
        
        if ($category_id > 0) {
            $count_params[] = $category_id;
            $count_types .= 'i';
        }
    }
    
    if ($type === 'all') {
        $count_query .= " UNION ";
    }
    
    if (($type === 'all' || $type === 'users') && $category_id === 0) {
        $count_query .= "
            SELECT u.id FROM users u
            WHERE u.username LIKE ? OR u.bio LIKE ?
        ";
        $count_params = array_merge($count_params, [$search_term, $search_term]);
        $count_types .= 'ss';
    }
    
    $count_query .= ") as combined_results";
    
    $total_result = $db->selectOne($count_query, $count_params, $count_types);
    $total = $total_result ? $total_result['total'] : 0;
    
    // Apply pagination to combined results
    if ($type === 'all') {
        $results = array_slice($results, $offset, $per_page);
    }
    
    return [
        'items' => $results,
        'total' => $total,
        'total_pages' => ceil($total / $per_page),
        'current_page' => $page,
        'has_prev' => $page > 1,
        'has_next' => $page < ceil($total / $per_page)
    ];
}

/**
 * Get search suggestions for autocomplete
 */
function getSearchSuggestions($query, $limit = 8) {
    $db = Database::getInstance();
    $search_term = $query . '%';
    $suggestions = [];
    
    // Get post title suggestions
    $post_suggestions = $db->select("
        SELECT DISTINCT p.title as suggestion, 'post' as type, COUNT(*) as frequency
        FROM posts p
        WHERE p.title LIKE ?
        GROUP BY p.title
        ORDER BY frequency DESC, p.title ASC
        LIMIT ?
    ", [$search_term, $limit], 'si');
    
    foreach ($post_suggestions as $suggestion) {
        $suggestions[] = [
            'text' => $suggestion['suggestion'],
            'type' => 'post',
            'icon' => 'fas fa-file-alt'
        ];
    }
    
    // Get user suggestions
    if (count($suggestions) < $limit) {
        $remaining = $limit - count($suggestions);
        $user_suggestions = $db->select("
            SELECT username as suggestion, 'user' as type
            FROM users
            WHERE username LIKE ?
            ORDER BY username ASC
            LIMIT ?
        ", [$search_term, $remaining], 'si');
        
        foreach ($user_suggestions as $suggestion) {
            $suggestions[] = [
                'text' => $suggestion['suggestion'],
                'type' => 'user',
                'icon' => 'fas fa-user'
            ];
        }
    }
    
    // Get trending topic suggestions
    if (count($suggestions) < $limit) {
        $remaining = $limit - count($suggestions);
        $topic_suggestions = $db->select("
            SELECT topic as suggestion, 'topic' as type
            FROM trending_topics
            WHERE topic LIKE ?
            ORDER BY post_count DESC
            LIMIT ?
        ", [$search_term, $remaining], 'si');
        
        foreach ($topic_suggestions as $suggestion) {
            $suggestions[] = [
                'text' => '#' . $suggestion['suggestion'],
                'type' => 'topic',
                'icon' => 'fas fa-hashtag'
            ];
        }
    }
    
    return $suggestions;
}

/**
 * Highlight search terms in text
 */
function highlightSearchTerms($text, $query) {
    if (empty($query) || empty($text)) {
        return htmlspecialchars($text);
    }
    
    $text = htmlspecialchars($text);
    $terms = explode(' ', $query);
    
    foreach ($terms as $term) {
        $term = trim($term);
        if (strlen($term) >= 2) {
            $text = preg_replace(
                '/(' . preg_quote($term, '/') . ')/i',
                '<mark class="search-highlight">$1</mark>',
                $text
            );
        }
    }
    
    return $text;
}

/**
 * Build search URL with parameters
 */
function buildSearchUrl($query, $type = 'all', $category = 0, $sort = 'relevance', $page = 1) {
    $params = ['q' => $query];
    
    if ($type !== 'all') {
        $params['type'] = $type;
    }
    
    if ($category > 0) {
        $params['category'] = $category;
    }
    
    if ($sort !== 'relevance') {
        $params['sort'] = $sort;
    }
    
    if ($page > 1) {
        $params['page'] = $page;
    }
    
    return 'search.php?' . http_build_query($params);
}
/**

 * Enhanced Security Functions
 */

/**
 * Enhanced input sanitization with XSS prevention
 */
function sanitizeInputAdvanced($data, $allow_html = false) {
    if (is_array($data)) {
        return array_map(function($item) use ($allow_html) {
            return sanitizeInputAdvanced($item, $allow_html);
        }, $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    
    if (!$allow_html) {
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else {
        // Allow limited HTML tags but sanitize dangerous content
        $allowed_tags = '<p><br><strong><em><u><a><ul><ol><li><blockquote><h3><h4><h5><h6>';
        $data = strip_tags($data, $allowed_tags);
        
        // Remove dangerous attributes
        $data = preg_replace('/(<[^>]+)\s+(on\w+|javascript:|vbscript:|data:)[^>]*>/i', '$1>', $data);
    }
    
    return $data;
}

/**
 * Validate and sanitize file uploads
 */
function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file was uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'File is too large'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File is too large'];
    }
    
    // Check file type
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);
    
    $allowed_mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_types) || 
        !isset($allowed_mime_types[$extension]) || 
        $mime_type !== $allowed_mime_types[$extension]) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    return ['success' => true, 'extension' => $extension, 'mime_type' => $mime_type];
}

/**
 * Rate limiting for API endpoints
 */
function checkRateLimit($action, $identifier = null, $max_attempts = 10, $time_window = 300) {
    if (!$identifier) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    $cache_file = sys_get_temp_dir() . '/' . md5($key) . '.cache';
    
    $now = time();
    $attempts = [];
    
    // Load existing attempts
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && isset($data['attempts'])) {
            $attempts = $data['attempts'];
        }
    }
    
    // Remove old attempts outside time window
    $attempts = array_filter($attempts, function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $max_attempts) {
        $oldest_attempt = min($attempts);
        $reset_time = $oldest_attempt + $time_window;
        return [
            'allowed' => false,
            'reset_time' => $reset_time,
            'attempts' => count($attempts)
        ];
    }
    
    // Add current attempt
    $attempts[] = $now;
    
    // Save attempts
    file_put_contents($cache_file, json_encode(['attempts' => $attempts]));
    
    return [
        'allowed' => true,
        'attempts' => count($attempts),
        'remaining' => $max_attempts - count($attempts)
    ];
}

/**
 * Log security events
 */
function logSecurityEvent($event_type, $details = [], $severity = 'INFO') {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'severity' => $severity,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'details' => $details
    ];
    
    $log_line = json_encode($log_entry) . PHP_EOL;
    
    // Log to security log file
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    
    // Also log to PHP error log for critical events
    if (in_array($severity, ['ERROR', 'CRITICAL'])) {
        error_log("Security Event [{$severity}]: {$event_type} - " . json_encode($details));
    }
}

/**
 * Detect and prevent common attack patterns
 */
function detectAttackPatterns($input) {
    $attack_patterns = [
        'sql_injection' => [
            '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bunion\b)/i',
            '/\b(select|insert|update|delete|drop|create|alter)\b.*\b(from|into|table|database)\b/i',
            '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i',
            '/\'\s*(or|and)\s*\'\w*\'\s*=\s*\'\w*\'/i'
        ],
        'xss' => [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i'
        ],
        'path_traversal' => [
            '/\.\.[\/\\\\]/i',
            '/\.(exe|bat|cmd|com|pif|scr|vbs|js)$/i'
        ],
        'command_injection' => [
            '/[;&|`$(){}[\]]/i',
            '/\b(cat|ls|pwd|id|whoami|uname|wget|curl|nc|netcat)\b/i'
        ]
    ];
    
    $detected_attacks = [];
    
    foreach ($attack_patterns as $attack_type => $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $detected_attacks[] = $attack_type;
                break;
            }
        }
    }
    
    if (!empty($detected_attacks)) {
        logSecurityEvent('attack_pattern_detected', [
            'attack_types' => $detected_attacks,
            'input_sample' => substr($input, 0, 200)
        ], 'WARNING');
    }
    
    return $detected_attacks;
}

/**
 * Enhanced CSRF token generation with expiration
 */
function generateCSRFTokenAdvanced($expiry_minutes = 60) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + ($expiry_minutes * 60);
    
    $_SESSION['csrf_tokens'][$token] = $expiry;
    
    // Clean up expired tokens
    if (isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = array_filter($_SESSION['csrf_tokens'], function($exp) {
            return $exp > time();
        });
    }
    
    return $token;
}

/**
 * Enhanced CSRF token verification with expiration check
 */
function verifyCSRFTokenAdvanced($token) {
    if (!isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }
    
    $expiry = $_SESSION['csrf_tokens'][$token];
    
    if ($expiry < time()) {
        // Token expired
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }
    
    // Token is valid, remove it (one-time use)
    unset($_SESSION['csrf_tokens'][$token]);
    return true;
}

/**
 * Content Security Policy header
 */
function setSecurityHeaders() {
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none';");
    
    // X-Frame-Options
    header("X-Frame-Options: DENY");
    
    // X-Content-Type-Options
    header("X-Content-Type-Options: nosniff");
    
    // X-XSS-Protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Strict Transport Security (if using HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

/**
 * Validate session integrity
 */
function validateSessionIntegrity() {
    // Check if session was hijacked
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        logSecurityEvent('session_hijack_attempt', [
            'expected_user_agent' => $_SESSION['user_agent'],
            'actual_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ], 'ERROR');
        
        logoutUser();
        return false;
    }
    
    // Check if session is from different IP (optional, can be problematic with mobile users)
    if (ENABLE_IP_VALIDATION && isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        logSecurityEvent('session_ip_mismatch', [
            'expected_ip' => $_SESSION['ip_address'],
            'actual_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ], 'WARNING');
        
        // Don't automatically logout for IP changes, just log
    }
    
    return true;
}

/**
 * Enhanced login function with security tracking
 */
function loginUserSecure($user_id, $username) {
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Log successful login
    logSecurityEvent('user_login', [
        'user_id' => $user_id,
        'username' => $username
    ], 'INFO');
    
    return true;
}

/**
 * Enhanced logout function with security cleanup
 */
function logoutUserSecure() {
    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? null;
    
    // Log logout
    if ($user_id) {
        logSecurityEvent('user_logout', [
            'user_id' => $user_id,
            'username' => $username
        ], 'INFO');
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    return true;
}

/**
 * Honeypot field validation
 */
function validateHoneypot($field_name = 'website') {
    if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
        logSecurityEvent('honeypot_triggered', [
            'field_name' => $field_name,
            'field_value' => $_POST[$field_name]
        ], 'WARNING');
        return false;
    }
    return true;
}

/**
 * Input validation with attack pattern detection
 */
function validateInputSecurity($input, $field_name = 'unknown') {
    // Check for attack patterns
    $attacks = detectAttackPatterns($input);
    
    if (!empty($attacks)) {
        logSecurityEvent('malicious_input_detected', [
            'field_name' => $field_name,
            'attack_types' => $attacks,
            'input_sample' => substr($input, 0, 100)
        ], 'WARNING');
        return false;
    }
    
    return true;
}

/**
 * Generate secure filename for uploads
 */
function generateSecureFilename($original_filename, $user_id = null) {
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $user_prefix = $user_id ? "user{$user_id}_" : '';
    
    return $user_prefix . $timestamp . '_' . $random . '.' . $extension;
}

/**
 * Clean up old security logs
 */
function cleanupSecurityLogs($days_to_keep = 30) {
    $log_file = __DIR__ . '/../logs/security.log';
    
    if (!file_exists($log_file)) {
        return;
    }
    
    $cutoff_time = time() - ($days_to_keep * 24 * 60 * 60);
    $lines = file($log_file, FILE_IGNORE_NEW_LINES);
    $kept_lines = [];
    
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data && isset($data['timestamp'])) {
            $log_time = strtotime($data['timestamp']);
            if ($log_time > $cutoff_time) {
                $kept_lines[] = $line;
            }
        }
    }
    
    file_put_contents($log_file, implode(PHP_EOL, $kept_lines) . PHP_EOL);
}

// ============================================================================
// PERFORMANCE OPTIMIZATION AND CACHING FUNCTIONS
// ============================================================================

/**
 * Get cached community statistics
 */
function getCachedCommunityStats() {
    global $cache;
    
    return $cache->remember('community_stats', function() {
        $db = Database::getInstance();
        
        // Get total members
        $total_members = $db->count('users');
        
        // Get posts today
        $posts_today = $db->count('posts', 'DATE(created_at) = CURDATE()');
        
        // Get active users (logged in within last 24 hours)
        $active_users = $db->count('users', 'last_active >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');
        
        return [
            'total_members' => $total_members,
            'posts_today' => $posts_today,
            'active_users' => $active_users
        ];
    }, 300); // Cache for 5 minutes
}

/**
 * Get cached recent posts with optimized query
 */
function getCachedRecentPosts($limit = 10) {
    global $cache;
    
    $cache_key = "recent_posts_$limit";
    
    return $cache->remember($cache_key, function() use ($limit) {
        $db = Database::getInstance();
        
        $query = "
            SELECT 
                p.id, p.title, p.excerpt, p.likes_count, p.comments_count, p.created_at,
                u.username, u.avatar,
                c.name as category_name, c.color as category_color
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN categories c ON p.category_id = c.id
            ORDER BY p.is_pinned DESC, p.created_at DESC
            LIMIT ?
        ";
        
        return $db->select($query, [$limit], 'i');
    }, 180); // Cache for 3 minutes
}

/**
 * Get cached trending topics
 */
function getCachedTrendingTopics($limit = 10) {
    global $cache;
    
    $cache_key = "trending_topics_$limit";
    
    return $cache->remember($cache_key, function() use ($limit) {
        $db = Database::getInstance();
        
        $query = "
            SELECT topic, post_count
            FROM trending_topics
            ORDER BY post_count DESC, last_updated DESC
            LIMIT ?
        ";
        
        return $db->select($query, [$limit], 'i');
    }, 600); // Cache for 10 minutes
}

/**
 * Get cached upcoming events
 */
function getCachedUpcomingEvents($limit = 5) {
    global $cache;
    
    $cache_key = "upcoming_events_$limit";
    
    return $cache->remember($cache_key, function() use ($limit) {
        $db = Database::getInstance();
        
        $query = "
            SELECT e.id, e.title, e.event_date, u.username as created_by_name
            FROM events e
            JOIN users u ON e.created_by = u.id
            WHERE e.event_date > NOW()
            ORDER BY e.event_date ASC
            LIMIT ?
        ";
        
        return $db->select($query, [$limit], 'i');
    }, 900); // Cache for 15 minutes
}

/**
 * Get cached category data with post counts
 */
function getCachedCategories() {
    global $cache;
    
    return $cache->remember('categories_with_counts', function() {
        $db = Database::getInstance();
        
        $query = "
            SELECT 
                c.id, c.name, c.description, c.icon, c.color,
                COUNT(p.id) as actual_post_count,
                (SELECT CONCAT(u.username, ' - ', p2.title) 
                 FROM posts p2 
                 JOIN users u ON p2.user_id = u.id 
                 WHERE p2.category_id = c.id 
                 ORDER BY p2.created_at DESC 
                 LIMIT 1) as latest_post
            FROM categories c
            LEFT JOIN posts p ON c.id = p.category_id
            GROUP BY c.id, c.name, c.description, c.icon, c.color
            ORDER BY c.id
        ";
        
        return $db->select($query);
    }, 600); // Cache for 10 minutes
}

/**
 * Get cached posts for a specific category with pagination
 */
function getCachedCategoryPosts($category_id, $page = 1, $per_page = 10, $sort = 'newest') {
    global $cache;
    
    $cache_key = "category_posts_{$category_id}_{$page}_{$per_page}_{$sort}";
    
    return $cache->remember($cache_key, function() use ($category_id, $page, $per_page, $sort) {
        $db = Database::getInstance();
        
        $offset = ($page - 1) * $per_page;
        
        // Determine sort order
        $order_by = match($sort) {
            'likes' => 'p.likes_count DESC, p.created_at DESC',
            'comments' => 'p.comments_count DESC, p.created_at DESC',
            'oldest' => 'p.created_at ASC',
            default => 'p.is_pinned DESC, p.created_at DESC'
        };
        
        $query = "
            SELECT 
                p.id, p.title, p.excerpt, p.likes_count, p.comments_count, 
                p.created_at, p.is_pinned,
                u.username, u.avatar
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.category_id = ?
            ORDER BY $order_by
            LIMIT ? OFFSET ?
        ";
        
        return $db->select($query, [$category_id, $per_page, $offset], 'iii');
    }, 300); // Cache for 5 minutes
}

/**
 * Get cached user profile data
 */
function getCachedUserProfile($user_id) {
    global $cache;
    
    $cache_key = "user_profile_$user_id";
    
    return $cache->remember($cache_key, function() use ($user_id) {
        $db = Database::getInstance();
        
        // Get user data
        $user = $db->selectOne("
            SELECT id, username, email, avatar, bio, created_at, last_active
            FROM users 
            WHERE id = ?
        ", [$user_id], 'i');
        
        if (!$user) {
            return null;
        }
        
        // Get user stats
        $post_count = $db->count('posts', 'user_id = ?', [$user_id], 'i');
        $comment_count = $db->count('comments', 'user_id = ?', [$user_id], 'i');
        $total_likes = $db->selectOne("
            SELECT COALESCE(SUM(likes_count), 0) as total_likes
            FROM posts 
            WHERE user_id = ?
        ", [$user_id], 'i')['total_likes'] ?? 0;
        
        $user['stats'] = [
            'post_count' => $post_count,
            'comment_count' => $comment_count,
            'total_likes' => $total_likes
        ];
        
        return $user;
    }, 600); // Cache for 10 minutes
}

/**
 * Get cached search results
 */
function getCachedSearchResults($query, $type = 'all', $limit = 20) {
    global $cache;
    
    $cache_key = "search_" . md5($query . $type . $limit);
    
    return $cache->remember($cache_key, function() use ($query, $type, $limit) {
        $db = Database::getInstance();
        
        $results = [];
        
        if ($type === 'all' || $type === 'posts') {
            // Search posts using full-text search
            $post_query = "
                SELECT 
                    p.id, p.title, p.excerpt, p.created_at, p.likes_count, p.comments_count,
                    u.username, c.name as category_name, c.color as category_color,
                    MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM posts p
                JOIN users u ON p.user_id = u.id
                JOIN categories c ON p.category_id = c.id
                WHERE MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC, p.created_at DESC
                LIMIT ?
            ";
            
            $results['posts'] = $db->select($post_query, [$query, $query, $limit], 'ssi');
        }
        
        if ($type === 'all' || $type === 'users') {
            // Search users
            $user_query = "
                SELECT 
                    u.id, u.username, u.avatar, u.bio,
                    MATCH(u.username, u.bio) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM users u
                WHERE MATCH(u.username, u.bio) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT ?
            ";
            
            $results['users'] = $db->select($user_query, [$query, $query, $limit], 'ssi');
        }
        
        return $results;
    }, 300); // Cache for 5 minutes
}

/**
 * Invalidate related caches when content changes
 */
function invalidateRelatedCaches($type, $id = null) {
    global $cache;
    
    switch ($type) {
        case 'post':
            // Invalidate recent posts, category posts, and stats
            $cache->delete('recent_posts_10');
            $cache->delete('community_stats');
            if ($id) {
                // Get post to find category
                $db = Database::getInstance();
                $post = $db->selectOne("SELECT category_id FROM posts WHERE id = ?", [$id], 'i');
                if ($post) {
                    // Clear category-specific caches
                    $pattern = "category_posts_{$post['category_id']}_*";
                    // Note: This is a simplified approach. In production, you might want a more sophisticated cache tagging system
                }
            }
            break;
            
        case 'user':
            if ($id) {
                $cache->delete("user_profile_$id");
            }
            $cache->delete('community_stats');
            break;
            
        case 'category':
            $cache->delete('categories_with_counts');
            break;
            
        case 'event':
            $cache->delete('upcoming_events_5');
            break;
            
        case 'trending':
            $cache->delete('trending_topics_10');
            break;
    }
}

/**
 * Optimize database queries by adding query hints
 */
function getOptimizedQuery($base_query, $optimization_type = 'default') {
    switch ($optimization_type) {
        case 'recent_posts':
            // Use index hints for recent posts queries
            return str_replace(
                'FROM posts p',
                'FROM posts p USE INDEX (idx_posts_pinned_created)',
                $base_query
            );
            
        case 'category_posts':
            // Use index hints for category-specific queries
            return str_replace(
                'FROM posts p',
                'FROM posts p USE INDEX (idx_posts_category_pinned_created)',
                $base_query
            );
            
        case 'user_posts':
            // Use index hints for user-specific queries
            return str_replace(
                'FROM posts p',
                'FROM posts p USE INDEX (idx_posts_user_created)',
                $base_query
            );
            
        default:
            return $base_query;
    }
}

/**
 * Batch cache warming for frequently accessed data
 */
function warmCache() {
    // Warm up frequently accessed caches
    getCachedCommunityStats();
    getCachedRecentPosts(10);
    getCachedTrendingTopics(10);
    getCachedUpcomingEvents(5);
    getCachedCategories();
    
    return true;
}

/**
 * Cache maintenance function to be called periodically
 */
function performCacheMaintenance() {
    global $cache;
    
    // Clean up expired cache entries
    $cleaned = $cache->cleanup();
    
    // Log cache maintenance
    error_log("Cache maintenance completed. Cleaned $cleaned expired entries.");
    
    // Warm up critical caches
    warmCache();
    
    return $cleaned;
}

// ============================================================================
// PERFORMANCE MONITORING FUNCTIONS
// ============================================================================

/**
 * Simple query performance tracker
 */
class QueryPerformanceTracker {
    private static $queries = [];
    private static $enabled = false;
    
    public static function enable() {
        self::$enabled = true;
    }
    
    public static function disable() {
        self::$enabled = false;
    }
    
    public static function startQuery($query) {
        if (!self::$enabled) return null;
        
        $id = uniqid();
        self::$queries[$id] = [
            'query' => $query,
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
        
        return $id;
    }
    
    public static function endQuery($id) {
        if (!self::$enabled || !isset(self::$queries[$id])) return;
        
        self::$queries[$id]['end_time'] = microtime(true);
        self::$queries[$id]['memory_end'] = memory_get_usage();
        self::$queries[$id]['duration'] = self::$queries[$id]['end_time'] - self::$queries[$id]['start_time'];
        self::$queries[$id]['memory_used'] = self::$queries[$id]['memory_end'] - self::$queries[$id]['memory_start'];
    }
    
    public static function getStats() {
        if (!self::$enabled) return null;
        
        $total_time = 0;
        $total_memory = 0;
        $slow_queries = [];
        
        foreach (self::$queries as $query) {
            if (isset($query['duration'])) {
                $total_time += $query['duration'];
                $total_memory += $query['memory_used'];
                
                // Flag queries taking more than 100ms as slow
                if ($query['duration'] > 0.1) {
                    $slow_queries[] = $query;
                }
            }
        }
        
        return [
            'total_queries' => count(self::$queries),
            'total_time' => $total_time,
            'average_time' => count(self::$queries) > 0 ? $total_time / count(self::$queries) : 0,
            'total_memory' => $total_memory,
            'slow_queries' => $slow_queries,
            'queries' => self::$queries
        ];
    }
    
    public static function reset() {
        self::$queries = [];
    }
}

/**
 * Enhanced database class with performance tracking
 */
class PerformanceDatabase extends Database {
    public function select($query, $params = [], $types = '') {
        $tracker_id = QueryPerformanceTracker::startQuery($query);
        $result = parent::select($query, $params, $types);
        QueryPerformanceTracker::endQuery($tracker_id);
        return $result;
    }
    
    public function execute($query, $params = [], $types = '') {
        $tracker_id = QueryPerformanceTracker::startQuery($query);
        $result = parent::execute($query, $params, $types);
        QueryPerformanceTracker::endQuery($tracker_id);
        return $result;
    }
}

/**
 * Page performance tracker
 */
class PagePerformanceTracker {
    private static $start_time;
    private static $start_memory;
    
    public static function start() {
        self::$start_time = microtime(true);
        self::$start_memory = memory_get_usage();
    }
    
    public static function end() {
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        return [
            'execution_time' => $end_time - self::$start_time,
            'memory_used' => $end_memory - self::$start_memory,
            'peak_memory' => memory_get_peak_usage(),
            'queries' => QueryPerformanceTracker::getStats()
        ];
    }
}

/**
 * Log slow queries for analysis
 */
function logSlowQuery($query, $duration, $params = []) {
    $log_file = __DIR__ . '/../logs/slow-queries.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'query' => $query,
        'duration' => $duration,
        'params' => $params,
        'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
    ];
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Get database performance recommendations
 */
function getDatabasePerformanceRecommendations() {
    $db = Database::getInstance();
    $recommendations = [];
    
    try {
        // Check for missing indexes on frequently queried columns
        $tables_to_check = ['posts', 'users', 'comments', 'categories'];
        
        foreach ($tables_to_check as $table) {
            $indexes = $db->select("SHOW INDEX FROM $table");
            $columns = $db->select("SHOW COLUMNS FROM $table");
            
            // Simple heuristic: check if commonly filtered columns have indexes
            $common_filter_columns = [
                'posts' => ['category_id', 'user_id', 'created_at', 'is_pinned'],
                'users' => ['username', 'email', 'last_active'],
                'comments' => ['post_id', 'user_id', 'created_at'],
                'categories' => ['name']
            ];
            
            if (isset($common_filter_columns[$table])) {
                $indexed_columns = array_column($indexes, 'Column_name');
                
                foreach ($common_filter_columns[$table] as $column) {
                    if (!in_array($column, $indexed_columns)) {
                        $recommendations[] = "Consider adding index on $table.$column";
                    }
                }
            }
        }
        
        // Check for tables without primary keys
        foreach ($tables_to_check as $table) {
            $primary_keys = array_filter($indexes, function($index) {
                return $index['Key_name'] === 'PRIMARY';
            });
            
            if (empty($primary_keys)) {
                $recommendations[] = "Table $table should have a primary key";
            }
        }
        
    } catch (Exception $e) {
        $recommendations[] = "Could not analyze database structure: " . $e->getMessage();
    }
    
    return $recommendations;
}