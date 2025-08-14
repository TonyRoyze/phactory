<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Initialize session
initializeSession();

// Get current user
$current_user = getCurrentUser();

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    redirect('index.php', 'Post not found.', 'error');
}

// Get post details
$post = getPostById($post_id);

if (!$post) {
    redirect('index.php', 'Post not found.', 'error');
}

// Get comments for this post
$comments = getPostComments($post_id);

// Check if current user has liked this post
$user_has_liked = $current_user ? hasUserLikedPost($current_user['id'], $post_id) : false;

// Handle comment submission
$comment_errors = [];
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$current_user) {
        redirect('login.php', 'Please log in to interact with posts.', 'warning');
    }
    
    if ($_POST['action'] === 'add_comment') {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $comment_errors[] = 'Invalid security token. Please try again.';
        } elseif (!validateHoneypot()) {
            $comment_errors[] = 'Bot detected. Please try again.';
        } else {
            $comment_content = sanitizeInputAdvanced($_POST['comment_content'] ?? '');
            
            // Check for malicious input patterns
            if (!validateInputSecurity($comment_content, 'comment')) {
                $comment_errors[] = 'Invalid content detected in comment';
            }
            
            // Validate comment
            if (empty($comment_content)) {
                $comment_errors[] = 'Comment content is required.';
            } elseif (strlen($comment_content) < 3) {
                $comment_errors[] = 'Comment must be at least 3 characters long.';
            } elseif (strlen($comment_content) > 1000) {
                $comment_errors[] = 'Comment must be less than 1000 characters.';
            }
            
            // If no errors, add the comment
            if (empty($comment_errors)) {
                $db = Database::getInstance();
                
                try {
                    $db->beginTransaction();
                    
                    // Insert comment
                    $result = $db->execute(
                        "INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())",
                        [$post_id, $current_user['id'], $comment_content],
                        'iis'
                    );
                    
                    if ($result && $result['success']) {
                        // Update post comment count
                        $db->execute(
                            "UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?",
                            [$post_id],
                            'i'
                        );
                        
                        $db->commit();
                        
                        // Redirect to avoid resubmission
                        redirect("post.php?id=$post_id#comments", 'Comment added successfully!', 'success');
                    } else {
                        throw new Exception('Failed to add comment');
                    }
                } catch (Exception $e) {
                    $db->rollback();
                    $comment_errors[] = 'An error occurred while adding your comment. Please try again.';
                    error_log("Comment creation error: " . $e->getMessage());
                }
            }
        }
    }
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($post['excerpt'] ?: createExcerpt($post['content'])); ?>">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php renderHeader('post', SITE_NAME); ?>
    
    <main class="main-content">
        <div class="container">
            <!-- Breadcrumb Navigation -->
            <nav class="breadcrumb">
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item">
                        <a href="forum.php?category=<?php echo $post['category_id']; ?>">
                            <?php echo htmlspecialchars($post['category_name']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($post['title']); ?></li>
                </ul>
            </nav>
            
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="main-content">
                    <!-- Post Content -->
                    <article class="post-detail card">
                        <!-- Post Header -->
                        <header class="post-header">
                            <div class="post-author">
                                <div class="author-avatar avatar avatar-lg">
                                    <?php if ($post['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($post['avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['username']); ?>'s avatar"
                                             class="avatar-img">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="author-info">
                                    <h2 class="author-name"><?php echo htmlspecialchars($post['username']); ?></h2>
                                    <?php if ($post['bio']): ?>
                                        <p class="author-bio"><?php echo htmlspecialchars($post['bio']); ?></p>
                                    <?php endif; ?>
                                    <time datetime="<?php echo $post['created_at']; ?>" class="post-time">
                                        Posted <?php echo timeAgo($post['created_at']); ?>
                                    </time>
                                </div>
                            </div>
                            
                            <div class="post-category">
                                <span class="category-badge badge" 
                                      style="background-color: <?php echo htmlspecialchars($post['category_color']); ?>20; color: <?php echo htmlspecialchars($post['category_color']); ?>">
                                    <i class="<?php echo htmlspecialchars($post['category_icon']); ?>"></i>
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </span>
                            </div>
                        </header>
                        
                        <!-- Post Title -->
                        <div class="post-title-section">
                            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                        </div>
                        
                        <!-- Post Content -->
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>
                        
                        <!-- Post Actions -->
                        <div class="post-actions">
                            <div class="engagement-actions">
                                <?php if ($current_user): ?>
                                    <button class="like-btn <?php echo $user_has_liked ? 'liked' : ''; ?>" 
                                            data-post-id="<?php echo $post['id']; ?>"
                                            data-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>">
                                        <i class="fas fa-heart"></i>
                                        <span class="like-count"><?php echo number_format($post['likes_count']); ?></span>
                                        <span class="like-text"><?php echo $user_has_liked ? 'Liked' : 'Like'; ?></span>
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="like-btn">
                                        <i class="fas fa-heart"></i>
                                        <span class="like-count"><?php echo number_format($post['likes_count']); ?></span>
                                        <span class="like-text">Like</span>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="#comments" class="comment-btn">
                                    <i class="fas fa-comment"></i>
                                    <span class="comment-count"><?php echo number_format($post['comments_count']); ?></span>
                                    <span class="comment-text">Comments</span>
                                </a>
                                
                                <button class="share-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-share"></i>
                                    <span class="share-text">Share</span>
                                </button>
                            </div>
                            
                            <?php if ($current_user && $current_user['id'] == $post['user_id']): ?>
                                <div class="author-actions">
                                    <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                    
                    <!-- Comments Section -->
                    <section class="comments-section" id="comments">
                        <header class="comments-header">
                            <h2 class="comments-title">
                                <i class="fas fa-comments"></i>
                                Comments (<?php echo count($comments); ?>)
                            </h2>
                        </header>
                        
                        <!-- Add Comment Form -->
                        <?php if ($current_user): ?>
                            <div class="add-comment-form card">
                                <div class="card-header">
                                    <h3 class="card-title">Add a Comment</h3>
                                </div>
                                
                                <!-- Comment Errors -->
                                <?php if (!empty($comment_errors)): ?>
                                    <div class="alert alert-error">
                                        <ul>
                                            <?php foreach ($comment_errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <form id="comment-form" method="POST" action="post.php?id=<?php echo $post['id']; ?>" data-validate-form>
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="add_comment">
                                    <!-- Honeypot field to catch bots -->
                                    <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off">
                                    
                                    <div class="comment-form-header">
                                        <div class="commenter-avatar avatar">
                                            <?php if ($current_user['avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($current_user['avatar']); ?>" 
                                                     alt="Your avatar" class="avatar-img">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="commenter-info">
                                            <span class="commenter-name"><?php echo htmlspecialchars($current_user['username']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="comment_content" class="sr-only">Comment content</label>
                                        <textarea id="comment_content" 
                                                  name="comment_content" 
                                                  class="form-textarea" 
                                                  rows="4"
                                                  placeholder="Write your comment here..."
                                                  data-validate="required|minlength:3|maxlength:1000|no-script"
                                                  maxlength="1000"><?php echo htmlspecialchars($_POST['comment_content'] ?? ''); ?></textarea>
                                        <div class="form-help">
                                            <span id="comment-counter">0</span>/1000 characters
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" id="comment-submit-btn" class="btn btn-primary">
                                            <span class="btn-text">
                                                <i class="fas fa-paper-plane"></i> Post Comment
                                            </span>
                                            <span class="spinner hidden" id="comment-loading-spinner"></span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="login-prompt card">
                                <div class="card-content">
                                    <p>Please <a href="login.php">log in</a> to add a comment.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Comments List -->
                        <div class="comments-list">
                            <?php if (empty($comments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment-slash empty-icon"></i>
                                    <h3 class="empty-title">No comments yet</h3>
                                    <p class="empty-description">Be the first to share your thoughts!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <article class="comment-item card">
                                        <div class="comment-header">
                                            <div class="comment-author">
                                                <div class="author-avatar avatar">
                                                    <?php if ($comment['avatar']): ?>
                                                        <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" 
                                                             alt="<?php echo htmlspecialchars($comment['username']); ?>'s avatar"
                                                             class="avatar-img">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($comment['username'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="author-info">
                                                    <span class="author-name"><?php echo htmlspecialchars($comment['username']); ?></span>
                                                    <time datetime="<?php echo $comment['created_at']; ?>" class="comment-time">
                                                        <?php echo timeAgo($comment['created_at']); ?>
                                                    </time>
                                                </div>
                                            </div>
                                            
                                            <?php if ($current_user && $current_user['id'] == $comment['user_id']): ?>
                                                <div class="comment-actions">
                                                    <button class="btn btn-outline btn-sm delete-comment-btn" 
                                                            data-comment-id="<?php echo $comment['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="comment-content">
                                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                
                <!-- Sidebar -->
                <aside class="sidebar">
                    <!-- Author Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">About the Author</h3>
                        </div>
                        <div class="card-content">
                            <div class="author-profile">
                                <div class="author-avatar avatar avatar-lg">
                                    <?php if ($post['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($post['avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['username']); ?>'s avatar"
                                             class="avatar-img">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="author-details">
                                    <h4 class="author-name"><?php echo htmlspecialchars($post['username']); ?></h4>
                                    <?php if ($post['bio']): ?>
                                        <p class="author-bio"><?php echo htmlspecialchars($post['bio']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Related Posts -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">More from <?php echo htmlspecialchars($post['category_name']); ?></h3>
                        </div>
                        <div class="card-content">
                            <div id="related-posts">
                                <div class="loading-message">
                                    <i class="fas fa-spinner fa-spin"></i> Loading related posts...
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
    
    <script src="js/validation.js"></script>
    <script src="js/ajax.js"></script>
    <script src="js/mobile-nav.js"></script>
    <script src="js/main.js"></script>
    <script src="js/post.js"></script>
</body>
</html>