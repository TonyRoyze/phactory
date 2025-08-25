<?php
include "../connector.php";

$post_id = $_GET["post_id"];
$writer_id = $_GET["writer_id"];

// Authorization check
$auth_sql = "SELECT author_id FROM posts WHERE post_id = ?";
$stmt = $conn->prepare($auth_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$auth_row = $result->fetch_assoc();

if ($auth_row['author_id'] != $writer_id) {
    die("Unauthorized: You can only edit your own posts.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $post_type = $_POST["post_type"];
    $category = $_POST["category"];
    
    $sql = "UPDATE posts SET title=?, content=?, post_type=?, category=?, updated_at=CURRENT_TIMESTAMP WHERE post_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $title, $content, $post_type, $category, $post_id);
    
    if ($stmt->execute()) {
        header("Location: writer.php?writer_id=$writer_id");
        exit();
    } else {
        $error = "Error updating post: " . $conn->error;
    }
}

// Get current post data
$sql = "SELECT * FROM posts WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Community Bulletin</title>
    <link rel="stylesheet" href="writer.css">
    <link rel="stylesheet" href="create.css">
</head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content flex-center">
        <div class="popup w-full">
            <form class="form" method="POST" action="">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-newspaper"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                        <path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>
                    </svg>
                </div>
                <div class="note">
                  <label class="title">Edit Post</label>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <select name="post_type" class="input_field" required>
                    <option value="BULLETIN" <?php echo ($row['post_type'] == 'BULLETIN') ? 'selected' : ''; ?>>Bulletin Post</option>
                    <option value="FORUM" <?php echo ($row['post_type'] == 'FORUM') ? 'selected' : ''; ?>>Forum Topic</option>
                </select>
                
                <select name="category" class="input_field" required>
                    <option value="General" <?php echo ($row['category'] == 'General') ? 'selected' : ''; ?>>General</option>
                    <option value="Events" <?php echo ($row['category'] == 'Events') ? 'selected' : ''; ?>>Events</option>
                    <option value="Marketplace" <?php echo ($row['category'] == 'Marketplace') ? 'selected' : ''; ?>>Marketplace</option>
                    <option value="Discussions" <?php echo ($row['category'] == 'Discussions') ? 'selected' : ''; ?>>Discussions</option>
                </select>
                
                <input type="text" name="title" class="input_field" value="<?php echo htmlspecialchars($row['title']); ?>" required>
                
                <textarea name="content" class="textarea_field" rows="10" required><?php echo htmlspecialchars($row['content']); ?></textarea>
                
                <button type="submit" class="submit">Update Post</button>
            </form>
        </div>
    </div>
</body>
</html>