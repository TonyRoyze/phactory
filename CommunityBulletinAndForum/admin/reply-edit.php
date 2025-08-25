<?php global $conn;
include "../connector.php";

$content = "";
$reply_id = "";
$post_id = $_GET["post_id"];
$admin_id = $_GET["admin_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST["content"];
    $reply_id = $_POST["reply_id"];

    $sql = "UPDATE replies SET content = '$content' WHERE reply_id = '$reply_id'";
    $result = $conn->query($sql);

    header("location: ./manage-replies.php?post_id=$post_id&admin_id=$admin_id");
}

if (isset($_GET["reply_id"])) {
    $reply_id = $_GET["reply_id"];
    $sql = "SELECT r.*, u.user_name FROM replies r JOIN user u ON r.author_id = u.user_id WHERE r.reply_id='$reply_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $content = $row["content"];
    $author_name = $row["user_name"];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Reply - Community Bulletin Admin</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="create.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content flex-center">
        <div class="popup w-full">
          <form class="form" method="post">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle">
                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                </svg>
            </div>
            <div class="note">
              <label class="title">Edit Reply by <?php echo htmlspecialchars($author_name); ?></label>
            </div>
            <input type="hidden" name="reply_id" value="<?php echo $reply_id; ?>">
            <textarea placeholder="Enter reply content" title="Enter reply content" name="content" class="textarea_field" required><?php echo htmlspecialchars($content); ?></textarea>
            <button class="submit">Update Reply</button>
            <a href="./manage-replies.php?post_id=<?php echo $post_id; ?>&admin_id=<?php echo $admin_id; ?>" class="submit" style="background: #666; text-decoration: none; display: inline-block; text-align: center; margin-top: 10px;">Cancel</a>
          </form>
        </div>
    </div>
</body>
</html>