<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Initialize session
initializeSession();

// Get data for homepage (using cached versions for better performance)
$categories = getCachedCategories();
$recent_posts = getCachedRecentPosts(5);
$trending_topics = getCachedTrendingTopics(8);
$upcoming_events = getCachedUpcomingEvents(3);
$community_stats = getCachedCommunityStats();

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(SITE_DESCRIPTION); ?>">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - Community Forum</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>
    <!-- Site Header -->
    <?php renderHeader('home'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <header class="page-header">
                <div class="page-header-content">
                    <h1 class="page-title">Welcome to <?php echo htmlspecialchars(SITE_NAME); ?></h1>
                    <p class="page-subtitle">Connect with your community, share ideas, and stay informed</p>
                </div>
                <?php if (isLoggedIn()): ?>
                    <div class="page-header-actions">
                        <a href="create-post.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Post
                        </a>
                    </div>
                <?php endif; ?>
            </header>
            
            <!-- Main Layout Grid -->
            <div class="content-grid">
                <!-- Main Content Area -->
                <section class="main-content-area" role="main">
                    <!-- Community Categories Section -->
                    <section class="categories-section" aria-labelledby="categories-heading">
                        <header class="section-header">
                            <h2 id="categories-heading" class="section-title">Community Categories</h2>
                            <p class="section-subtitle">Explore different areas of our community</p>
                        </header>
                        
                        <div class="categories-grid grid grid-cols-2" role="list">
                            <?php foreach ($categories as $category): ?>
                                <article class="category-card card" role="listitem">
                                    <div class="category-header">
                                        <div class="category-icon" style="color: <?php echo htmlspecialchars($category['color']); ?>" aria-hidden="true">
                                            <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                        </div>
                                        <div class="category-info">
                                            <h3 class="category-title">
                                                <a href="forum.php?category=<?php echo $category['id']; ?>" 
                                                   class="category-link"
                                                   aria-describedby="category-<?php echo $category['id']; ?>-desc">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </a>
                                            </h3>
                                            <p id="category-<?php echo $category['id']; ?>-desc" class="category-description">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="category-stats">
                                        <div class="stat-item">
                                            <span class="stat-number"><?php echo number_format($category['actual_post_count']); ?></span>
                                            <span class="stat-label">Posts</span>
                                        </div>
                                        <?php if (!empty($category['latest_post_date'])): ?>
                                            <div class="stat-item">
                                                <span class="stat-label">Latest:</span>
                                                <time datetime="<?php echo $category['latest_post_date']; ?>" class="stat-time">
                                                    <?php echo timeAgo($category['latest_post_date']); ?>
                                                </time>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($category['latest_post_title'])): ?>
                                        <div class="category-latest-post">
                                            <span class="latest-post-label">Latest post:</span>
                                            <span class="latest-post-title"><?php echo htmlspecialchars($category['latest_post_title']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    
                    <!-- Recent Posts Section -->
                    <section class="recent-posts-section" aria-labelledby="recent-posts-heading">
                        <header class="section-header">
                            <h2 id="recent-posts-heading" class="section-title">Recent Posts</h2>
                            <p class="section-subtitle">Latest discussions from the community</p>
                        </header>
                        
                        <?php if (empty($recent_posts)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comments empty-icon" aria-hidden="true"></i>
                                <h3 class="empty-title">No posts yet</h3>
                                <p class="empty-description">Be the first to start a discussion in our community!</p>
                                <?php if (isLoggedIn()): ?>
                                    <a href="post.php?action=create" class="btn btn-primary">Create First Post</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login to Post</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="posts-list" role="list">
                                <?php foreach ($recent_posts as $post): ?>
                                    <article class="post-card card" role="listitem">
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
                                            
                                            <div class="post-category">
                                                <span class="category-badge badge" 
                                                      style="background-color: <?php echo htmlspecialchars($post['category_color']); ?>20; color: <?php echo htmlspecialchars($post['category_color']); ?>">
                                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="post-content">
                                            <h3 class="post-title">
                                                <a href="post.php?id=<?php echo $post['id']; ?>" class="post-link">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </h3>
                                            
                                            <?php if ($post['excerpt']): ?>
                                                <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                            <?php else: ?>
                                                <p class="post-excerpt"><?php echo createExcerpt($post['content']); ?></p>
                                            <?php endif; ?>
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
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </section>
                
                <!-- Sidebar -->
                <aside class="sidebar" role="complementary">
                    <!-- Community Statistics -->
                    <section class="stats-widget card" aria-labelledby="stats-heading">
                        <header class="card-header">
                            <h3 id="stats-heading" class="card-title">Community Stats</h3>
                        </header>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon" aria-hidden="true">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($community_stats['total_members']); ?></div>
                                    <div class="stat-label">Total Members</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon" aria-hidden="true">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($community_stats['posts_today']); ?></div>
                                    <div class="stat-label">Posts Today</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon" aria-hidden="true">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($community_stats['active_users']); ?></div>
                                    <div class="stat-label">Active Users</div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Trending Topics -->
                    <section class="trending-widget card" aria-labelledby="trending-heading">
                        <header class="card-header">
                            <h3 id="trending-heading" class="card-title">Trending Topics</h3>
                        </header>
                        <div class="card-content">
                            <?php if (empty($trending_topics)): ?>
                                <p class="empty-message">No trending topics yet</p>
                            <?php else: ?>
                                <div class="trending-list" role="list">
                                    <?php foreach ($trending_topics as $topic): ?>
                                        <div class="trending-item" role="listitem">
                                            <a href="forum.php?topic=<?php echo urlencode($topic['topic']); ?>" 
                                               class="trending-link">
                                                <span class="topic-name">#<?php echo htmlspecialchars($topic['topic']); ?></span>
                                                <span class="topic-count"><?php echo number_format($topic['post_count']); ?> posts</span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Upcoming Events -->
                    <section class="events-widget card" aria-labelledby="events-heading">
                        <header class="card-header">
                            <h3 id="events-heading" class="card-title">Upcoming Events</h3>
                        </header>
                        <div class="card-content">
                            <?php if (empty($upcoming_events)): ?>
                                <p class="empty-message">No upcoming events</p>
                            <?php else: ?>
                                <div class="events-list" role="list">
                                    <?php foreach ($upcoming_events as $event): ?>
                                        <div class="event-item" role="listitem">
                                            <div class="event-date">
                                                <time datetime="<?php echo $event['event_date']; ?>">
                                                    <?php echo date('M j', strtotime($event['event_date'])); ?>
                                                </time>
                                            </div>
                                            <div class="event-details">
                                                <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                                                <p class="event-time">
                                                    <?php echo date('g:i A', strtotime($event['event_date'])); ?>
                                                </p>
                                                <p class="event-organizer">
                                                    by <?php echo htmlspecialchars($event['created_by_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>
    
    <!-- JavaScript -->
    <script src="js/ajax.js"></script>
    <script src="js/mobile-nav.js"></script>
    <script src="js/main.js"></script>
</body>
</html>