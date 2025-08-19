<?php global $conn;
include "../connector.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $post_type = $_POST["post_type"];
    $category = $_POST["category"];
    $content = $_POST["content"];
    $writer_id = $_GET["writer_id"];
    
    // Validate required fields
    if (empty($title) || empty($post_type) || empty($category) || empty($content)) {
        $error = "All fields are required.";
    } else if (!in_array($post_type, ['BULLETIN', 'FORUM'])) {
        $error = "Invalid post type.";
    } else if (!in_array($category, ['General', 'Events', 'Marketplace', 'Discussions'])) {
        $error = "Invalid category.";
    } else {
        $sql = "INSERT INTO posts (title, content, post_type, category, author_id) VALUES ('$title', '$content', '$post_type', '$category', $writer_id)";
        $result = $conn->query($sql);

        if ($result) {
            header("location: ./writer.php?writer_id=$writer_id");
            exit();
        } else {
            $error = "Error creating post. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Post - Community Bulletin</title>
        <link rel="stylesheet" href="writer.css">
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
              <label class="title">Create Community Post</label>
            </div>
            <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 10px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <input placeholder="Enter title" title="Enter title" name="title" type="text" class="input_field" required>
            <select name="post_type" class="input_field" required>
                <option value="">Select Post Type</option>
                <option value="BULLETIN">Bulletin Post</option>
                <option value="FORUM">Forum Topic</option>
            </select>
            <select name="category" class="input_field" required>
                <option value="">Select Category</option>
                <option value="General">General</option>
                <option value="Events">Events</option>
                <option value="Marketplace">Marketplace</option>
                <option value="Discussions">Discussions</option>
            </select>
            <textarea placeholder="Enter content" title="Enter content"  name="content" type="text" class="textarea_field" required></textarea>
            <button class="submit">Publish Post</button>
          </form>
        </div>
    </div>
    <script>
        // Update content placeholder based on post type
        document.querySelector('select[name="post_type"]').addEventListener('change', function() {
            const contentField = document.querySelector('textarea[name="content"]');
            if (this.value === 'BULLETIN') {
                contentField.placeholder = 'Enter bulletin content (announcement, event details, marketplace item, etc.)';
            } else if (this.value === 'FORUM') {
                contentField.placeholder = 'Enter your discussion topic or question to start the conversation';
            } else {
                contentField.placeholder = 'Enter content';
            }
        });

        // Form validation
        document.querySelector('.form').addEventListener('submit', function(e) {
            const postType = document.querySelector('select[name="post_type"]').value;
            const category = document.querySelector('select[name="category"]').value;
            
            if (!postType) {
                e.preventDefault();
                alert('Please select a post type.');
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category.');
                return false;
            }
        });
    </script>
</body>
</html>
