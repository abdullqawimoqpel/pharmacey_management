<?php
// session_fix.php - Fix session issues
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    exit('Access denied.');
}
session_start();

echo "<h3>🔧 Session Repair Tool</h3>";

// Check current session
echo "<h4>Current Session Status:</h4>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Fix session if user_id exists but role/full_name are missing
if (isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || !isset($_SESSION['full_name']))) {
    echo "<h4>🔧 Fixing Session...</h4>";

    try {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();

        $user_id = $_SESSION['user_id'];
        $query = "SELECT username, full_name, role FROM users WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Set missing session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            echo "<div class='alert alert-success'>";
            echo "✅ Session repaired successfully!<br>";
            echo "Username: " . $user['username'] . "<br>";
            echo "Full Name: " . $user['full_name'] . "<br>";
            echo "Role: " . $user['role'] . "<br>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>User not found in database. Session destroyed.</div>";
            session_destroy();
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='alert alert-info'>No session issues detected or no user logged in.</div>";
}

echo "<hr>";
echo "<p><a href='modules/dashboard/' class='btn btn-success'>Go to Dashboard</a> ";
echo "<a href='modules/auth/logout.php' class='btn btn-danger'>Logout</a> ";
echo "<a href='modules/auth/login.php' class='btn btn-primary'>Login Page</a></p>";
