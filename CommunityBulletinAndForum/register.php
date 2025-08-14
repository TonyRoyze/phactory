<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Initialize session
initializeSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php', 'You are already logged in', 'info');
}

$errors = [];
$success = false;
$form_data = [
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif (!validateHoneypot()) {
        $errors[] = 'Bot detected. Please try again.';
    } else {
        // Sanitize and validate input
        $form_data['username'] = sanitizeInputAdvanced($_POST['username'] ?? '');
        $form_data['email'] = sanitizeInputAdvanced($_POST['email'] ?? '');
        $form_data['password'] = $_POST['password'] ?? '';
        $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';
        
        // Check for malicious input patterns
        if (!validateInputSecurity($form_data['username'], 'username')) {
            $errors[] = 'Invalid characters detected in username';
        }
        if (!validateInputSecurity($form_data['email'], 'email')) {
            $errors[] = 'Invalid characters detected in email';
        }
        
        // Server-side validation
        if (empty($form_data['username'])) {
            $errors[] = 'Username is required';
        } elseif (!validateUsername($form_data['username'])) {
            $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores';
        } elseif (usernameExists($form_data['username'])) {
            $errors[] = 'Username already exists';
        }
        
        if (empty($form_data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!validateEmail($form_data['email'])) {
            $errors[] = 'Please enter a valid email address';
        } elseif (emailExists($form_data['email'])) {
            $errors[] = 'Email already exists';
        }
        
        if (empty($form_data['password'])) {
            $errors[] = 'Password is required';
        } elseif (!validatePassword($form_data['password'])) {
            $errors[] = 'Password must be at least 8 characters long and contain both letters and numbers';
        }
        
        if (empty($form_data['confirm_password'])) {
            $errors[] = 'Please confirm your password';
        } elseif ($form_data['password'] !== $form_data['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, create the user
        if (empty($errors)) {
            $result = createUser($form_data['username'], $form_data['email'], $form_data['password']);
            
            if ($result['success']) {
                // Auto-login the user after successful registration
                loginUser($result['user_id'], $form_data['username']);
                redirect('index.php', 'Welcome to CommunityHub! Your account has been created successfully.', 'success');
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CommunityHub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-layout">
        <?php renderHeader('register'); ?>

        <main class="main-content">
            <div class="container">
                <div class="form-container">
                    <h1 class="page-title">Create Account</h1>
                    <p class="page-subtitle">Join our community and start connecting with others</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error" id="error-messages">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="register-form" method="POST" action="register.php" novalidate data-validate-form>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <!-- Honeypot field to catch bots -->
                        <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off">
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username *</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                data-validate="required|username|no-script"
                                autocomplete="username"
                                maxlength="20"
                            >
                            <div class="form-help">3-20 characters, letters, numbers, and underscores only</div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                data-validate="required|email|no-script"
                                autocomplete="email"
                                maxlength="255"
                            >
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                data-validate="required|password"
                                autocomplete="new-password"
                                maxlength="255"
                            >
                            <div class="form-help">At least 8 characters with letters and numbers</div>
                            <div class="password-strength" id="password-strength"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                data-validate="required|password-confirm"
                                autocomplete="new-password"
                                maxlength="255"
                            >
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary w-full" id="submit-btn">
                                <span class="btn-text">Create Account</span>
                                <span class="spinner hidden" id="loading-spinner"></span>
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-md">
                        <p>Already have an account? <a href="login.php">Sign in here</a></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/validation.js"></script>
    <script src="js/register.js"></script>
</body>
</html>