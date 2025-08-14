<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Initialize session
initializeSession();

// Get search parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$results_per_page = 10;
$results = [];
$total_results = 0;
$search_performed = false;

// Perform search if query is provided
if (!empty($query) && strlen($query) >= 2) {
    $search_performed = true;
    $results = performSearch($query, $type, $category, $sort, $page, $results_per_page);
    $total_results = $results['total'];
}

// Get categories for filter dropdown
$categories = getCategories();

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Search results for <?php echo htmlspecialchars($query); ?> - <?php echo htmlspecialchars(SITE_NAME); ?>">
    <title><?php echo !empty($query) ? 'Search: ' . htmlspecialchars($query) : 'Search'; ?> - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    
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
    <?php renderHeader('search'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Search Header -->
            <header class="search-header">
                <div class="search-header-content">
                    <h1 class="page-title">
                        <?php if (!empty($query)): ?>
                            Search Results for "<?php echo htmlspecialchars($query); ?>"
                        <?php else: ?>
                            Search
                        <?php endif; ?>
                    </h1>
                    <?php if ($search_performed): ?>
                        <p class="search-subtitle">
                            <?php echo number_format($total_results); ?> result<?php echo $total_results !== 1 ? 's' : ''; ?> found
                        </p>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Search Form -->
            <section class="search-form-section">
                <form class="search-form" method="GET" action="search.php">
                    <div class="search-input-group">
                        <input type="text" 
                               name="q" 
                               id="main-search-input"
                               class="search-input-main" 
                               placeholder="Search posts, users, topics..." 
                               value="<?php echo htmlspecialchars($query); ?>"
                               autocomplete="off">
                        <button type="submit" class="search-btn-main">
                            <i class="fas fa-search"></i>
                            <span class="sr-only">Search</span>
                        </button>
                        <div id="search-suggestions" class="search-suggestions"></div>
                    </div>
                    
                    <!-- Search Filters -->
                    <div class="search-filters">
                        <div class="filter-group">
                            <label for="type-filter" class="filter-label">Type:</label>
                            <select name="type" id="type-filter" class="filter-select">
                                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="posts" <?php echo $type === 'posts' ? 'selected' : ''; ?>>Posts</option>
                                <option value="users" <?php echo $type === 'users' ? 'selected' : ''; ?>>Users</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="category-filter" class="filter-label">Category:</label>
                            <select name="category" id="category-filter" class="filter-select">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category === $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort-filter" class="filter-label">Sort by:</label>
                            <select name="sort" id="sort-filter" class="filter-select">
                                <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                                <option value="most_liked" <?php echo $sort === 'most_liked' ? 'selected' : ''; ?>>Most Liked</option>
                                <option value="most_commented" <?php echo $sort === 'most_commented' ? 'selected' : ''; ?>>Most Commented</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-outline filter-apply-btn">Apply Filters</button>
                    </div>
                </form>
            </section>
            
            <!-- Search Results -->
            <?php if ($search_performed): ?>
                <section class="search-results-section">
                    <?php if (empty($results['items'])): ?>
                        <!-- No Results -->
                        <div class="no-results">
                            <div class="no-results-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="no-results-title">No results found</h3>
                            <p class="no-results-description">
                                We couldn't find anything matching "<?php echo htmlspecialchars($query); ?>". 
                                Try different keywords or check your spelling.
                            </p>
                            <div class="no-results-suggestions">
                                <h4>Search suggestions:</h4>
                                <ul>
                                    <li>Try more general keywords</li>
                                    <li>Check your spelling</li>
                                    <li>Use fewer keywords</li>
                                    <li>Try searching in a specific category</li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Results List -->
                        <div class="search-results-list">
                            <?php foreach ($results['items'] as $result): ?>
                                <?php if ($result['type'] === 'post'): ?>
                                    <!-- Post Result -->
                                    <article class="search-result-item post-result">
                                        <div class="result-header">
                                            <div class="result-type">
                                                <i class="fas fa-file-alt"></i>
                                                <span>Post</span>
                                            </div>
                                            <div class="result-category">
                                                <span class="category-badge badge" 
                                                      style="background-color: <?php echo htmlspecialchars($result['category_color']); ?>20; color: <?php echo htmlspecialchars($result['category_color']); ?>">
                                                    <?php echo htmlspecialchars($result['category']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="result-content">
                                            <h3 class="result-title">
                                                <a href="<?php echo htmlspecialchars($result['url']); ?>" class="result-link">
                                                    <?php echo highlightSearchTerms($result['title'], $query); ?>
                                                </a>
                                            </h3>
                                            
                                            <?php if (!empty($result['excerpt'])): ?>
                                                <p class="result-excerpt">
                                                    <?php echo highlightSearchTerms($result['excerpt'], $query); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="result-meta">
                                                <span class="result-author">
                                                    <i class="fas fa-user"></i>
                                                    by <?php echo htmlspecialchars($result['username']); ?>
                                                </span>
                                                <span class="result-date">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo timeAgo($result['created_at']); ?>
                                                </span>
                                                <span class="result-engagement">
                                                    <i class="fas fa-heart"></i>
                                                    <?php echo number_format($result['likes_count']); ?>
                                                    <i class="fas fa-comment"></i>
                                                    <?php echo number_format($result['comments_count']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                    
                                <?php elseif ($result['type'] === 'user'): ?>
                                    <!-- User Result -->
                                    <article class="search-result-item user-result">
                                        <div class="result-header">
                                            <div class="result-type">
                                                <i class="fas fa-user"></i>
                                                <span>User</span>
                                            </div>
                                        </div>
                                        
                                        <div class="result-content">
                                            <h3 class="result-title">
                                                <a href="<?php echo htmlspecialchars($result['url']); ?>" class="result-link">
                                                    <?php echo highlightSearchTerms($result['username'], $query); ?>
                                                </a>
                                            </h3>
                                            
                                            <?php if (!empty($result['bio'])): ?>
                                                <p class="result-excerpt">
                                                    <?php echo highlightSearchTerms(createExcerpt($result['bio'], 100), $query); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="result-meta">
                                                <span class="result-joined">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    Joined <?php echo timeAgo($result['created_at']); ?>
                                                </span>
                                                <?php if (isset($result['post_count'])): ?>
                                                    <span class="result-posts">
                                                        <i class="fas fa-edit"></i>
                                                        <?php echo number_format($result['post_count']); ?> posts
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($results['total_pages'] > 1): ?>
                            <nav class="search-pagination" aria-label="Search results pagination">
                                <div class="pagination-info">
                                    Showing <?php echo number_format(($page - 1) * $results_per_page + 1); ?> - 
                                    <?php echo number_format(min($page * $results_per_page, $total_results)); ?> 
                                    of <?php echo number_format($total_results); ?> results
                                </div>
                                
                                <div class="pagination-controls">
                                    <?php if ($results['has_prev']): ?>
                                        <a href="<?php echo buildSearchUrl($query, $type, $category, $sort, $page - 1); ?>" 
                                           class="btn btn-outline pagination-btn">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($results['total_pages'], $page + 2);
                                    ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <?php if ($i === $page): ?>
                                            <span class="pagination-current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo buildSearchUrl($query, $type, $category, $sort, $i); ?>" 
                                               class="pagination-link"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($results['has_next']): ?>
                                        <a href="<?php echo buildSearchUrl($query, $type, $category, $sort, $page + 1); ?>" 
                                           class="btn btn-outline pagination-btn">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php elseif (!empty($query) && strlen($query) < 2): ?>
                <!-- Query too short -->
                <div class="search-message">
                    <div class="search-message-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="search-message-title">Search query too short</h3>
                    <p class="search-message-description">
                        Please enter at least 2 characters to search.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- JavaScript -->
    <script src="js/ajax.js"></script>
    <script src="js/mobile-nav.js"></script>
    <script src="js/search.js"></script>
    <script src="js/main.js"></script>
</body>
</html>