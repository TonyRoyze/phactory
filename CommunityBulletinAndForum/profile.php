<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Initialize session
initializeSession();

// Require authentication
requireAuth();

$current_user = getCurrentUser();
$errors = [];
$success_message = '';
$is_editing = isset($_GET['edit']) && $_GET['edit'] === '1';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $avatar = sanitizeInput($_POST['avatar'] ?? '');
        
        // Validate bio length
        if (strlen($bio) > 500) {
            $errors[] = 'Bio must be 500 characters or less';
        }
        
        // Validate avatar URL if provided
        if (!empty($avatar) && !filter_var($avatar, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid avatar URL';
        }
        
        // Update profile if no errors
        if (empty($errors)) {
            $db = Database::getInstance();
            $result = $db->execute(
                "UPDATE users SET bio = ?, avatar = ? WHERE id = ?",
                [$bio, $avatar, $current_user['id']],
                'ssi'
            );
            
            if ($result && $result['success']) {
                $success_message = 'Profile updated successfully!';
                // Refresh current user data
                $current_user = getCurrentUser();
                $is_editing = false;
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Get user's posts with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$posts_per_page = 10;
$offset = ($page - 1) * $posts_per_page;

$db = Database::getInstance();

// Get user's posts
$user_posts = $db->select("
    SELECT p.*, 
           c.name as category_name, c.color as category_color, c.icon as category_icon
    FROM posts p
    JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
", [$current_user['id'], $posts_per_page, $offset], 'iii');

// Get total posts count for pagination
$total_posts = $db->selectOne("
    SELECT COUNT(*) as count FROM posts WHERE user_id = ?
", [$current_user['id']], 'i')['count'];

$total_pages = ceil($total_posts / $posts_per_page);

// Get user activity stats
$stats = [
    'total_posts' => $total_posts,
    'total_likes' => $db->selectOne("
        SELECT COUNT(*) as count FROM post_likes pl
        JOIN posts p ON pl.post_id = p.id
        WHERE p.user_id = ?
    ", [$current_user['id']], 'i')['count'],
    'total_comments' => $db->selectOne("
        SELECT COUNT(*) as count FROM comments WHERE user_id = ?
    ", [$current_user['id']], 'i')['count'],
    'member_since' => date('F Y', strtotime($current_user['created_at'] ?? 'now'))
];

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_user['username']); ?>'s Profile - CommunityHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="main-layout">
        <?php renderHeader('profile'); ?>

        <main class="main-content">
            <div class="container">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
                    <!-- Profile Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-user"></i>
                                    Profile
                                </h2>
                                <?php if (!$is_editing): ?>
                                    <a href="profile.php?edit=1" class="btn btn-sm btn-outline">
                                        <i class="fas fa-edit"></i>
                                        Edit Profile
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="profile-info">
                                <div class="profile-avatar">
                                    <?php if (!empty($current_user['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($current_user['avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($current_user['username']); ?>" 
                                             class="avatar-img avatar-lg">
                                    <?php else: ?>
                                        <div class="avatar-placeholder avatar-lg">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="profile-details">
                                    <h3 class="profile-username"><?php echo htmlspecialchars($current_user['username']); ?></h3>
                                    <p class="profile-email"><?php echo htmlspecialchars($current_user['email']); ?></p>
                                    
                                    <?php if (!empty($current_user['bio'])): ?>
                                        <div class="profile-bio">
                                            <p><?php echo nl2br(htmlspecialchars($current_user['bio'])); ?></p>
                                        </div>
                                    <?php elseif (!$is_editing): ?>
                                        <div class="profile-bio-empty">
                                            <p class="text-muted">No bio added yet.</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="profile-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Member since <?php echo htmlspecialchars($stats['member_since']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Last active <?php echo timeAgo($current_user['last_active']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Stats -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-bar"></i>
                                    Activity Stats
                                </h3>
                            </div>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo number_format($stats['total_posts']); ?></div>
                                        <div class="stat-label">Posts</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo number_format($stats['total_likes']); ?></div>
                                        <div class="stat-label">Likes Received</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo number_format($stats['total_comments']); ?></div>
                                        <div class="stat-label">Comments</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        <?php if ($flash): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                                <?php echo htmlspecialchars($flash['message']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($is_editing): ?>
                            <!-- Edit Profile Form -->
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">
                                        <i class="fas fa-edit"></i>
                                        Edit Profile
                                    </h2>
                                </div>
                                
                                <form method="POST" action="profile.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="form-group">
                                        <label for="avatar" class="form-label">
                                            <i class="fas fa-image"></i>
                                            Avatar URL
                                        </label>
                                        <input 
                                            type="url" 
                                            id="avatar" 
                                            name="avatar" 
                                            class="form-input" 
                                            value="<?php echo htmlspecialchars($current_user['avatar'] ?? ''); ?>"
                                            placeholder="https://example.com/your-avatar.jpg"
                                        >
                                        <div class="form-help">Enter a URL to your profile picture</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bio" class="form-label">
                                            <i class="fas fa-align-left"></i>
                                            Bio
                                        </label>
                                        <textarea 
                                            id="bio" 
                                            name="bio" 
                                            class="form-textarea" 
                                            rows="4" 
                                            maxlength="500"
                                            placeholder="Tell us about yourself..."
                                        ><?php echo htmlspecialchars($current_user['bio'] ?? ''); ?></textarea>
                                        <div class="form-help">
                                            <span>Share a bit about yourself with the community</span>
                                            <span id="bio-counter">
                                                <?php echo strlen($current_user['bio'] ?? ''); ?>/500
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                            Save Changes
                                        </button>
                                        <a href="profile.php" class="btn btn-outline">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- User Posts -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-file-alt"></i>
                                    My Posts
                                    <?php if ($total_posts > 0): ?>
                                        <span class="badge"><?php echo number_format($total_posts); ?></span>
                                    <?php endif; ?>
                                </h2>
                                <a href="create-post.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i>
                                    New Post
                                </a>
                            </div>
                            
                            <?php if (empty($user_posts)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <h3 class="empty-title">No posts yet</h3>
                                    <p class="empty-description">
                                        You haven't created any posts yet. Share your thoughts with the community!
                                    </p>
                                    <a href="create-post.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Create Your First Post
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="posts-list">
                                    <?php foreach ($user_posts as $post): ?>
                                        <div class="post-card">
                                            <div class="post-header">
                                                <div class="post-meta">
                                                    <span class="category-badge" style="background-color: <?php echo htmlspecialchars($post['category_color']); ?>">
                                                        <i class="<?php echo htmlspecialchars($post['category_icon']); ?>"></i>
                                                        <?php echo htmlspecialchars($post['category_name']); ?>
                                                    </span>
                                                    <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
                                                </div>
                                                <?php if ($post['is_pinned']): ?>
                                                    <div class="post-pinned">
                                                        <i class="fas fa-thumbtack"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="post-content">
                                                <h3 class="post-title">
                                                    <a href="post.php?id=<?php echo $post['id']; ?>" class="post-link">
                                                        <?php echo htmlspecialchars($post['title']); ?>
                                                    </a>
                                                </h3>
                                                
                                                <?php if (!empty($post['excerpt'])): ?>
                                                    <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="post-engagement">
                                                <div class="engagement-stats">
                                                    <div class="stat-item">
                                                        <i class="fas fa-heart"></i>
                                                        <span><?php echo number_format($post['likes_count']); ?></span>
                                                    </div>
                                                    <div class="stat-item">
                                                        <i class="fas fa-comment"></i>
                                                        <span><?php echo number_format($post['comments_count']); ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="post-actions">
                                                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="pagination-container">
                                        <div class="pagination">
                                            <?php if ($page > 1): ?>
                                                <a href="profile.php?page=<?php echo $page - 1; ?>" class="pagination-btn">
                                                    <i class="fas fa-chevron-left"></i>
                                                    Previous
                                                </a>
                                            <?php endif; ?>
                                            
                                            <div class="pagination-info">
                                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                            </div>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <a href="profile.php?page=<?php echo $page + 1; ?>" class="pagination-btn">
                                                    Next
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Character counter for bio
        const bioTextarea = document.getElementById('bio');
        const bioCounter = document.getElementById('bio-counter');
        
        if (bioTextarea && bioCounter) {
            bioTextarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                bioCounter.textContent = currentLength + '/500';
                
                if (currentLength > 450) {
                    bioCounter.style.color = '#dc3545';
                } else if (currentLength > 400) {
                    bioCounter.style.color = '#fd7e14';
                } else {
                    bioCounter.style.color = '#6c757d';
                }
            });
        }
    </script>
</body>
</html>