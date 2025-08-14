<?php global $conn;
include "../connector.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $article_type = $_POST["article_type"];
    $file_name = $_FILES["img_name"]["name"];
    $temp_name = $_FILES["img_name"]["tmp_name"];
    $folder = "../images/" . $file_name;

    move_uploaded_file($temp_name, $folder);

    $content = $_POST["content"];
    $admin_id = $_GET["admin_id"];

    $sql = "INSERT INTO articles (title, type, img_name, content, writer_id) VALUES ('$title', '$article_type', '$file_name', '$content', $admin_id)";
    $result = $conn->query($sql);

    header("location: ./manage-news.php?admin_id=$admin_id");
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online News</title>
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
              <label class="title">New Article</label>
            </div>
            <input placeholder="Enter title" title="Enter title" name="title" type="text" class="input_field" required>
            <select name="article_type" class="input_field" required>
                <option value="">Select Type</option>
                <option value="CAM">Campus News</option>
                <option value="EDU">Educational</option>
                <option value="SPN">Sports News</option>
            </select>
            <textarea placeholder="Enter content" title="Enter content"  name="content" type="text" class="textarea_field" required></textarea>
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
                <img id="image-preview" src="" alt="Image preview">
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
