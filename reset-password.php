<?php
/**
 * FlowJM Password Reset Page
 * Handles password reset via token
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

$token = $_GET['token'] ?? '';
$errors = [];
$success = false;
$tokenValid = false;
$resetData = null;

// Verify token
if ($token) {
    $resetData = Auth::verifyPasswordResetToken($token);
    $tokenValid = $resetData !== false;
    
    if (!$tokenValid) {
        $errors['token'] = 'This password reset link is invalid or has expired.';
    }
} else {
    $errors['token'] = 'No password reset token provided.';
}

// Handle password reset
if ($_POST && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    // Validate CSRF token
    if (!Auth::verifyCsrfToken($csrfToken)) {
        $errors['csrf'] = 'Security token validation failed. Please try again.';
    }
    // Validate password
    elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    }
    elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    } else {
        // Update password
        $user = new User();
        $updated = $user->update($resetData['user_id'], ['password' => $password]);
        
        if ($updated) {
            // Delete the reset token
            Auth::deletePasswordResetToken($resetData['user_id']);
            
            // Log password reset
            Auth::logActivity('password_reset', 'Password reset completed', $resetData['user_id']);
            
            $success = true;
        } else {
            $errors['general'] = 'Failed to update password. Please try again.';
        }
    }
}

// Clean expired tokens
Auth::cleanExpiredTokens();

// Generate CSRF token
$csrfToken = Auth::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    
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
            <h1 class="text-3xl font-bold text-night-sky mb-2">Reset Password</h1>
            <p class="text-stone-gray">Create a new password</p>
        </div>
        
        <!-- Password Reset Form -->
        <div class="bg-white rounded-lg shadow-lg p-8 border border-stone-gray/10">
            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="text-center">
                    <div class="text-5xl mb-4">✅</div>
                    <h2 class="text-xl font-semibold text-night-sky mb-3">Password Updated</h2>
                    <p class="text-stone-gray mb-6">
                        Your password has been successfully updated. You can now log in with your new password.
                    </p>
                    <a href="/login.php" class="inline-block bg-sunrise-orange text-white py-3 px-6 rounded-lg font-medium hover:bg-trail-brown transition-colors">
                        Continue to Login
                    </a>
                </div>
            <?php elseif (!$tokenValid): ?>
                <!-- Invalid Token -->
                <div class="text-center">
                    <div class="text-5xl mb-4">❌</div>
                    <h2 class="text-xl font-semibold text-night-sky mb-3">Invalid Link</h2>
                    <p class="text-stone-gray mb-6">
                        <?php echo escapeContent($errors['token']); ?>
                    </p>
                    <p class="text-sm text-stone-gray mb-6">
                        Please request a new password reset link.
                    </p>
                    <a href="/forgot-password.php" class="inline-block bg-sunrise-orange text-white py-3 px-6 rounded-lg font-medium hover:bg-trail-brown transition-colors">
                        Request New Link
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="/reset-password.php?token=<?php echo escapeContent($token); ?>">
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo escapeContent($csrfToken); ?>">
                    
                    <!-- User Info -->
                    <div class="mb-6 text-center">
                        <p class="text-stone-gray text-sm">
                            Resetting password for: <strong><?php echo escapeContent($resetData['email']); ?></strong>
                        </p>
                    </div>
                    
                    <!-- Error Messages -->
                    <?php if (isset($errors['general']) || isset($errors['csrf'])): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm"><?php echo escapeContent($errors['general'] ?? $errors['csrf']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-night-sky mb-2">
                            New Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors <?php echo isset($errors['password']) ? 'border-red-300' : ''; ?>"
                            placeholder="Enter your new password"
                            autocomplete="new-password"
                        >
                        <?php if (isset($errors['password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo escapeContent($errors['password']); ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-xs text-stone-gray">
                            Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long
                        </p>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="mb-6">
                        <label for="password_confirm" class="block text-sm font-medium text-night-sky mb-2">
                            Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            required
                            class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors <?php echo isset($errors['password_confirm']) ? 'border-red-300' : ''; ?>"
                            placeholder="Confirm your new password"
                            autocomplete="new-password"
                        >
                        <?php if (isset($errors['password_confirm'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo escapeContent($errors['password_confirm']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-sunrise-orange text-white py-3 px-4 rounded-lg font-medium hover:bg-trail-brown focus:ring-2 focus:ring-sunrise-orange focus:ring-offset-2 transition-colors"
                    >
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-xs text-stone-gray">
            <p>&copy; 2025 FlowJM. Built for journey management.</p>
        </div>
    </div>
    
    <!-- Password confirmation script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            
            if (password && passwordConfirm) {
                // Real-time password confirmation validation
                passwordConfirm.addEventListener('input', function() {
                    if (password.value !== passwordConfirm.value) {
                        passwordConfirm.setCustomValidity('Passwords do not match');
                    } else {
                        passwordConfirm.setCustomValidity('');
                    }
                });
                
                password.addEventListener('input', function() {
                    if (password.value !== passwordConfirm.value && passwordConfirm.value !== '') {
                        passwordConfirm.setCustomValidity('Passwords do not match');
                    } else {
                        passwordConfirm.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>