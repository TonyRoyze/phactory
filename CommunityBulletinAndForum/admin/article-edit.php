<?php global $conn;
include "../connector.php";

$title = "";
$post_type = "";
$category = "";
$img_name = "";
$content = "";

$post_id = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $post_type = $_POST["post_type"];
    $category = $_POST["category"];
    $content = $_POST["content"];
    $author_id = $_POST["author_id"];
    $post_id = $_POST["post_id"];

    $sql = "UPDATE posts " .
        "SET title = '$title', post_type = '$post_type', category = '$category', content = '$content' " .
        "WHERE post_id = '$post_id'";
    $result = $conn->query($sql);

    $admin_id = $_GET["admin_id"];
    header("location: ./manage-news.php?admin_id=$admin_id");
}

if (isset($_GET["post_id"])) {
    $post_id = $_GET["post_id"];
    $sql = "SELECT * FROM posts WHERE post_id='$post_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $title = $row["title"];
    $post_type = $row["post_type"];
    $category = $row["category"];
    $content = $row["content"];
    $author_id = $row["author_id"];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Post - Community Bulletin Admin</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="create.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content flex-center">
        <div class="popup w-full">
          <form class="form" method="post" enctype="multipart/form-data">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-newspaper"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                    <path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>
                </svg>
            </div>
            <div class="note">
              <label class="title">Edit Post</label>
            </div>
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <input type="hidden" name="author_id" value="<?php echo $author_id; ?>">
            <input placeholder="Enter title" title="Enter title" name="title" type="text" class="input_field" value="<?php echo $title; ?>" required>
            <select name="post_type" class="input_field" required>
                <option value="">Select Post Type</option>
                <option value="BULLETIN" <?php echo $post_type == "BULLETIN" ? "selected" : ""; ?>>Bulletin Post</option>
                <option value="FORUM" <?php echo $post_type == "FORUM" ? "selected" : ""; ?>>Forum Topic</option>
            </select>
            <select name="category" class="input_field" required>
                <option value="">Select Category</option>
                <option value="General" <?php echo $category == "General" ? "selected" : ""; ?>>General</option>
                <option value="Events" <?php echo $category == "Events" ? "selected" : ""; ?>>Events</option>
                <option value="Marketplace" <?php echo $category == "Marketplace" ? "selected" : ""; ?>>Marketplace</option>
                <option value="Discussions" <?php echo $category == "Discussions" ? "selected" : ""; ?>>Discussions</option>
            </select>
            <textarea placeholder="Enter content" title="Enter content"  name="content" type="text" class="textarea_field" required><?php echo $content; ?></textarea>
            <button class="submit">Update Post</button>
          </form>
        </div>
    </div>


</body>
</html>
