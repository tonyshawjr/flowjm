<?php
/**
 * Session Test File
 * Diagnose session issues
 */

session_start();

echo "<h1>Session Test</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "\nSession Data:\n";
print_r($_SESSION);
echo "\n\nServer Info:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "</pre>";

// Test setting a session variable
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

echo "<p>Test Counter: " . $_SESSION['test_counter'] . "</p>";
echo "<p>Refresh this page to see if the counter increments (testing session persistence)</p>";

echo "<hr>";
echo "<a href='/login.php'>Go to Login</a> | ";
echo "<a href='/index.php'>Go to Dashboard</a> | ";
echo "<a href='/logout.php'>Logout</a>";
?>