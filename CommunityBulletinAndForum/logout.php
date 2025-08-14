<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Initialize session
initializeSession();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php', 'You are not logged in', 'info');
}

// Handle logout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for security
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Get username for goodbye message
        $current_user = getCurrentUser();
        $username = $current_user ? $current_user['username'] : 'User';
        
        // Logout user and destroy session
        logoutUser();
        
        // Redirect with goodbye message
        redirect('index.php', 'Goodbye, ' . $username . '! You have been logged out successfully.', 'success');
    } else {
        redirect('index.php', 'Invalid security token', 'error');
    }
}

// If GET request, show confirmation page
$current_user = getCurrentUser();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - CommunityHub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-layout">
        <?php renderHeader('logout'); ?>

        <main class="main-content">
            <div class="container">
                <div class="form-container">
                    <h1 class="page-title">Confirm Logout</h1>
                    <p class="page-subtitle">Are you sure you want to sign out of your account?</p>

                    <div class="card">
                        <div class="card-content">
                            <?php if ($current_user): ?>
                                <p>You are currently signed in as <strong><?php echo htmlspecialchars($current_user['username']); ?></strong></p>
                                <p>Logging out will end your current session and you'll need to sign in again to access member features.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" action="logout.php" class="mt-md">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Yes, Sign Me Out
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>