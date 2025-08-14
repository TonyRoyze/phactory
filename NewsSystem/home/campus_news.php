<?php global $conn;
include "../connector.php";
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online News</title>
        <link rel="stylesheet" href="home.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
        <section class="news-section">
            <h2>Campus News</h2>
            <div class="news-grid">
                <?php
                $sql = "SELECT * from articles WHERE type = 'CAM'";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "
                        <div class='card'>
                            <img class='image' src='../images/$row[img_name]'>
                            <div class='article-content'>
                                <span class='title'>$row[title]</span>
                                <p class='desc'>$row[content]</p>
                            </div>
                        </div>";
                }
                ?>
            </div>
        </section>
    </div>
</body>
</html>
