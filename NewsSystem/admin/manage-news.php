<?php global $conn;
include "../connector.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online News</title>
        <link rel="stylesheet" href="admin.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <?php echo "
            <a href='./article-create.php?admin_id=$admin_id' class='submit'>Add Article</a>
            "; ?>
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Type</th>
                <th>Writer</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM articles";
            $result = $conn->query($sql);
            $admin_id = $_GET["admin_id"];

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            while ($row = $result->fetch_assoc()) {
                $content = substr($row["content"], 0, 30) . "...";
                echo "
                    <tr>
                        <td>$row[title]</td>
                        <td>$content</td>
                        <td>$row[type]</td>
                        <td>$row[writer_id]</td>
                        <td class='action'>
                            <a href='./article-edit.php?article_id=$row[article_id]&admin_id=$admin_id'>Edit</a>
                            <a href='./article-delete.php?article_id=$row[article_id]&admin_id=$admin_id'>Delete</a>
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
