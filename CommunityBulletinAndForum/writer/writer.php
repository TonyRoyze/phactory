<?php global $conn;
include "../connector.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Profile - Community Bulletin</title>
        <link rel="stylesheet" href="writer.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <h2>My Posts</h2>
        
        <?php 
        // Display success/error messages
        if (isset($_GET['message'])) {
            echo "<div class='success'>" . htmlspecialchars($_GET['message']) . "</div>";
        }
        if (isset($_GET['error'])) {
            echo "<div class='error'>" . htmlspecialchars($_GET['error']) . "</div>";
        }
        ?>
        
        <?php echo "
            <a href='./article-create.php?writer_id=$writer_id' class='submit'>Create New Post</a>
            "; ?>
        
        <h3>My Bulletin Posts & Forum Topics</h3>
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Title</th>
                <th>Content Preview</th>
                <th>Type</th>
                <th>Category</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT p.*, 
                    (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.post_id) as reply_count,
                    (SELECT MAX(r.created_at) FROM replies r WHERE r.post_id = p.post_id) as last_activity
                    FROM posts p 
                    WHERE p.author_id = $writer_id 
                    ORDER BY p.created_at DESC";
            $result = $conn->query($sql);

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            if ($result->num_rows == 0) {
                echo "<tr><td colspan='6'>You haven't created any posts yet.</td></tr>";
            } else {
                while ($row = $result->fetch_assoc()) {
                    // Generate consistent preview (150 chars max, same as community.php)
                    $content_preview = strlen($row["content"]) > 150 ? substr($row["content"], 0, 150) . "..." : $row["content"];
                    
                    $created_date = date('M j, Y g:i A', strtotime($row["created_at"]));
                    
                    // For forum topics, show reply count and last activity
                    $type_display = $row["post_type"];
                    $activity_info = "Created: $created_date";
                    
                    if ($row["post_type"] == "FORUM") {
                        $reply_count = $row["reply_count"];
                        $reply_text = $reply_count == 1 ? "reply" : "replies";
                        $type_display .= " ($reply_count $reply_text)";
                        
                        if ($row["last_activity"]) {
                            $last_activity_date = date('M j, Y g:i A', strtotime($row["last_activity"]));
                            $activity_info .= "<br><small>Last activity: $last_activity_date</small>";
                        }
                    }
                    
                    echo "
                        <tr>
                            <td>$row[title]</td>
                            <td>$content_preview</td>
                            <td>$type_display</td>
                            <td>$row[category]</td>
                            <td>$activity_info</td>
                            <td class='action'>
                                <a href='./post-edit.php?post_id=$row[post_id]&writer_id=$writer_id'>Edit</a>
                                <a href='./post-delete.php?post_id=$row[post_id]&writer_id=$writer_id' onclick='return confirm(\"Are you sure you want to delete this post?\")'>Delete</a>
                            </td>
                        </tr>
                        ";
                }
            }
            ?>
            </tbody>
        </table>

        <h3>My Replies</h3>
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Reply Content</th>
                <th>Topic Title</th>
                <th>Topic Author</th>
                <th>Posted</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT r.*, p.title as topic_title, u.user_name as topic_author 
                    FROM replies r 
                    JOIN posts p ON r.post_id = p.post_id 
                    JOIN user u ON p.author_id = u.user_id
                    WHERE r.author_id = $writer_id 
                    ORDER BY r.created_at DESC";
            $result = $conn->query($sql);

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            if ($result->num_rows == 0) {
                echo "<tr><td colspan='5'>You haven't posted any replies yet.</td></tr>";
            } else {
                while ($row = $result->fetch_assoc()) {
                    // Generate consistent preview (100 chars for replies to fit in table)
                    $content_preview = strlen($row["content"]) > 100 ? substr($row["content"], 0, 100) . "..." : $row["content"];
                    $created_date = date('M j, Y g:i A', strtotime($row["created_at"]));
                    echo "
                        <tr>
                            <td>$content_preview</td>
                            <td><a href='../home/forum-topic.php?id=$row[post_id]&user_id=$writer_id'>$row[topic_title]</a></td>
                            <td>$row[topic_author]</td>
                            <td>$created_date</td>
                            <td class='action'>
                                <a href='./reply-edit.php?reply_id=$row[reply_id]&writer_id=$writer_id'>Edit</a>
                                <a href='./reply-delete.php?reply_id=$row[reply_id]&writer_id=$writer_id' onclick='return confirm(\"Are you sure you want to delete this reply?\")'>Delete</a>
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
