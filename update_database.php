<?php
// update_database.php - Add missing columns to customers table
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

    echo "<h3>🔧 Database Update Tool</h3>";

    // Check and add missing columns to customers table
    $columns_to_add = [
        'loyalty_points' => "INT DEFAULT 0",
        'total_purchases' => "DECIMAL(10,2) DEFAULT 0",
        'last_purchase_date' => "DATE NULL"
    ];

    foreach ($columns_to_add as $column_name => $column_definition) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM customers LIKE ?");
            $stmt->execute([$column_name]);

            if ($stmt->rowCount() == 0) {
                // Column doesn't exist, add it
                $sql = "ALTER TABLE customers ADD COLUMN $column_name $column_definition";
                $pdo->exec($sql);
                $success_messages[] = "✅ Added column '$column_name' to customers table";
            } else {
                $success_messages[] = "✅ Column '$column_name' already exists";
            }
        } catch (PDOException $e) {
            $error_messages[] = "❌ Error with column '$column_name': " . $e->getMessage();
        }
    }

    // Update existing customers to set default values for new columns
    try {
        $update_sql = "UPDATE customers SET 
                      loyalty_points = COALESCE(loyalty_points, 0),
                      total_purchases = COALESCE(total_purchases, 0)";
        $pdo->exec($update_sql);
        $success_messages[] = "✅ Updated existing customer records with default values";
    } catch (PDOException $e) {
        $error_messages[] = "⚠️ Note: " . $e->getMessage();
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
echo "<a href='modules/customers/index.php' class='btn btn-success'>Test Customers Page</a> ";
echo "<a href='modules/dashboard/' class='btn btn-primary'>Go to Dashboard</a>";
echo "</div>";
echo "</div>";
echo "</div>";
