<?php global $conn;
include "./connector.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $repass = $_POST["repassword"];

    if ($password == $repass) {
        $sql = "INSERT INTO user (user_name, pass, user_type) VALUES ('$username', '$password','MEMBER') ";
        $result = $conn->query($sql);
    }

    header("location: ./login.php");
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Join Community - Community Bulletin</title>
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
                        <!-- User plus icon placeholder -->
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                </div>
                <h1 class="title">Join Our Community</h1>
                <p class="subtitle">Create your account to get started</p>
            </div>

            <form class="form" method="post">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" class="input_field" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" class="input_field" required>
                </div>
                
                <div class="input-group">
                    <label for="repassword">Confirm Password</label>
                    <input id="repassword" name="repassword" type="password" class="input_field" required>
                </div>
                
                <button type="submit" class="submit">Create Account</button>
                
                <p class="signup-link">
                    Already have an account? 
                    <a href="./login.php">Sign in</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
