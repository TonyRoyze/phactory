
<?php
session_start();

require 'connector.php';

// Fetch distinct categories for select inputs
$all_categories = [];
$categories_sql = "SELECT DISTINCT category FROM posts ORDER BY category ASC";
$categories_result = $conn->query($categories_sql);
if ($categories_result->num_rows > 0) {
    while($row = $categories_result->fetch_assoc()) {
        $all_categories[] = $row['category'];
    }
}

$view = $_GET['view'] ?? 'posts'; // Default view

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav class="top-nav">
        <div class="nav-container">
            <a href="app.php" class="nav-brand">Forum </a>
            <ul class="nav-links">
                <li><a href="app.php?view=posts" class="<?= ($view === 'posts' || $view === 'post') ? 'active' : '' ?>">Posts</a></li>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN'): ?>
                    <li><a href="app.php?view=users" class="<?= ($view === 'users') ? 'active' : '' ?>">Manage Users</a></li>
                <?php endif; ?>
            </ul>
            <ul class="nav-user">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="app.php?view=edit_profile" class="<?= ($view === 'edit_profile') ? 'active' : '' ?>">Edit Profile</a></li>
                    <li><a href="logout.php" class="btn-signup">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-signup" style="margin-right: 5px">Login</a></li>
                    <li><a href="signup.php" class="btn-signup">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <div class="container">
            <?php
            // Based on the view parameter, include the corresponding content
            switch ($view) {
                case 'users':
                    require 'views/users.php';
                    break;
                case 'post':
                    require 'views/post.php';
                    break;
                case 'edit_user':
                    require 'views/edit_user.php';
                break;
                case 'edit_profile':
                    require 'views/edit_profile.php';
                break;
                case 'edit_post':
                    require 'views/edit_post.php';
                    break;
                case 'create_post':
                    require 'views/create_post.php';
                    break;
                case 'posts':
                default:
                    require 'views/posts.php';
                    break;
            }
            ?>
        </div>
    </div>

</body>
</html>
