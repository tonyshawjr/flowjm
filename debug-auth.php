<?php
/**
 * Debug Authentication Issues
 */

// Define application root
define('FLOWJM_ROOT', __DIR__);

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'auth.php';

// Initialize auth
Auth::init();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Authentication Debug</h1>
    
    <div class="section">
        <h2>Session Information</h2>
        <p>Session ID: <?php echo session_id(); ?></p>
        <p>Session Status: <?php echo session_status(); ?> 
            <?php 
            switch(session_status()) {
                case PHP_SESSION_DISABLED:
                    echo '<span class="error">(Sessions are disabled)</span>';
                    break;
                case PHP_SESSION_NONE:
                    echo '<span class="warning">(Session not started)</span>';
                    break;
                case PHP_SESSION_ACTIVE:
                    echo '<span class="success">(Session active)</span>';
                    break;
            }
            ?>
        </p>
        <p>Session Save Path: <?php echo session_save_path() ?: 'Default'; ?></p>
        <p>Session Cookie Name: <?php echo session_name(); ?></p>
    </div>
    
    <div class="section">
        <h2>Authentication Status</h2>
        <p>Logged In: <?php echo Auth::check() ? '<span class="success">YES</span>' : '<span class="error">NO</span>'; ?></p>
        <?php if (Auth::check()): ?>
            <?php $user = Auth::user(); ?>
            <p>User ID: <?php echo $user['id'] ?? 'Not set'; ?></p>
            <p>Email: <?php echo $user['email'] ?? 'Not set'; ?></p>
            <p>Name: <?php echo $user['name'] ?? 'Not set'; ?></p>
            <p>Role: <?php echo $user['role'] ?? 'Not set'; ?></p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="section">
        <h2>Cookie Data</h2>
        <pre><?php print_r($_COOKIE); ?></pre>
    </div>
    
    <div class="section">
        <h2>Actions</h2>
        <p>
            <a href="/login.php">Go to Login</a> | 
            <a href="/index.php">Go to Dashboard</a> | 
            <a href="/logout.php">Logout</a> |
            <a href="/debug-auth.php">Refresh</a>
        </p>
    </div>
    
    <div class="section">
        <h2>Test Login</h2>
        <p>To test login, use:</p>
        <ul>
            <li>Email: admin@flowjm.local</li>
            <li>Password: ChangeMe123!</li>
        </ul>
    </div>
</body>
</html>