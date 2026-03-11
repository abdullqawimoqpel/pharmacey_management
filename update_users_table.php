<?php
// update_users_table.php - Add missing columns to users table
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pharmacy_management';

$success_messages = [];
$error_messages = [];

try {
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use the database
    $pdo->exec("USE `$db_name`");

    echo "<h3>🔧 Users Table Update Tool</h3>";

    // Check and add missing columns to users table
    $columns_to_add = [
        'phone' => "VARCHAR(20) NULL",
        'last_login' => "TIMESTAMP NULL",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    foreach ($columns_to_add as $column_name => $column_definition) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$column_name]);

            if ($stmt->rowCount() == 0) {
                // Column doesn't exist, add it
                $sql = "ALTER TABLE users ADD COLUMN $column_name $column_definition";
                $pdo->exec($sql);
                $success_messages[] = "✅ Added column '$column_name' to users table";
            } else {
                $success_messages[] = "✅ Column '$column_name' already exists";
            }
        } catch (PDOException $e) {
            $error_messages[] = "❌ Error with column '$column_name': " . $e->getMessage();
        }
    }

    // Check if login_attempts table exists
    try {
        $pdo->query("SELECT 1 FROM login_attempts LIMIT 1");
        $success_messages[] = "✅ login_attempts table exists";
    } catch (PDOException $e) {
        // Create login_attempts table
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username_time (username, attempt_time)
        )";
        $pdo->exec($sql);
        $success_messages[] = "✅ Created login_attempts table";
    }
} catch (PDOException $e) {
    $error_messages[] = "Database connection error: " . $e->getMessage();
}

// Display results
echo "<div class='container mt-4'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'><h4>Database Update Results</h4></div>";
echo "<div class='card-body'>";

if (!empty($success_messages)) {
    echo "<div class='alert alert-success'>";
    echo "<h5>Successful Operations:</h5>";
    foreach ($success_messages as $msg) {
        echo "<div>$msg</div>";
    }
    echo "</div>";
}

if (!empty($error_messages)) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>Errors:</h5>";
    foreach ($error_messages as $msg) {
        echo "<div>$msg</div>";
    }
    echo "</div>";
}

echo "</div>";
echo "<div class='card-footer'>";
echo "<a href='modules/users/index.php' class='btn btn-success'>Test Users Page</a> ";
echo "<a href='modules/dashboard/' class='btn btn-primary'>Go to Dashboard</a>";
echo "</div>";
echo "</div>";
echo "</div>";
