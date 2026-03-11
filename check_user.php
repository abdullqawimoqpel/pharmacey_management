<?php
// check_user.php - Check user data in database
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    exit('Access denied.');
}
session_start();

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    echo "<h3>👤 User Data Check</h3>";

    // Check admin user
    $query = "SELECT user_id, username, full_name, role, is_active FROM users WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin_user) {
        echo "<div class='alert alert-success'>";
        echo "<h4>Admin User Found:</h4>";
        echo "<pre>";
        print_r($admin_user);
        echo "</pre>";
        echo "</div>";

        // Check if password is correct
        $check_password = "SELECT password FROM users WHERE username = 'admin'";
        $stmt = $db->prepare($check_password);
        $stmt->execute();
        $password_hash = $stmt->fetchColumn();

        if (password_verify('admin123', $password_hash)) {
            echo "<div class='alert alert-success'>✅ Password is correct</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Password is incorrect</div>";
            echo "<p>Resetting password...</p>";

            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE username = 'admin'";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$new_hash]);
            echo "<div class='alert alert-success'>✅ Password reset to 'admin123'</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>❌ Admin user not found!</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='modules/auth/login.php' class='btn btn-primary'>Try Login Again</a></p>";
