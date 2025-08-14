<?php global $conn;
include "../connector.php";

$title = "";
$article_type = "";
$img_name = "";
$content = "";

$article_id = "";
$writer_id = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $article_type = $_POST["article_type"];
    $content = $_POST["content"];
    $article_id = $_POST["article_id"];

    $writer_id = $_GET["writer_id"];

    $file_name = $_FILES["img_name"]["name"];
    $temp_name = $_FILES["img_name"]["tmp_name"];
    $folder = "../images/" . $file_name;
    move_uploaded_file($temp_name, $folder);

    $sql =
        /** @lang text */
        "UPDATE articles " .
        "SET title = '$title', type = '$article_type', img_name = '$file_name', content = '$content', writer_id = '$writer_id' " .
        "WHERE article_id = '$article_id'";
    $result = $conn->query($sql);

    header("location: ./writer.php?writer_id=$writer_id");
}

if (isset($_GET["article_id"])) {
    $article_id = $_GET["article_id"];
    $sql = "SELECT * FROM articles WHERE article_id='$article_id '";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $title = $row["title"];
    $article_type = $row["type"];
    $img_name = $row["img_name"];
    $content = $row["content"];
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online News</title>
        <link rel="stylesheet" href="writer.css">
        <link rel="stylesheet" href="create.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content flex-center">
        <div class="popup w-full">
          <form class="form" method="post" enctype="multipart/form-data" >
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-plus">
                  <path d="M2 21a8 8 0 0 1 13.292-6"/>
                  <circle cx="10" cy="8" r="5"/><path d="M19 16v6"/><path d="M22 19h-6"/>
                </svg>
            </div>
            <div class="note">
              <label class="title">Edit Article</label>
            </div>
            <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
            <input type="hidden" name="writer_id" value="<?php echo $writer_id; ?>">
            <input placeholder="Enter title" title="Enter title" name="title" type="text" class="input_field" value="<?php echo $title; ?>" required>
            <select name="article_type" class="input_field" required>
                <option value="">Select Type</option>
                <option value="CAM" <?php echo $article_type == "CAM"
                    ? "selected"
                    : ""; ?>>Campus News</option>
                <option value="EDU" <?php echo $article_type == "EDU"
                    ? "selected"
                    : ""; ?>>Educational</option>
                <option value="SPN" <?php echo $article_type == "SPN"
                    ? "selected"
                    : ""; ?>>Sports News</option>
            </select>
            <textarea placeholder="Enter content" title="Enter content"  name="content" type="text" class="textarea_field" required><?php echo $content; ?></textarea>
            <div class="custom-file-upload-container">
                <label class="custom-file-upload" for="file">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image-up">
                            <path d="M10.3 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10l-3.1-3.1a2 2 0 0 0-2.814.014L6 21"/><path d="m14 19.5 3-3 3 3"/><path d="M17 22v-5.5"/><circle cx="9" cy="9" r="2"/>
                        </svg>
                    </div>
                    <div class="text">
                        <span>Click to upload image</span>
                    </div>
                    <input name="img_name" type="file" id="file" accept="image/*">
                </label>
                <img id="image-preview" src="../images/<?php echo $img_name; ?>" alt="Image preview" style="display: <?php echo $img_name
    ? "block"
    : "none"; ?>;">
            </div>
            <button class="submit">Submit</button>
          </form>
        </div>
    </div>
    <script>
        document.getElementById('file').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
