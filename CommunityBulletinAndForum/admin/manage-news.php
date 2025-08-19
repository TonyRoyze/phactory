<?php global $conn;
include "../connector.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Community Bulletin Admin</title>
        <link rel="stylesheet" href="admin.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <?php 
        $admin_id = $_GET["admin_id"];
        echo "
            <a href='./article-create.php?admin_id=$admin_id' class='submit'>Add Post</a>
            "; 
        ?>
        
        <h2>Posts Management</h2>
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Post Type</th>
                <th>Category</th>
                <th>Author</th>
                <th>Created / Last Activity</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT p.*, u.user_name,
                    (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.post_id) as reply_count,
                    (SELECT MAX(r.created_at) FROM replies r WHERE r.post_id = p.post_id) as last_activity
                    FROM posts p 
                    JOIN user u ON p.author_id = u.user_id 
                    ORDER BY COALESCE(last_activity, p.created_at) DESC";
            $result = $conn->query($sql);

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            while ($row = $result->fetch_assoc()) {
                // Generate consistent preview (150 chars max, same as other displays)
                $content_preview = strlen($row["content"]) > 150 ? substr($row["content"], 0, 150) . "..." : $row["content"];
                $created_date = date('M j, Y g:i A', strtotime($row["created_at"]));
                
                $reply_count = $row["reply_count"];
                $type_display = $row["post_type"];
                if ($row["post_type"] == "FORUM") {
                    $reply_text = $reply_count == 1 ? "reply" : "replies";
                    $type_display .= " ($reply_count $reply_text)";
                }
                
                // Show creation date and last activity for forum topics
                $date_info = "Created: $created_date";
                if ($row["post_type"] == "FORUM" && $row["last_activity"]) {
                    $last_activity_date = date('M j, Y g:i A', strtotime($row["last_activity"]));
                    $date_info .= "<br><small>Last activity: $last_activity_date</small>";
                }
                
                echo "
                    <tr>
                        <td>$row[title]</td>
                        <td>$content_preview</td>
                        <td>$type_display</td>
                        <td>$row[category]</td>
                        <td>$row[user_name]</td>
                        <td>$date_info</td>
                        <td class='action'>
                            <a href='./article-edit.php?post_id=$row[post_id]&admin_id=$admin_id'>Edit</a>
                            <a href='./article-delete.php?post_id=$row[post_id]&admin_id=$admin_id'>Delete</a>";
                            
                if ($row["post_type"] == "FORUM" && $reply_count > 0) {
                    echo "<br><a href='./manage-replies.php?post_id=$row[post_id]&admin_id=$admin_id'>Manage Replies</a>";
                }
                
                echo "
                        </td>
                    </tr>
                    ";
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>
