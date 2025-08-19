<?php
global $conn;
include "../connector.php";

$post_id = $_GET["post_id"];
$writer_id = $_GET["writer_id"];

// Authorization check - ensure user can only edit their own posts
$auth_sql = "SELECT author_id FROM posts WHERE post_id = $post_id";
$auth_result = $conn->query($auth_sql);
$auth_row = $auth_result->fetch_assoc();

if ($auth_row['author_id'] != $writer_id) {
    die("Unauthorized: You can only edit your own posts.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $post_type = $_POST["post_type"];
    $category = $_POST["category"];
    
    $sql = "UPDATE posts SET title='$title', content='$content', post_type='$post_type', category='$category', updated_at=CURRENT_TIMESTAMP WHERE post_id=$post_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: writer.php?writer_id=$writer_id");
        exit();
    } else {
        $error = "Error updating post: " . $conn->error;
    }
}

// Get current post data
$sql = "SELECT * FROM posts WHERE post_id = $post_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Community Bulletin</title>
    <link rel="stylesheet" href="create.css">
</head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <h2>Edit Post</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="post_type">Post Type:</label>
                <select name="post_type" id="post_type" required>
                    <option value="BULLETIN" <?php echo ($row['post_type'] == 'BULLETIN') ? 'selected' : ''; ?>>Bulletin Post</option>
                    <option value="FORUM" <?php echo ($row['post_type'] == 'FORUM') ? 'selected' : ''; ?>>Forum Topic</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="category">Category:</label>
                <select name="category" id="category" required>
                    <option value="General" <?php echo ($row['category'] == 'General') ? 'selected' : ''; ?>>General</option>
                    <option value="Events" <?php echo ($row['category'] == 'Events') ? 'selected' : ''; ?>>Events</option>
                    <option value="Marketplace" <?php echo ($row['category'] == 'Marketplace') ? 'selected' : ''; ?>>Marketplace</option>
                    <option value="Discussions" <?php echo ($row['category'] == 'Discussions') ? 'selected' : ''; ?>>Discussions</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($row['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Content:</label>
                <textarea name="content" id="content" rows="10" required><?php echo htmlspecialchars($row['content']); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="submit">Update Post</button>
                <a href="writer.php?writer_id=<?php echo $writer_id; ?>" class="cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>