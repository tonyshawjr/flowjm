<?php
/**
 * FlowJM Password Reset Request Page
 * Handles password reset requests via email
 */

// Define application root
define('FLOWJM_ROOT', __DIR__);

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'models/User.php';
require_once INCLUDES_PATH . 'auth.php';

// Initialize auth and redirect if already logged in
Auth::init();
if (Auth::check()) {
    redirect('/');
}

$message = '';
$error = '';
$email = '';
$sent = false;

if ($_POST) {
    $email = sanitize_input($_POST['email'] ?? '');
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    // Validate CSRF token
    if (!Auth::verifyCsrfToken($csrfToken)) {
        $error = 'Security token validation failed. Please try again.';
    }
    // Validate email
    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if user exists
        $user = new User();
        $existingUser = $user->findByEmail($email);
        
        if ($existingUser) {
            // Generate password reset token
            $token = Auth::generatePasswordResetToken($existingUser['id']);
            
            // In a real application, you would send an email here
            // For now, we'll just show a success message
            $message = 'If an account with that email exists, we have sent a password reset link to your email address.';
            $sent = true;
            
            // Log password reset request
            Auth::logActivity('password_reset_request', 'Password reset requested for: ' . $email, $existingUser['id']);
            
            // For demo purposes, log the reset URL (remove in production)
            if (DEBUG) {
                $resetUrl = APP_URL . "/reset-password.php?token=" . $token;
                error_log("Password reset URL for {$email}: {$resetUrl}");
            }
        } else {
            // Don't reveal if email doesn't exist (security best practice)
            $message = 'If an account with that email exists, we have sent a password reset link to your email address.';
            $sent = true;
            
            // Log attempted reset for non-existent user
            Auth::logActivity('password_reset_attempt', 'Password reset attempted for non-existent email: ' . $email);
        }
    }
}

// Generate CSRF token
$csrfToken = Auth::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    
    <!-- Configure Tailwind with custom colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'canvas': '#F5F0E8',
                        'night-sky': '#3A3938',
                        'forest-floor': '#2D2C2B',
                        'stone-gray': '#6B6968',
                        'morning-mist': '#9B9998',
                        'sunrise-orange': '#EA580C',
                        'pine-green': '#059669',
                        'lake-blue': '#0284C7',
                        'trail-brown': '#92400E'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-canvas min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🏔️</div>
            <h1 class="text-3xl font-bold text-night-sky mb-2">Password Reset</h1>
            <p class="text-stone-gray">Get back on the trail</p>
        </div>
        
        <!-- Password Reset Form -->
        <div class="bg-white rounded-lg shadow-lg p-8 border border-stone-gray/10">
            <?php if ($sent): ?>
                <!-- Success Message -->
                <div class="text-center">
                    <div class="text-5xl mb-4">📧</div>
                    <h2 class="text-xl font-semibold text-night-sky mb-3">Check Your Email</h2>
                    <p class="text-stone-gray mb-6"><?php echo escapeContent($message); ?></p>
                    <p class="text-sm text-stone-gray mb-6">
                        Didn't receive an email? Check your spam folder or try again.
                    </p>
                    <a href="/login.php" class="inline-block bg-sunrise-orange text-white py-3 px-6 rounded-lg font-medium hover:bg-trail-brown transition-colors">
                        Back to Login
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="/forgot-password.php">
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo escapeContent($csrfToken); ?>">
                    
                    <!-- Instructions -->
                    <div class="mb-6 text-center">
                        <p class="text-stone-gray text-sm">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>
                    </div>
                    
                    <!-- Error Message -->
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm"><?php echo escapeContent($error); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Email Field -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-night-sky mb-2">
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo escapeContent($email); ?>"
                            required
                            class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors"
                            placeholder="Enter your email address"
                            autocomplete="email"
                        >
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-sunrise-orange text-white py-3 px-4 rounded-lg font-medium hover:bg-trail-brown focus:ring-2 focus:ring-sunrise-orange focus:ring-offset-2 transition-colors"
                    >
                        Send Reset Link
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Links -->
        <?php if (!$sent): ?>
        <div class="text-center mt-6">
            <p class="text-sm text-stone-gray">
                Remember your password? 
                <a href="/login.php" class="text-lake-blue hover:text-sunrise-orange font-medium transition-colors">
                    Sign in here
                </a>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-xs text-stone-gray">
            <p>&copy; 2025 FlowJM. Built for journey management.</p>
        </div>
    </div>
</body>
</html>