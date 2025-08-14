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
$form_data = [
    'username' => '',
    'password' => '',
    'remember_me' => false
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
        $form_data['password'] = $_POST['password'] ?? '';
        $form_data['remember_me'] = isset($_POST['remember_me']);
        
        // Check for malicious input patterns
        if (!validateInputSecurity($form_data['username'], 'username')) {
            $errors[] = 'Invalid characters detected in input';
        }
        
        // Server-side validation
        if (empty($form_data['username'])) {
            $errors[] = 'Username or email is required';
        }
        
        if (empty($form_data['password'])) {
            $errors[] = 'Password is required';
        }
        
        // If no validation errors, attempt authentication
        if (empty($errors)) {
            $result = authenticateUser($form_data['username'], $form_data['password']);
            
            if ($result['success']) {
                // Login successful
                loginUser($result['user']['id'], $result['user']['username']);
                
                // Handle "Remember Me" functionality
                if ($form_data['remember_me']) {
                    // Set a longer session lifetime (30 days)
                    $expire_time = time() + (30 * 24 * 60 * 60);
                    session_set_cookie_params($expire_time);
                    
                    // Create a remember token (optional enhancement for future)
                    // This would typically involve creating a secure token in the database
                }
                
                // Redirect to intended page or homepage
                $redirect_url = $_GET['redirect'] ?? 'index.php';
                redirect($redirect_url, 'Welcome back, ' . $result['user']['username'] . '!', 'success');
            } else {
                $errors[] = $result['error'];
                
                // Add a small delay to prevent brute force attacks
                usleep(500000); // 0.5 second delay
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CommunityHub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-layout">
        <?php renderHeader('login'); ?>

        <main class="main-content">
            <div class="container">
                <div class="form-container">
                    <h1 class="page-title">Welcome Back</h1>
                    <p class="page-subtitle">Sign in to your account to continue</p>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                            <?php echo htmlspecialchars($flash['message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error" id="error-messages">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="login-form" method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" novalidate data-validate-form>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <!-- Honeypot field to catch bots -->
                        <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off">
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username or Email *</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                data-validate="required|no-script"
                                autocomplete="username"
                                autofocus
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
                                data-validate="required"
                                autocomplete="current-password"
                                maxlength="255"
                            >
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input 
                                    type="checkbox" 
                                    id="remember_me" 
                                    name="remember_me" 
                                    <?php echo $form_data['remember_me'] ? 'checked' : ''; ?>
                                >
                                <span class="checkbox-text">Remember me for 30 days</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary w-full" id="submit-btn">
                                <span class="btn-text">Sign In</span>
                                <span class="spinner hidden" id="loading-spinner"></span>
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-md">
                        <p>Don't have an account? <a href="register.php">Create one here</a></p>
                        <p><a href="#" class="text-sm">Forgot your password?</a></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/validation.js"></script>
    <script src="js/login.js"></script>
</body>
</html>