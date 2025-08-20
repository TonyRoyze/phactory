<?php 
global $conn;
include "../connector.php";

// Get the topic ID from URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($topic_id <= 0) {
    header("Location: community.php");
    exit();
}

// Handle reply submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_content'])) {
    $reply_content = trim($_POST['reply_content']);
    $author_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    // Validate reply content length
    if (empty($reply_content)) {
        $error_message = "Reply content cannot be empty.";
    } elseif (strlen($reply_content) > 5000) {
        $error_message = "Reply content is too long. Maximum 5000 characters allowed.";
    } elseif ($author_id <= 0) {
        $error_message = "You must be logged in to post a reply.";
    } else {
        // Verify user exists and is active
        $user_check = $conn->prepare("SELECT user_id FROM user WHERE user_id = ?");
        $user_check->bind_param("i", $author_id);
        $user_check->execute();
        $user_result = $user_check->get_result();
        
        if ($user_result->num_rows == 0) {
            $error_message = "Invalid user session. Please log in again.";
        } else {
            $stmt = $conn->prepare("INSERT INTO replies (post_id, content, author_id) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $topic_id, $reply_content, $author_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                $redirect_url = "forum-topic.php?id=$topic_id";
                if (isset($_GET['user_id'])) {
                    $redirect_url .= "&user_id=" . $_GET['user_id'];
                }
                header("Location: $redirect_url");
                exit();
            } else {
                $error_message = "Error posting reply. Please try again.";
            }
        }
    }
}

// Get the forum topic details
$stmt = $conn->prepare("SELECT p.*, u.user_name FROM posts p 
                       JOIN user u ON p.author_id = u.user_id 
                       WHERE p.post_id = ? AND p.post_type = 'FORUM'");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$topic_result = $stmt->get_result();

if ($topic_result->num_rows == 0) {
    header("Location: community.php");
    exit();
}

$topic = $topic_result->fetch_assoc();

// Get all replies for this topic
$stmt = $conn->prepare("SELECT r.*, u.user_name FROM replies r 
                       JOIN user u ON r.author_id = u.user_id 
                       WHERE r.post_id = ? 
                       ORDER BY r.created_at ASC");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$replies_result = $stmt->get_result();

// Check if user is logged in
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$is_logged_in = $user_id > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Community Forum</title>
    <link rel="stylesheet" href="home.css">
    <style>
        .forum-topic-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .topic-header {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .topic-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .topic-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        .topic-content {
            color: #374151;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .replies-section {
            margin-top: 30px;
        }
        
        .replies-header {
            color: #000
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .reply {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .reply-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .reply-author {
            font-weight: 600;
            color: #374151;
        }
        
        .reply-content {
            color: #374151;
            line-height: 1.6;
        }
        
        .reply-form {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .reply-form h3 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.875rem;
            resize: vertical;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background-color: #1d4ed8;
        }
        
        .btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
        
        .error-message {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #fecaca;
        }
        
        .login-prompt {
            background-color: #f3f4f6;
            color: #374151;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        
        .login-prompt a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-prompt a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .breadcrumb-link {
            color: #000;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .breadcrumb-link:hover {
            color: #ffcc00;
            text-decoration: underline;
        }
        
        .breadcrumb-separator {
            color: rgba(255, 255, 255, 0.6);
            font-weight: bold;
        }
        
        .breadcrumb-current {
            color: #ffcc00;
            font-weight: 500;
        }
        
        .category-badge {
            background-color: #e5e7eb;
            color: #374151;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .forum-topic-page {
                padding: 10px;
            }
            
            .topic-meta {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <div class="forum-topic-page">
            <div class="breadcrumb">
                <a href="community.php<?php echo isset($_GET['user_id']) ? '?user_id=' . intval($_GET['user_id']) : ''; ?>" class="breadcrumb-link">Home</a>
                <span class="breadcrumb-separator">›</span>
                <a href="community.php?category=<?php echo urlencode($topic['category']); ?><?php echo isset($_GET['user_id']) ? '&user_id=' . intval($_GET['user_id']) : ''; ?>" class="breadcrumb-link">
                    <?php echo htmlspecialchars($topic['category']); ?>
                </a>
                <span class="breadcrumb-separator">›</span>
                <span class="breadcrumb-current">Discussion</span>
            </div>
            
            <!-- Topic Header -->
            <div class="topic-header">
                <h1 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h1>
                <div class="topic-meta">
                    <span class="reply-author">Started by <?php echo htmlspecialchars($topic['user_name']); ?></span>
                    <span class="category-badge category <?php echo strtolower($topic['category']); ?>"><?php echo htmlspecialchars($topic['category']); ?></span>
                    <span><?php echo date('M j, Y g:i A', strtotime($topic['created_at'])); ?></span>
                </div>
                <div class="topic-content">
                    <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
                </div>
            </div>
            
            <!-- Replies Section -->
            <div class="replies-section">
                <h2 class="replies-header">
                    Replies (<?php echo $replies_result->num_rows; ?>)
                </h2>
                
                <?php if ($replies_result->num_rows > 0): ?>
                    <?php while ($reply = $replies_result->fetch_assoc()): ?>
                        <div class="reply">
                            <div class="reply-meta">
                                <span class="reply-author"><?php echo htmlspecialchars($reply['user_name']); ?></span>
                                <span><?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?></span>
                            </div>
                            <div class="reply-content">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #000 text-align: center; padding: 20px;">No replies yet. Be the first to reply!</p>
                <?php endif; ?>
            </div>
            
            <!-- Reply Form -->
            <div class="reply-form">
                <?php if ($is_logged_in): ?>
                    <h3>Post a Reply</h3>
                    <?php if (isset($error_message)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <textarea name="reply_content" placeholder="Enter your reply..." required maxlength="5000" id="reply-textarea"></textarea>
                            <div style="text-align: right; font-size: 0.75rem; color: #6b7280; margin-top: 5px;">
                                <span id="char-count">0</span>/5000 characters
                            </div>
                        </div>
                        <button type="submit" class="btn">Post Reply</button>
                    </form>
                    <script>
                        document.getElementById('reply-textarea').addEventListener('input', function() {
                            const charCount = this.value.length;
                            document.getElementById('char-count').textContent = charCount;
                            
                            if (charCount > 4500) {
                                document.getElementById('char-count').style.color = '#dc2626';
                            } else {
                                document.getElementById('char-count').style.color = '#6b7280';
                            }
                        });
                    </script>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>You must be <a href="../login.php">logged in</a> to post a reply.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>