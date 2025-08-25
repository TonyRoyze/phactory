
<?php
session_start();

require 'connector.php';

$message = '';

// Email validation function
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $message = "All fields are required!";
    } elseif (strlen($username) < 3 || strlen($username) > 100) {
        $message = "Username must be between 3 and 100 characters!";
    } elseif (!validateEmail($email)) {
        $message = "Please enter a valid email address!";
    } elseif (strlen($email) > 255) {
        $message = "Email address is too long!";
    } elseif (strlen($full_name) > 255) {
        $message = "Full name is too long!";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        // Check if username or email already exists
        $sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or email already exists!";
        } else {
            // Insert new user (default to CUSTOMER role)
            $user_role = 'CUSTOMER';
            $sql = "INSERT INTO users (username, email, password, full_name, user_role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $password, $full_name, $user_role);

            if ($stmt->execute()) {
                // Log the user in and redirect to home page (app.php)
                $new_user_id = $conn->insert_id;
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $user_role;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                header("Location: app.php");
                exit();
            } else {
                $message = "Error: Could not create account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Helpdesk</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1>Create an Account</h1>
        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="100">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit">Sign Up</button>
        </form>
        <div class="signup-link">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        // Client-side form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const email = document.getElementById('email');
            
            // Real-time password confirmation validation
            confirmPassword.addEventListener('input', function() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
            
            // Email format validation
            email.addEventListener('input', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.value)) {
                    email.setCustomValidity('Please enter a valid email address');
                } else {
                    email.setCustomValidity('');
                }
            });
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password.value.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
