<?php
// This file is views/edit_post.php

if (!isset($_SESSION['user_id'])) {
    echo '<h1>Access Denied</h1>';
    exit(); // Use exit() instead of break for included files
}
require 'connector.php';
$post_id = $_GET['id'] ?? 0;
$post_data = null;

if ($post_id > 0) {
    $sql = "SELECT title, content, category, author_id FROM posts WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $post_data = $result->fetch_assoc();
        // Check if user is author or admin
        if ($post_data['author_id'] != $_SESSION['user_id'] && $_SESSION['user_type'] !== 'ADMIN') {
            echo '<h1>Access Denied</h1>';
            exit(); // Use exit() instead of break for included files
        }
    }
}

if ($post_data) {
    ?>
    <h1>Edit Post: <?= htmlspecialchars($post_data['title']) ?></h1>
    <form action="actions/update_post.php" method="POST" class="post-form">
        <input type="hidden" name="post_id" value="<?= $post_id ">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($post_data['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="10" required><?= htmlspecialchars($post_data['content']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select a category</option>
                <?php foreach ($all_categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($post_data['category'] === $cat ? 'selected' : '') ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
                <option value="Other" <?= (!in_array($post_data['category'], $all_categories) ? 'selected' : '') ?>>Other (specify below)</option>
            </select>
        </div>
        <div class="form-group" id="new-category-group" style="display:<?= (!in_array($post_data['category'], $all_categories) ? 'block' : 'none') ?>;">
            <label for="new_category">New Category</label>
            <input type="text" id="new_category" name="new_category" value="<?= (!in_array($post_data['category'], $all_categories) ? htmlspecialchars($post_data['category']) : '') ?>">
        </div>
        <button type="submit">Update Post</button>
    </form>
    <script>
        document.getElementById('category').addEventListener('change', function() {
            var newCategoryGroup = document.getElementById('new-category-group');
            if (this.value === 'Other') {
                newCategoryGroup.style.display = 'block';
                document.getElementById('new_category').setAttribute('required', 'required');
            } else {
                newCategoryGroup.style.display = 'none';
                document.getElementById('new_category').removeAttribute('required');
            }
        });
    </script>
    <?php
} else {
    echo '<h1>Post not found.</h1>';
}
