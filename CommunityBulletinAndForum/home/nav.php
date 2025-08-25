<?php
// Check if user is logged in via URL parameter
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_param = $user_id > 0 ? "&user_id=$user_id" : "";

// Get current category for active state
$current_category = isset($_GET['category']) ? $_GET['category'] : '';
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info if logged in
$user_name = "";
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT user_name FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_name = $user_data['user_name'];
    }
}

// Function to determine if a nav item is active
function isActive($category = '', $current_category = '', $current_page = '') {
    if ($current_page === 'community.php') {
        if (empty($category) && empty($current_category)) {
            return 'active';
        } elseif ($category === $current_category) {
            return 'active';
        }
    }
    return '';
}

echo '
<nav class="navbar">
    <div class="logo">Community Bulletin</div>
    <ul class="nav-links">
        <li><a href="community.php' . ($user_param ? '?user_id=' . $user_id : '') . '" class="' . isActive('', $current_category, $current_page) . '">Home</a></li>
        <li><a href="community.php?category=General' . $user_param . '" class="' . isActive('General', $current_category, $current_page) . '">General</a></li>
        <li><a href="community.php?category=Events' . $user_param . '" class="' . isActive('Events', $current_category, $current_page) . '">Events</a></li>
        <li><a href="community.php?category=Marketplace' . $user_param . '" class="' . isActive('Marketplace', $current_category, $current_page) . '">Marketplace</a></li>
        <li><a href="community.php?category=Discussions' . $user_param . '" class="' . isActive('Discussions', $current_category, $current_page) . '">Discussions</a></li>';

if ($user_id > 0 && !empty($user_name)) {
    echo '<li><a href="../writer/writer.php?writer_id=' . $user_id . '" style="color: #ffcc00;">Welcome, ' . htmlspecialchars($user_name) . '</a></li>';
    echo '<li><a href="../login.php" class="login-btn">Logout</a></li>';
} else {
    echo '<li><a href="../login.php" class="login-btn">Login</a></li>';
}

echo '
    </ul>
</nav>
';
?>
