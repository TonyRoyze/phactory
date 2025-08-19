<?php
global $conn;
include "../connector.php";

$reply_id = $_GET["reply_id"];
$writer_id = $_GET["writer_id"];

// Authorization check - ensure user can only edit their own replies
$auth_sql = "SELECT r.author_id, r.post_id, p.title as topic_title FROM replies r 
             JOIN posts p ON r.post_id = p.post_id 
             WHERE r.reply_id = $reply_id";
$auth_result = $conn->query($auth_sql);
$auth_row = $auth_result->fetch_assoc();

if ($auth_row['author_id'] != $writer_id) {
    die("Unauthorized: You can only edit your own replies.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST["content"];
    
    $sql = "UPDATE replies SET content='$content' WHERE reply_id=$reply_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: writer.php?writer_id=$writer_id");
        exit();
    } else {
        $error = "Error updating reply: " . $conn->error;
    }
}

// Get current reply data
$sql = "SELECT * FROM replies WHERE reply_id = $reply_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reply - Community Bulletin</title>
    <link rel="stylesheet" href="create.css">
</head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <h2>Edit Reply</h2>
        <p><strong>Topic:</strong> <?php echo htmlspecialchars($auth_row['topic_title']); ?></p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="content">Reply Content:</label>
                <textarea name="content" id="content" rows="8" required><?php echo htmlspecialchars($row['content']); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="submit">Update Reply</button>
                <a href="writer.php?writer_id=<?php echo $writer_id; ?>" class="cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>