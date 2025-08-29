<?php
// This file is views/create_post.php

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['ADMIN', 'MEMBER'])) {
    echo '<h1>Access Denied</h1>';
    exit(); // Use exit() instead of break for included files
}
?>
<h1>Create a New Post</h1>
<form action="actions/create_post.php" method="POST" class="post-form">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
        <label for="category">Category</label>
        <div class="select-wrapper">
            <select id="category" name="category" required>
                <option value="">Select a category</option>
                <?php foreach ($all_categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
                <option value="Other">Other (specify below)</option>
            </select>
        </div>
    </div>
    <div class="form-group" id="new-category-group" style="display:none;">
        <label for="new_category">New Category</label>
        <input type="text" id="new_category" name="new_category">
    </div>
    <div class="form-group">
        <label class="checkbox-label">
            <input type="checkbox" id="is_bulletin" name="is_bulletin" value="1">
            Make this a bulletin post
        </label>
        <p class="form-help">Bulletin posts appear in the featured carousel on the posts page</p>
    </div>
    <div class="form-group">
        <label for="content">Content</label>
        <textarea id="content" name="content" rows="10" required></textarea>
    </div>
    <button type="submit">Create Post</button>
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
