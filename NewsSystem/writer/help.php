<?php global $conn;
include "../connector.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Writer Help - Online News</title>
        <link rel="stylesheet" href="writer.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="help-content">
        <h1 class="help-title">Writer Help</h1>
        <section class="help-section">
            <h2 class="help-subtitle">Getting Started</h2>
            <p>Welcome to the Online News writer's help page. Here you'll find information on how to use the writing system effectively.</p>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Writing an Article</h2>
            <ol class="help-list">
                <li>Click on "New Article".</li>
                <li>Fill in the article title, category, and content.</li>
                <li>Click "Submit" to make it live.</li>
            </ol>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Editing Existing Articles</h2>
            <ol class="help-list">
                <li>Go to "Manage Articles" in the navigation menu.</li>
                <li>Click on the article you want to edit.</li>
                <li>Make your changes and click "Submit" to save.</li>
            </ol>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Tips for Good Writing</h2>
            <ul class="help-list">
                <li>Keep your headlines clear and concise.</li>
                <li>Use short paragraphs for easy readability.</li>
                <li>Always fact-check your information before publishing.</li>
            </ul>
        </section>
        
        <section class="help-section">
            <h2 class="help-subtitle">Need More Help?</h2>
            <p>If you have any questions or need further assistance, please contact our support team at <a href="mailto:support@onlinenews.com" class="help-link">support@onlinenews.com</a>.</p>
        </section>
    </div>
</body>
</html>
