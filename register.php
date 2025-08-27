<?php
/**
 * FlowJM Registration Page
 * User registration form for new accounts
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

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => ''
];

if ($_POST) {
    // Get and sanitize form data
    $formData['name'] = sanitize_input($_POST['name'] ?? '');
    $formData['email'] = sanitize_input($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['password_confirm'] = $_POST['password_confirm'] ?? '';
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    // Validate CSRF token
    if (!Auth::verifyCsrfToken($csrfToken)) {
        $errors['csrf'] = 'Security token validation failed. Please try again.';
    } else {
        // Basic validation
        if (empty($formData['name']) || strlen(trim($formData['name'])) < 2) {
            $errors['name'] = 'Full name must be at least 2 characters long.';
        }
        
        if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        
        if (strlen($formData['password']) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        
        if ($formData['password'] !== $formData['password_confirm']) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
        
        // Check if email already exists
        if (empty($errors['email'])) {
            $user = new User();
            if ($user->emailExists($formData['email'])) {
                $errors['email'] = 'An account with this email address already exists.';
            }
        }
        
        // Validate using model validation
        if (empty($errors)) {
            $userModel = new User();
            $modelErrors = $userModel->validate($formData);
            $errors = array_merge($errors, $modelErrors);
        }
        
        // Create user if no errors
        if (empty($errors)) {
            $userModel = new User();
            $userData = [
                'name' => $formData['name'],
                'email' => $formData['email'],
                'password' => $formData['password'],
                'role' => 'user'
            ];
            
            $userId = $userModel->create($userData);
            
            if ($userId) {
                // Auto-login the new user
                $newUser = $userModel->findById($userId);
                Auth::login($newUser);
                
                // Log registration
                Auth::logActivity('register', 'New user registration: ' . $formData['email'], $userId);
                
                // Set success flash message
                set_flash('success', 'Welcome to FlowJM! Your account has been created successfully.');
                
                // Redirect to dashboard
                redirect('/');
            } else {
                $errors['general'] = 'Registration failed. Please try again.';
            }
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
    <title>Register - <?php echo APP_NAME; ?></title>
    
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
            <h1 class="text-3xl font-bold text-night-sky mb-2">Join FlowJM</h1>
            <p class="text-stone-gray">Set up your basecamp</p>
        </div>
        
        <!-- Registration Form -->
        <div class="bg-white rounded-lg shadow-lg p-8 border border-stone-gray/10">
            <form method="POST" action="/register.php" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo escapeContent($csrfToken); ?>">
                
                <!-- General Error Message -->
                <?php if (isset($errors['general']) || isset($errors['csrf'])): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-700 text-sm"><?php echo escapeContent($errors['general'] ?? $errors['csrf']); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Full Name Field -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-night-sky mb-2">
                        Full Name
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?php echo escapeContent($formData['name']); ?>"
                        required
                        class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors <?php echo isset($errors['name']) ? 'border-red-300' : ''; ?>"
                        placeholder="Enter your full name"
                        autocomplete="name"
                    >
                    <?php if (isset($errors['name'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo escapeContent($errors['name']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Email Field -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-night-sky mb-2">
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo escapeContent($formData['email']); ?>"
                        required
                        class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors <?php echo isset($errors['email']) ? 'border-red-300' : ''; ?>"
                        placeholder="Enter your email"
                        autocomplete="email"
                    >
                    <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo escapeContent($errors['email']); ?></p>
                    <?php endif; ?>
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
                        minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                        class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors <?php echo isset($errors['password']) ? 'border-red-300' : ''; ?>"
                        placeholder="Create a password"
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
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required
                        class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors <?php echo isset($errors['password_confirm']) ? 'border-red-300' : ''; ?>"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                    >
                    <?php if (isset($errors['password_confirm'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo escapeContent($errors['password_confirm']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Terms Agreement -->
                <div class="mb-6">
                    <label class="flex items-start">
                        <input 
                            type="checkbox" 
                            name="terms" 
                            required
                            class="w-4 h-4 text-sunrise-orange border-stone-gray/30 rounded focus:ring-sunrise-orange mt-1"
                        >
                        <span class="ml-2 text-sm text-stone-gray">
                            I agree to the <a href="/terms.php" class="text-lake-blue hover:text-sunrise-orange" target="_blank">Terms of Service</a> and <a href="/privacy.php" class="text-lake-blue hover:text-sunrise-orange" target="_blank">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <!-- Register Button -->
                <button 
                    type="submit" 
                    class="w-full bg-sunrise-orange text-white py-3 px-4 rounded-lg font-medium hover:bg-trail-brown focus:ring-2 focus:ring-sunrise-orange focus:ring-offset-2 transition-colors"
                >
                    Create Account
                </button>
            </form>
        </div>
        
        <!-- Login Link -->
        <div class="text-center mt-6">
            <p class="text-sm text-stone-gray">
                Already have an account? 
                <a href="/login.php" class="text-lake-blue hover:text-sunrise-orange font-medium transition-colors">
                    Sign in here
                </a>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-xs text-stone-gray">
            <p>&copy; 2025 FlowJM. Built for journey management.</p>
        </div>
    </div>
    
    <!-- Password strength indicator script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            
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
        });
    </script>
</body>
</html>