
<?php
session_start();

require 'connector.php';

$view = $_GET['view'] ?? 'events'; // Default view

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav class="top-nav">
        <div class="nav-container">
            <a href="app.php" class="nav-brand">events<span class="asterisk">*</span></a>
            <ul class="nav-links">
                <li><a href="app.php?view=events" class="<?= ($view === 'events' || $view === 'event') ? 'active' : '' ?>">Events</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                    <li><a href="app.php?view=users" class="<?= ($view === 'users') ? 'active' : '' ?>">Manage Users</a></li>
                <?php endif; ?>
            </ul>
            <ul class="nav-user">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="app.php?view=edit_profile" class="<?= ($view === 'edit_profile') ? 'active' : '' ?>" style="display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-user-icon lucide-circle-user"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>
                    </a></li>
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
                case 'event':
                    require 'views/event.php';
                    break;
                case 'edit_user':
                    require 'views/edit_user.php';
                break;
                case 'edit_profile':
                    require 'views/edit_profile.php';
                break;
                case 'edit_event':
                    require 'views/edit_event.php';
                    break;
                case 'create_event':
                    require 'views/create_event.php';
                    break;
                case 'events':
                default:
                    require 'views/events.php';
                    break;
            }
            ?>
        </div>
    </div>

</body>
</html>
