<?php
// quick_fix.php - Fix session and database issues
session_start();

echo "<h3>🔧 Pharmacy System Quick Fix</h3>";

// Check session
echo "<h4>Session Status:</h4>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
    echo "✅ Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
    echo "✅ Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    echo "✅ Full Name: " . ($_SESSION['full_name'] ?? 'Not set') . "<br>";
} else {
    echo "❌ No active session. Please <a href='modules/auth/login.php'>login</a>.<br>";
}

// Check database connection
echo "<h4>Database Connection:</h4>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";

    // Check if users table exists and has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Users table: " . $result['count'] . " users found<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='modules/auth/login.php' class='btn btn-primary'>Go to Login</a></p>";
echo "<p><a href='install.php' class='btn btn-warning'>Re-run Installation</a></p>";
