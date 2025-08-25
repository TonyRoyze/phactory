
<?php
session_start();

// If user is already logged in, redirect to app.php
if (isset($_SESSION['user_id'])) {
    header("Location: app.php");
    exit();
}

require 'connector.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['login_input']);
    $password = $_POST['password'];

    // Check if input is email or username
    $is_email = filter_var($login_input, FILTER_VALIDATE_EMAIL);
    
    if ($is_email) {
        // Login with email
        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
    } else {
        // Login with username
        $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login_input, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['user_role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        header("Location: app.php");
        exit();
    } else {
        $message = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Helpdesk</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1>Helpdesk Login</h1>
        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="login_input">Username or Email</label>
                <input type="text" id="login_input" name="login_input" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="signup-link">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p>
        </div>
    </div>
</body>
</html>
