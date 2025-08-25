
<?php
session_start();

require 'connector.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set default view based on user role
$default_view = ($_SESSION['user_role'] === 'ADMIN') ? 'dashboard' : 'my_tickets';
$view = $_GET['view'] ?? $default_view;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav class="top-nav">
        <div class="nav-container">
            <a href="app.php" class="nav-brand">Helpdesk System</a>
            <ul class="nav-links">
                <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
                    <li><a href="app.php?view=dashboard" class="<?= ($view === 'dashboard') ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="app.php?view=tickets" class="<?= ($view === 'tickets' || $view === 'ticket') ? 'active' : '' ?>">All Tickets</a></li>
                    <li><a href="app.php?view=users" class="<?= ($view === 'users') ? 'active' : '' ?>">Manage Users</a></li>
                <?php else: ?>
                    <li><a href="app.php?view=my_tickets" class="<?= ($view === 'my_tickets' || $view === 'ticket') ? 'active' : '' ?>">My Tickets</a></li>
                    <li><a href="app.php?view=create_ticket" class="<?= ($view === 'create_ticket') ? 'active' : '' ?>">Create Ticket</a></li>
                <?php endif; ?>
            </ul>
            <ul class="nav-user">
                <li><span class="user-welcome">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span></li>
                <li><a href="app.php?view=edit_profile" class="<?= ($view === 'edit_profile') ? 'active' : '' ?>">Edit Profile</a></li>
                <li><a href="logout.php" class="btn-signup">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <div class="container">
            <?php
            // Based on the view parameter, include the corresponding content
            switch ($view) {
                case 'dashboard':
                    if ($_SESSION['user_role'] === 'ADMIN') {
                        echo "<h1>Admin Dashboard</h1><p>Dashboard functionality will be implemented in a future task.</p>";
                    } else {
                        header("Location: app.php?view=my_tickets");
                        exit();
                    }
                    break;
                case 'tickets':
                    if ($_SESSION['user_role'] === 'ADMIN') {
                        echo "<h1>All Tickets</h1><p>Ticket management functionality will be implemented in a future task.</p>";
                    } else {
                        header("Location: app.php?view=my_tickets");
                        exit();
                    }
                    break;
                case 'my_tickets':
                    if ($_SESSION['user_role'] === 'CUSTOMER') {
                        require 'views/my_tickets.php';
                    } else {
                        header("Location: app.php?view=dashboard");
                        exit();
                    }
                    break;
                case 'create_ticket':
                    if ($_SESSION['user_role'] === 'CUSTOMER') {
                        require 'views/create_ticket.php';
                    } else {
                        header("Location: app.php?view=dashboard");
                        exit();
                    }
                    break;
                case 'ticket':
                    require 'views/ticket.php';
                    break;
                case 'users':
                    if ($_SESSION['user_role'] === 'ADMIN') {
                        require 'views/users.php';
                    } else {
                        header("Location: app.php?view=my_tickets");
                        exit();
                    }
                    break;
                case 'edit_user':
                    if ($_SESSION['user_role'] === 'ADMIN') {
                        require 'views/edit_user.php';
                    } else {
                        header("Location: app.php?view=my_tickets");
                        exit();
                    }
                    break;
                case 'edit_profile':
                    require 'views/edit_profile.php';
                    break;
                default:
                    // Redirect to appropriate default view
                    $redirect_view = ($_SESSION['user_role'] === 'ADMIN') ? 'dashboard' : 'my_tickets';
                    header("Location: app.php?view=" . $redirect_view);
                    exit();
                    break;
            }
            ?>
        </div>
    </div>

</body>
</html>
