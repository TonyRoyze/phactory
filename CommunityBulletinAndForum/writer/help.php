<?php global $conn;
include "../connector.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Community Member Help - Community Bulletin</title>
        <link rel="stylesheet" href="writer.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="help-content">
        <h1 class="help-title">Community Member Help</h1>
        <section class="help-section">
            <h2 class="help-subtitle">Getting Started</h2>
            <p>Welcome to the Community Bulletin & Forum help page. Here you'll find information on how to participate in our community effectively.</p>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Creating Posts</h2>
            <ol class="help-list">
                <li>Click on "Create New Post" from your profile.</li>
                <li>Choose between "Bulletin Post" (announcements, events, marketplace) or "Forum Topic" (discussions).</li>
                <li>Select the appropriate category: General, Events, Marketplace, or Discussions.</li>
                <li>Fill in the title and content with clear, descriptive information.</li>
                <li>Click "Publish Post" to share with the community.</li>
            </ol>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Participating in Discussions</h2>
            <ol class="help-list">
                <li>Browse forum topics on the community home page or by category.</li>
                <li>Click on a topic title to view the full discussion.</li>
                <li>Scroll to the bottom to add your reply to the conversation.</li>
                <li>Keep discussions respectful and on-topic.</li>
            </ol>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Managing Your Content</h2>
            <ol class="help-list">
                <li>Go to "My Profile" to see all your posts and replies.</li>
                <li>Click "Edit" to modify your content or "Delete" to remove it.</li>
                <li>Remember that deleting a forum topic will also remove all replies.</li>
            </ol>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Community Guidelines</h2>
            <ul class="help-list">
                <li>Keep your posts clear and relevant to the selected category.</li>
                <li>Use descriptive titles that help others understand your content.</li>
                <li>Be respectful and constructive in discussions.</li>
                <li>Use the marketplace category for buying/selling items.</li>
                <li>Post events in the Events category with clear dates and details.</li>
            </ul>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Need More Help?</h2>
            <p>If you have any questions or need further assistance, please contact our community moderators at <a href="mailto:community@bulletin.com" class="help-link">community@bulletin.com</a>.</p>
        </section>
    </div>
</body>
</html>
