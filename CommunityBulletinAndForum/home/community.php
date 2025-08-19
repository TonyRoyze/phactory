<?php global $conn;
include "../connector.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Bulletin & Forum</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <?php
        // Check if category filter is applied
        $category_filter = isset($_GET['category']) ? $_GET['category'] : null;
        $valid_categories = ['General', 'Events', 'Marketplace', 'Discussions'];
        
        if ($category_filter && in_array($category_filter, $valid_categories)) {
            // Show filtered content for specific category
            echo "<div class='category-header'>
                    <h1>$category_filter</h1>
                    <p class='category-description'>Browse all content in the $category_filter category</p>
                  </div>";
            
            // Get bulletin count first
            $sql = "SELECT p.*, u.user_name FROM posts p 
                    JOIN user u ON p.author_id = u.user_id 
                    WHERE p.post_type = 'BULLETIN' AND p.category = ? 
                    ORDER BY p.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_filter);
            $stmt->execute();
            $result = $stmt->get_result();
            $bulletin_count = $result->num_rows;
            
            // Bulletin Posts in this category
            echo "<section class='news-section category-section'>
                    <div class='section-header'>
                        <h2>
                            <svg class='section-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                <!-- Bulletin/Clipboard icon placeholder -->
                                <rect width='8' height='4' x='8' y='2' rx='1' ry='1'/>
                                <path d='M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2'/>
                                <path d='M12 11h4'/>
                                <path d='M12 16h4'/><path d='M8 11h.01'/>
                                <path d='M8 16h.01'/>
                            </svg>
                            Bulletin Posts
                        </h2>
                        <span class='section-subtitle'>$bulletin_count " . ($bulletin_count == 1 ? 'post' : 'posts') . " in this category</span>
                    </div>
                    <div class='news-grid'>";
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Generate consistent preview (150 chars max)
                    $preview = strlen($row['content']) > 150 ? substr($row['content'], 0, 150) . '...' : $row['content'];
                    $category_class = strtolower($row['category']);
                    echo "
                        <div class='card'>
                            <div class='card-header $category_class'>
                                <span class='category-badge'>{$row['category']}</span>
                            </div>
                            <div class='article-content'>
                                <span class='title'>{$row['title']}</span>
                                <div class='meta-info'>
                                    <small>By {$row['user_name']} • " . date('M j, Y g:i A', strtotime($row['created_at'])) . "</small>
                                </div>
                                <p class='desc'>$preview</p>
                            </div>
                        </div>";
                }
            } else {
                echo "<p>No bulletin posts in $category_filter category.</p>";
            }
            
            echo "</div></section>";
            
            // Get forum count first
            $sql = "SELECT p.*, u.user_name, 
                    (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.post_id) as reply_count,
                    (SELECT MAX(r.created_at) FROM replies r WHERE r.post_id = p.post_id) as last_activity
                    FROM posts p 
                    JOIN user u ON p.author_id = u.user_id 
                    WHERE p.post_type = 'FORUM' AND p.category = ? 
                    ORDER BY COALESCE(last_activity, p.created_at) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_filter);
            $stmt->execute();
            $result = $stmt->get_result();
            $forum_count = $result->num_rows;
            
            // Forum Topics in this category
            echo "<section class='news-section category-section'>
                    <div class='section-header'>
                        <h2>
                            <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-messages-square-icon lucide-messages-square'><path d='M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z'/><path d='M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1'/></svg>
                            Forum Discussions
                        </h2>
                        <span class='section-subtitle'>$forum_count " . ($forum_count == 1 ? 'discussion' : 'discussions') . " in this category</span>
                    </div>
                    <div class='forum-grid'>";
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $last_activity = $row['last_activity'] ? $row['last_activity'] : $row['created_at'];
                    $reply_text = $row['reply_count'] == 1 ? 'reply' : 'replies';
                    echo "
                        <div class='forum-topic'>
                            <div class='topic-info'>
                                <h3 class='topic-title'><a href='forum-topic.php?id={$row['post_id']}" . (isset($_GET['user_id']) ? '&user_id=' . intval($_GET['user_id']) : '') . "' style='text-decoration: none; color: inherit;'>{$row['title']}</a></h3>
                                <div class='topic-meta'>
                                    <span class='author'>Started by {$row['user_name']}</span>
                                    <span class='replies'>{$row['reply_count']} $reply_text</span>
                                    <span class='last-activity'>Last activity: " . date('M j, Y g:i A', strtotime($last_activity)) . "</span>
                                </div>
                            </div>
                        </div>";
                }
            } else {
                echo "<p>No forum discussions in $category_filter category.</p>";
            }
            
            echo "</div></section>";
            
        } else {
            // Show default home page with recent content from all categories
        ?>
        
        <!-- Recent Bulletin Posts Section -->
        <section class="news-section">
            <div class="section-header">
                <h2>
                <svg class='section-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                <!-- Bulletin/Clipboard icon placeholder -->
                                <rect width='8' height='4' x='8' y='2' rx='1' ry='1'/>
                                <path d='M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2'/>
                                <path d='M12 11h4'/>
                                <path d='M12 16h4'/><path d='M8 11h.01'/>
                                <path d='M8 16h.01'/>
                            </svg>
                    Recent Bulletin Posts
                </h2>
                <span class="section-subtitle">Latest announcements and information</span>
            </div>
            <div class="news-grid">
                <?php
                $sql = "SELECT p.*, u.user_name FROM posts p 
                        JOIN user u ON p.author_id = u.user_id 
                        WHERE p.post_type = 'BULLETIN' 
                        ORDER BY p.created_at DESC LIMIT 6";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Generate consistent preview (150 chars max)
                        $preview = strlen($row['content']) > 150 ? substr($row['content'], 0, 150) . '...' : $row['content'];
                        $category_class = strtolower($row['category']);
                        echo "
                            <div class='card'>
                                <div class='card-header $category_class'>
                                    <span class='category-badge'>{$row['category']}</span>
                                </div>
                                <div class='article-content'>
                                    <span class='title'>{$row['title']}</span>
                                    <div class='meta-info'>
                                        <small>By {$row['user_name']} • " . date('M j, Y g:i A', strtotime($row['created_at'])) . "</small>
                                    </div>
                                    <p class='desc'>$preview</p>
                                </div>
                            </div>";
                    }
                } else {
                    echo "<p>No bulletin posts available.</p>";
                }
                ?>
            </div>
        </section>

        <!-- Active Forum Discussions Section -->
        <section class="news-section">
            <div class="section-header">
                <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-messages-square-icon lucide-messages-square"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"/></svg>
                    Active Forum Discussions
                </h2>
                <span class="section-subtitle">Join the conversation</span>
            </div>
            <div class="forum-grid">
                <?php
                $sql = "SELECT p.*, u.user_name, 
                        (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.post_id) as reply_count,
                        (SELECT MAX(r.created_at) FROM replies r WHERE r.post_id = p.post_id) as last_activity
                        FROM posts p 
                        JOIN user u ON p.author_id = u.user_id 
                        WHERE p.post_type = 'FORUM' 
                        ORDER BY COALESCE(last_activity, p.created_at) DESC LIMIT 8";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $last_activity = $row['last_activity'] ? $row['last_activity'] : $row['created_at'];
                        $reply_text = $row['reply_count'] == 1 ? 'reply' : 'replies';
                        echo "
                            <div class='forum-topic'>
                                <div class='topic-info'>
                                    <h3 class='topic-title'><a href='forum-topic.php?id={$row['post_id']}" . (isset($_GET['user_id']) ? '&user_id=' . intval($_GET['user_id']) : '') . "' style='text-decoration: none; color: inherit;'>{$row['title']}</a></h3>
                                    <div class='topic-meta'>
                                        <span class='author'>Started by {$row['user_name']}</span>
                                        <span class='category " . strtolower($row['category']) . "'>{$row['category']}</span>
                                        <span class='replies'>{$row['reply_count']} $reply_text</span>
                                        <span class='last-activity'>Last activity: " . date('M j, Y g:i A', strtotime($last_activity)) . "</span>
                                    </div>
                                </div>
                            </div>";
                    }
                } else {
                    echo "<p>No forum discussions available.</p>";
                }
                ?>
            </div>
        </section>
        
        <?php } ?>
    </div>
</body>
</html>