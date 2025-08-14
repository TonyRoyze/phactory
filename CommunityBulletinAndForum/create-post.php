<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Require authentication
requireAuth();

// Initialize session
initializeSession();

// Get current user
$current_user = getCurrentUser();

// Get categories for the form
$categories = getCategories();

// Handle form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif (!validateHoneypot()) {
        $errors[] = 'Bot detected. Please try again.';
    } else {
        // Get form data
        $title = sanitizeInputAdvanced($_POST['title'] ?? '');
        $content = sanitizeInputAdvanced($_POST['content'] ?? '', true); // Allow limited HTML
        $category_id = (int)($_POST['category_id'] ?? 0);
        
        // Check for malicious input patterns
        if (!validateInputSecurity($title, 'title')) {
            $errors[] = 'Invalid content detected in title';
        }
        if (!validateInputSecurity($content, 'content')) {
            $errors[] = 'Invalid content detected in post content';
        }
        
        // Validate form data
        if (empty($title)) {
            $errors[] = 'Post title is required.';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Post title must be less than 255 characters.';
        }
        
        if (empty($content)) {
            $errors[] = 'Post content is required.';
        } elseif (strlen($content) < 10) {
            $errors[] = 'Post content must be at least 10 characters long.';
        }
        
        if ($category_id <= 0) {
            $errors[] = 'Please select a valid category.';
        } else {
            // Verify category exists
            $db = Database::getInstance();
            $category_exists = $db->selectOne(
                "SELECT id FROM categories WHERE id = ?",
                [$category_id],
                'i'
            );
            if (!$category_exists) {
                $errors[] = 'Selected category does not exist.';
            }
        }
        
        // If no errors, create the post
        if (empty($errors)) {
            $db = Database::getInstance();
            
            try {
                $db->beginTransaction();
                
                // Create excerpt from content
                $excerpt = createExcerpt($content, 150);
                
                // Insert post
                $result = $db->execute(
                    "INSERT INTO posts (user_id, category_id, title, content, excerpt, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    [$current_user['id'], $category_id, $title, $content, $excerpt],
                    'iisss'
                );
                
                if ($result && $result['success']) {
                    $post_id = $result['insert_id'];
                    
                    // Update category post count
                    $db->execute(
                        "UPDATE categories SET post_count = post_count + 1 WHERE id = ?",
                        [$category_id],
                        'i'
                    );
                    
                    // Invalidate related caches
                    invalidateRelatedCaches('post', $post_id);
                    
                    // Update trending topics based on content
                    updateTrendingTopics($content);
                    
                    $db->commit();
                    
                    // Redirect to the new post
                    redirect("post.php?id=$post_id", 'Post created successfully!', 'success');
                } else {
                    throw new Exception('Failed to create post');
                }
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'An error occurred while creating the post. Please try again.';
                error_log("Post creation error: " . $e->getMessage());
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
    <title>Create New Post - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php renderHeader('create-post', SITE_NAME); ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Create New Post</h1>
                <p class="page-subtitle">Share your thoughts with the community</p>
            </div>
            
            <!-- Breadcrumb Navigation -->
            <nav class="breadcrumb">
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Create Post</li>
                </ul>
            </nav>
            
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" id="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="main-content">
                    <!-- Post Creation Form -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">New Post</h2>
                            <p class="card-subtitle">Fill out the form below to create your post</p>
                        </div>
                        
                        <form id="create-post-form" method="POST" action="create-post.php" data-validate-form>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <!-- Honeypot field to catch bots -->
                            <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off">
                            
                            <!-- Category Selection -->
                            <div class="form-group">
                                <label for="category_id" class="form-label">
                                    <i class="fas fa-folder"></i> Category *
                                </label>
                                <select id="category_id" name="category_id" class="form-select" data-validate="required|numeric">
                                    <option value="">Select a category...</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Post Title -->
                            <div class="form-group">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i> Post Title *
                                </label>
                                <input type="text" 
                                       id="title" 
                                       name="title" 
                                       class="form-input" 
                                       placeholder="Enter a descriptive title for your post..."
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       data-validate="required|minlength:5|maxlength:255|no-script"
                                       maxlength="255">
                                <div class="form-help">
                                    <span id="title-counter">0</span>/255 characters
                                </div>
                            </div>
                            
                            <!-- Post Content -->
                            <div class="form-group">
                                <label for="content" class="form-label">
                                    <i class="fas fa-edit"></i> Post Content *
                                </label>
                                <textarea id="content" 
                                          name="content" 
                                          class="form-textarea" 
                                          rows="12"
                                          placeholder="Write your post content here... You can use hashtags like #community #discussion to help others find your post."
                                          data-validate="required|minlength:10|no-script"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                <div class="form-help">
                                    <span id="content-counter">0</span> characters • Minimum 10 characters required
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" id="submit-btn" class="btn btn-primary">
                                    <span class="btn-text">
                                        <i class="fas fa-paper-plane"></i> Create Post
                                    </span>
                                    <span class="spinner hidden" id="loading-spinner"></span>
                                </button>
                                <a href="index.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <aside class="sidebar">
                    <!-- Post Guidelines -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Post Guidelines
                            </h3>
                        </div>
                        <div class="card-content">
                            <ul class="guidelines-list">
                                <li><strong>Be respectful:</strong> Keep discussions civil and constructive</li>
                                <li><strong>Stay on topic:</strong> Choose the appropriate category for your post</li>
                                <li><strong>Use hashtags:</strong> Add relevant hashtags like #community #help</li>
                                <li><strong>Be descriptive:</strong> Write clear titles and detailed content</li>
                                <li><strong>No spam:</strong> Avoid duplicate or promotional posts</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Category Information -->
                    <div class="card" id="category-info" style="display: none;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-folder-open"></i> Category Info
                            </h3>
                        </div>
                        <div class="card-content">
                            <div class="category-preview">
                                <div class="category-icon-preview">
                                    <i id="category-icon-display"></i>
                                </div>
                                <div class="category-details-preview">
                                    <h4 id="category-name-display"></h4>
                                    <p id="category-description-display"></p>
                                    <div class="category-stats-preview">
                                        <span class="stat-item">
                                            <i class="fas fa-file-alt"></i>
                                            <span id="category-posts-display">0</span> posts
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Posts in Category -->
                    <div class="card" id="recent-posts-category" style="display: none;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock"></i> Recent Posts
                            </h3>
                        </div>
                        <div class="card-content">
                            <div id="recent-posts-list">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
    
    <script>
        // Pass category data to JavaScript
        window.categoryData = <?php echo json_encode($categories); ?>;
    </script>
    <script src="js/validation.js"></script>
    <script src="js/ajax.js"></script>
    <script src="js/mobile-nav.js"></script>
    <script src="js/main.js"></script>
    <script src="js/create-post.js"></script>
</body>
</html>