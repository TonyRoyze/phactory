<?php
include "./connector.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT * FROM user WHERE user_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        if ($user_data && $password == $user_data["pass"]) {
            if ($user_data["user_type"] == "ADMIN") {
                header(
                    "location: ./admin/manage-news.php?admin_id=$user_data[user_id]"
                );
                exit();
            } elseif ($user_data["user_type"] == "MEMBER") {
                header(
                    "location: ./home/community.php?user_id=$user_data[user_id]"
                );
                exit();
            }
        } else {
            $errorMessage = "Username or Password is Incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Community Bulletin</title>
        <link rel="stylesheet" href="login.css">
    </head>
<body>
    <div class="container">
        <div class="back-button">
            <a href="./index.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <!-- Back arrow icon placeholder -->
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                Back to Community
            </a>
        </div>
        
        <div class="form-container">
            <div class="form-header">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <!-- Login icon placeholder -->
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10,17 15,12 10,7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                </div>
                <h1 class="title">Welcome Back</h1>
                <p class="subtitle">Sign in to your community account</p>
            </div>

            <?php if (isset($errorMessage)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form class="form" method="post">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" class="input_field" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" class="input_field" required>
                </div>
                
                <button type="submit" class="submit">Sign In</button>
                
                <p class="signup-link">
                    Don't have an account? 
                    <a href="./signup.php">Join the community</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>