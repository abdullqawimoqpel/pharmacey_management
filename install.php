<?php
// install.php - Database Installation Script
session_start();
ob_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pharmacy_managements';

$steps = [
    1 => 'Database Connection',
    2 => 'Create Database',
    3 => 'Create Tables',
    4 => 'Add Default Data',
    5 => 'Complete'
];

$current_step = $_GET['step'] ?? 1;
$error = '';
$success = '';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_database':
                // Step 2: Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` 
                           CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $current_step = 2;
                break;

            case 'create_tables':
                // Step 3: Create tables
                $pdo->exec("USE `$db_name`");
                createTables($pdo);
                $current_step = 3;
                break;

            case 'add_data':
                // Step 4: Add default data
                $pdo->exec("USE `$db_name`");
                addDefaultData($pdo);
                $current_step = 4;
                break;
        }
    }
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

function createTables($pdo)
{
    $tables = [
        // First create categories table
        "categories" => "CREATE TABLE IF NOT EXISTS categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // Then create users table
        "users" => "CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            role ENUM('admin', 'pharmacist', 'assistant') DEFAULT 'assistant',
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "suppliers" => "CREATE TABLE IF NOT EXISTS suppliers (
            supplier_id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_name VARCHAR(100) NOT NULL,
            contact_person VARCHAR(100),
            phone VARCHAR(20),
            email VARCHAR(100),
            address TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "customers" => "CREATE TABLE IF NOT EXISTS customers (
            customer_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(100),
            address TEXT,
            loyalty_points INT DEFAULT 0,
            total_purchases DECIMAL(10,2) DEFAULT 0,
            last_purchase_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // Medicines table with foreign key to categories
        "medicines" => "CREATE TABLE IF NOT EXISTS medicines (
            medicine_id INT AUTO_INCREMENT PRIMARY KEY,
            medicine_name VARCHAR(100) NOT NULL,
            generic_name VARCHAR(100),
            category_id INT NULL,
            supplier_id INT NULL,
            batch_number VARCHAR(50),
            expiry_date DATE NOT NULL,
            purchase_price DECIMAL(10,2) NOT NULL,
            selling_price DECIMAL(10,2) NOT NULL,
            quantity_in_stock INT NOT NULL DEFAULT 0,
            min_stock_level INT DEFAULT 10,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
            INDEX idx_expiry (expiry_date),
            INDEX idx_stock (quantity_in_stock),
            INDEX idx_name (medicine_name)
        ) ENGINE=InnoDB",

        "sales" => "CREATE TABLE IF NOT EXISTS sales (
            sale_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NULL,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) DEFAULT 0,
            tax_amount DECIMAL(10,2) DEFAULT 0,
            final_amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'card', 'mobile') DEFAULT 'cash',
            sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            INDEX idx_sale_date (sale_date)
        ) ENGINE=InnoDB",

        "sale_items" => "CREATE TABLE IF NOT EXISTS sale_items (
            sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id),
            INDEX idx_sale_medicine (sale_id, medicine_id)
        ) ENGINE=InnoDB",

        "purchases" => "CREATE TABLE IF NOT EXISTS purchases (
            purchase_id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            purchase_date DATE NOT NULL,
            received_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
            FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id),
            INDEX idx_purchase_date (purchase_date)
        ) ENGINE=InnoDB",

        "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('stock', 'expiry', 'sale', 'system') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_priority (priority),
            INDEX idx_read (is_read)
        ) ENGINE=InnoDB",

        "login_attempts" => "CREATE TABLE IF NOT EXISTS login_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username_time (username, attempt_time)
        ) ENGINE=InnoDB"
    ];

    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Table '{$tableName}' created successfully<br>";
        } catch (PDOException $e) {
            echo "❌ Error creating table '{$tableName}': " . $e->getMessage() . "<br>";
            throw $e;
        }
    }
}

function addDefaultData($pdo)
{
    // Add default admin user
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $user_sql = "INSERT IGNORE INTO users (username, password, full_name, email, role) 
                 VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($user_sql)->execute([
        'admin',
        $password_hash,
        'System Administrator',
        'admin@pharmacy.com',
        'admin'
    ]);

    // Add default categories
    $categories = [
        ['Analgesics', 'Pain relief medications'],
        ['Antibiotics', 'Anti-bacterial medications'],
        ['Vitamins', 'Nutritional supplements'],
        ['Cardiovascular', 'Heart and blood pressure medications'],
        ['Diabetes', 'Diabetes management medications']
    ];

    $category_stmt = $pdo->prepare("INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $category_stmt->execute($category);
    }

    // Add sample supplier (Saudi)
    $supplier_sql = "INSERT IGNORE INTO suppliers (supplier_name, contact_person, phone, email, address) 
                     VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($supplier_sql)->execute([
        'Global Pharma Distributors',
        'John Smith',
        '+96612345678',
        'contact@globalpharma.com',
        'Industrial Area, Riyadh, Saudi Arabia'
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy System Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .installation-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .installation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .step {
            text-align: center;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
        }

        .step.active .step-number {
            background: #007bff;
            color: white;
        }

        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <div class="installation-wrapper py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="installation-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h1 class="h3">🏥 Pharmacy Management System</h1>
                            <p class="text-muted">Installation Wizard</p>
                        </div>

                        <!-- Progress Steps -->
                        <div class="step-indicator">
                            <?php foreach ($steps as $step_num => $step_name): ?>
                                <div class="step <?php echo $step_num == $current_step ? 'active' : ''; ?> 
                                            <?php echo $step_num < $current_step ? 'completed' : ''; ?>">
                                    <div class="step-number"><?php echo $step_num; ?></div>
                                    <small><?php echo $step_name; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Step Content -->
                        <div class="step-content">
                            <?php if ($current_step == 1): ?>
                                <div class="text-center">
                                    <h4>Welcome to Pharmacy System Installation</h4>
                                    <p class="text-muted mb-4">
                                        This wizard will guide you through the installation process.
                                        Make sure your MySQL server is running.
                                    </p>

                                    <div class="mb-4">
                                        <h6>System Requirements:</h6>
                                        <ul class="list-unstyled">
                                            <li>✅ PHP 7.4 or higher</li>
                                            <li>✅ MySQL 5.7 or higher</li>
                                            <li>✅ PDO Extension</li>
                                            <li>✅ MBString Extension</li>
                                        </ul>
                                    </div>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="create_database">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            Start Installation
                                        </button>
                                    </form>
                                </div>

                            <?php elseif ($current_step == 2): ?>
                                <div class="text-center">
                                    <h4>Database Created Successfully</h4>
                                    <p class="text-muted mb-4">
                                        Database 'pharmacy_management' has been created successfully.
                                    </p>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="create_tables">
                                        <button type="submit" class="btn btn-primary">
                                            Create Tables
                                        </button>
                                    </form>
                                </div>

                            <?php elseif ($current_step == 3): ?>
                                <div class="text-center">
                                    <h4>Tables Created Successfully</h4>
                                    <p class="text-muted mb-4">
                                        All database tables have been created successfully.
                                    </p>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_data">
                                        <button type="submit" class="btn btn-primary">
                                            Add Default Data
                                        </button>
                                    </form>
                                </div>

                            <?php elseif ($current_step == 4): ?>
                                <div class="text-center">
                                    <div class="alert alert-success">
                                        <h4>✅ Installation Complete!</h4>
                                        <p class="mb-3">Pharmacy Management System has been installed successfully.</p>
                                    </div>

                                    <div class="card mb-4">
                                        <div class="card-header bg-warning">
                                            <strong>Default Login Credentials</strong>
                                        </div>
                                        <div class="card-body text-start">
                                            <p><strong>Username:</strong> admin</p>
                                            <p><strong>Password:</strong> admin123</p>
                                            <p class="text-danger small">
                                                <strong>Important:</strong> Change the default password after first login!
                                            </p>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="modules/auth/login.php" class="btn btn-success btn-lg">
                                            🚀 Go to Login Page
                                        </a>
                                        <a href="install.php?step=1" class="btn btn-outline-secondary">
                                            🔄 Re-run Installation
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
