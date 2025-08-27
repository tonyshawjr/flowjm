<?php
/**
 * Fix Admin Password
 * Updates the admin password to the correct hash
 */

// Define application root
define('FLOWJM_ROOT', __DIR__);

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Generate proper password hash for 'ChangeMe123!'
    $password = 'ChangeMe123!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h1>Fix Admin Password</h1>";
    echo "<p>Generating hash for password: ChangeMe123!</p>";
    echo "<p>Hash: <code>$hash</code></p>";
    
    // Update the admin user's password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $result = $stmt->execute([$hash, 'admin@flowjm.local']);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Password updated successfully!</p>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li>Email: admin@flowjm.local</li>";
        echo "<li>Password: ChangeMe123!</li>";
        echo "</ul>";
        echo "<p><a href='/login.php'>Go to Login</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to update password</p>";
    }
    
    // Also show current users
    echo "<hr>";
    echo "<h2>Current Users in Database:</h2>";
    $stmt = $pdo->query("SELECT id, email, name, role, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database. Creating admin user...</p>";
        
        // Create admin user if none exists
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, name, role, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([
            'admin@flowjm.local',
            $hash,
            'Admin User',
            'admin'
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin user</p>";
            print_r($stmt->errorInfo());
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>