<?php
// repair_database.php - Database repair script
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

    echo "<h3>🔧 Database Repair Tool</h3>";

    // Check and create categories table if missing
    try {
        $pdo->query("SELECT 1 FROM categories LIMIT 1");
        $success_messages[] = "✅ Categories table exists";
    } catch (PDOException $e) {
        // Create categories table
        $sql = "CREATE TABLE categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        $success_messages[] = "✅ Created missing categories table";

        // Add default categories
        $categories = [
            ['Analgesics', 'Pain relief medications'],
            ['Antibiotics', 'Anti-bacterial medications'],
            ['Vitamins', 'Nutritional supplements'],
            ['Cardiovascular', 'Heart and blood pressure medications'],
            ['Diabetes', 'Diabetes management medications'],
            ['Antihistamines', 'Allergy medications'],
            ['Antacids', 'Digestive system medications'],
            ['Dermatological', 'Skin care medications']
        ];

        $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
        $success_messages[] = "✅ Added default categories";
    }

    // Check if category_id column exists in medicines table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM medicines LIKE 'category_id'");
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        // Add category_id column to medicines table
        $pdo->exec("ALTER TABLE medicines ADD COLUMN category_id INT NULL AFTER generic_name");
        $pdo->exec("ALTER TABLE medicines ADD FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL");
        $success_messages[] = "✅ Added category_id column to medicines table";
    } else {
        $success_messages[] = "✅ category_id column exists in medicines table";
    }

    // Check and fix other common issues
    $tables_to_check = ['users', 'suppliers', 'customers', 'medicines', 'sales', 'sale_items'];

    foreach ($tables_to_check as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $success_messages[] = "✅ $table table exists";
        } catch (PDOException $e) {
            $error_messages[] = "❌ $table table is missing: " . $e->getMessage();
        }
    }

    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();

    if ($admin_count == 0) {
        // Create admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password_hash, 'System Administrator', 'admin@pharmacy.com', 'admin']);
        $success_messages[] = "✅ Created admin user (username: admin, password: admin123)";
    } else {
        $success_messages[] = "✅ Admin user exists";
    }
} catch (PDOException $e) {
    $error_messages[] = "Database error: " . $e->getMessage();
}

// Display results
echo "<div class='container mt-4'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'><h4>Repair Results</h4></div>";
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
echo "<a href='modules/medicines/index.php' class='btn btn-success'>Test Medicines Page</a> ";
echo "<a href='install.php' class='btn btn-warning'>Re-run Full Installation</a> ";
echo "<a href='modules/auth/login.php' class='btn btn-primary'>Go to Login</a>";
echo "</div>";
echo "</div>";
echo "</div>";
