<?php global $conn;
include "../connector.php";

$admin_id = $_GET["admin_id"];
$post_id = $_GET["post_id"];

// Get the forum topic details
$post_sql = "SELECT p.*, u.user_name FROM posts p JOIN user u ON p.author_id = u.user_id WHERE p.post_id = '$post_id'";
$post_result = $conn->query($post_sql);
$post = $post_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Replies - Community Bulletin Admin</title>
        <link rel="stylesheet" href="admin.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <a href="./manage-news.php?admin_id=<?php echo $admin_id; ?>" class="submit">Back to Posts</a>
        
        <h2>Managing Replies for: "<?php echo htmlspecialchars($post['title']); ?>"</h2>
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <strong>Original Post by <?php echo htmlspecialchars($post['user_name']); ?>:</strong><br>
            <p><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>...</p>
            <small>Posted: <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
        </div>
        
        <h3>Replies</h3>
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Reply Content</th>
                <th>Author</th>
                <th>Posted</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT r.*, u.user_name FROM replies r JOIN user u ON r.author_id = u.user_id WHERE r.post_id = '$post_id' ORDER BY r.created_at ASC";
            $result = $conn->query($sql);

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            if ($result->num_rows == 0) {
                echo "<tr><td colspan='4' style='text-align: center;'>No replies yet</td></tr>";
            } else {
                while ($row = $result->fetch_assoc()) {
                    $content = strlen($row["content"]) > 100 ? substr($row["content"], 0, 100) . "..." : $row["content"];
                    $created_date = date('M j, Y g:i A', strtotime($row["created_at"]));
                    
                    echo "
                        <tr>
                            <td>" . nl2br(htmlspecialchars($content)) . "</td>
                            <td>" . htmlspecialchars($row["user_name"]) . "</td>
                            <td>$created_date</td>
                            <td class='action'>
                                <a href='./reply-edit.php?reply_id=$row[reply_id]&post_id=$post_id&admin_id=$admin_id'>Edit</a>
                                <a href='./reply-delete.php?reply_id=$row[reply_id]&post_id=$post_id&admin_id=$admin_id' onclick='return confirm(\"Are you sure you want to delete this reply?\")'>Delete</a>
                            </td>
                        </tr>
                        ";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>