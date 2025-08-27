<?php
/**
 * FlowJM Login Page
 * Simple login form for accessing the application
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application root
define('FLOWJM_ROOT', __DIR__);

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'models/User.php';
require_once COMPONENTS_PATH . 'index.php';

// Initialize auth system (this handles session start)
Auth::init();
if (Auth::check()) {
    redirect('/');
}

$error = '';
$email = '';
$attempts = 0;

if ($_POST) {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    $rememberMe = !empty($_POST['remember']);
    
    // Validate CSRF token
    if (!Auth::verifyCsrfToken($csrfToken)) {
        $error = 'Security token validation failed. Please try again.';
    }
    // Check for brute force attempts
    elseif (Auth::checkBruteForce($email)) {
        $error = 'Too many failed attempts. Please wait 15 minutes before trying again.';
        Auth::logActivity('brute_force_attempt', "Blocked login attempt for email: $email");
    }
    // Validate input
    elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $user = new User();
        $authenticatedUser = $user->authenticate($email, $password);
        
        if ($authenticatedUser) {
            // Clear failed attempts
            Auth::clearFailedAttempts($email);
            
            // Log user in
            Auth::login($authenticatedUser);
            
            // Set remember me cookie if requested
            if ($rememberMe) {
                $rememberToken = bin2hex(random_bytes(32));
                setcookie('remember_token', $rememberToken, time() + (86400 * 30), '/', '', true, true); // 30 days
                // Store token in database for future implementation
            }
            
            // Redirect to intended page or dashboard
            $redirect = $_GET['redirect'] ?? '/';
            redirect($redirect);
        } else {
            // Record failed attempt
            Auth::recordFailedAttempt($email);
            Auth::logActivity('failed_login', "Failed login attempt for email: $email");
            $error = 'Invalid email or password.';
        }
    }
}

// Generate CSRF token for form
$csrfToken = Auth::generateCsrfToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
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
            <h1 class="text-3xl font-bold text-night-sky mb-2">FlowJM</h1>
            <p class="text-stone-gray">Welcome back to basecamp</p>
        </div>
        
        <!-- Login Form -->
        <div class="bg-white rounded-lg shadow-lg p-8 border border-stone-gray/10">
            <form method="POST" action="/login.php">
                <!-- CSRF Token -->
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo escapeContent($csrfToken); ?>">
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
                        placeholder="Enter your email"
                        autocomplete="email"
                    >
                </div>
                
                <!-- Password Field -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-night-sky mb-2">
                        Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                </div>
                
                <!-- Remember Me -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-sunrise-orange border-stone-gray/30 rounded focus:ring-sunrise-orange">
                        <span class="ml-2 text-sm text-stone-gray">Remember me</span>
                    </label>
                    
                    <a href="/forgot-password.php" class="text-sm text-lake-blue hover:text-sunrise-orange transition-colors">
                        Forgot password?
                    </a>
                </div>
                
                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="w-full bg-sunrise-orange text-white py-3 px-4 rounded-lg font-medium hover:bg-trail-brown focus:ring-2 focus:ring-sunrise-orange focus:ring-offset-2 transition-colors"
                >
                    Sign In to FlowJM
                </button>
            </form>
        </div>
        
        <!-- Demo Notice -->
        <div class="mt-8 p-4 bg-white/50 backdrop-blur-sm rounded-lg border border-stone-gray/10">
            <h3 class="text-sm font-medium text-night-sky mb-2">Demo Login</h3>
            <p class="text-xs text-stone-gray mb-2">
                For demonstration purposes, you can use:
            </p>
            <div class="text-xs font-mono text-forest-floor bg-white/70 p-2 rounded">
                Email: admin@flowjm.local<br>
                Password: ChangeMe123!
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-xs text-stone-gray">
            <p>&copy; 2025 FlowJM. Built for journey management.</p>
        </div>
    </div>
</body>
</html>