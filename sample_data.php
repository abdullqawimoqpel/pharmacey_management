<?php
// sample_data.php - Populate database with sample data
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

    echo "<h3>📊 Loading Sample Data</h3>";

    // 1. CATEGORIES
    $categories = [
        ['Analgesics', 'Pain relief medications'],
        ['Antibiotics', 'Anti-bacterial medications'],
        ['Vitamins', 'Nutritional supplements'],
        ['Cardiovascular', 'Heart and blood pressure medications'],
        ['Diabetes', 'Diabetes management medications'],
        ['Antihistamines', 'Allergy medications'],
        ['Antacids', 'Digestive system medications'],
        ['Dermatological', 'Skin care medications'],
        ['Respiratory', 'Asthma and respiratory medications'],
        ['Mental Health', 'Psychiatric medications']
    ];

    $category_stmt = $pdo->prepare("INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $category_stmt->execute($category);
    }
    $success_messages[] = "✅ Added " . count($categories) . " categories";

    // 2. SUPPLIERS
    $suppliers = [
        ['Global Pharma Distributors', 'John Smith', '+96512345678', 'orders@globalpharma.com', 'Industrial Area, Block 3, Kuwait'],
        ['MediCare Suppliers', 'Sarah Johnson', '+96512345679', 'info@medicare.com', 'Salmiya, Salem Al Mubarak Street, Kuwait'],
        ['Kuwait Medical Co.', 'Ahmed Al-Farsi', '+96512345680', 'contact@kuwaitmedical.com', 'Sharq, Arabian Gulf Street, Kuwait'],
        ['Pharma Gulf', 'Fatima Al-Sabah', '+96512345681', 'sales@pharmagulf.com', 'Hawalli, Beirut Street, Kuwait'],
        ['Health Plus Distributors', 'Mohammed Hassan', '+96512345682', 'orders@healthplus.com', 'Farwaniya, Airport Road, Kuwait']
    ];

    $supplier_stmt = $pdo->prepare("INSERT IGNORE INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
    foreach ($suppliers as $supplier) {
        $supplier_stmt->execute($supplier);
    }
    $success_messages[] = "✅ Added " . count($suppliers) . " suppliers";

    // 3. CUSTOMERS
    $customers = [
        ['Ahmed Mohammed', '+96550012345', 'ahmed.mohammed@email.com', 'Hawalli, Block 12, Street 5, Kuwait', 150, 1250.75],
        ['Fatima Al-Sabah', '+96550012346', 'fatima.alsabah@email.com', 'Salmiya, Salem Al Mubarak Street, Kuwait', 280, 2340.50],
        ['Yousef Al-Rashid', '+96550012347', 'yousef.alrashid@email.com', 'Sharq, Arabian Gulf Street, Kuwait', 75, 890.25],
        ['Noura Hassan', '+96550012348', 'noura.hassan@email.com', 'Farwaniya, Block 8, Street 2, Kuwait', 420, 3560.80],
        ['Khalid Al-Mansour', '+96550012349', 'khalid.mansour@email.com', 'Jabriya, Block 1, Street 10, Kuwait', 190, 1675.30],
        ['Layla Abdullah', '+96550012350', 'layla.abdullah@email.com', 'Qadsia, Ahmed Al Jaber Street, Kuwait', 320, 2780.45],
        ['Omar Farouk', '+96550012351', 'omar.farouk@email.com', 'Mishref, Block 4, Street 3, Kuwait', 60, 720.90],
        ['Sara Al-Otaibi', '+96550012352', 'sara.otaibi@email.com', 'Khaldiya, Gulf Road, Kuwait', 510, 4120.60]
    ];

    $customer_stmt = $pdo->prepare("INSERT IGNORE INTO customers (customer_name, phone, email, address, loyalty_points, total_purchases) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($customers as $customer) {
        $customer_stmt->execute($customer);
    }
    $success_messages[] = "✅ Added " . count($customers) . " customers";

    // 4. MEDICINES
    $medicines = [
        // Analgesics
        ['Panadol Extra', 'Paracetamol 500mg', 1, 1, 'BATCH-PAN-001', '2025-12-31', 2.50, 5.00, 150, 20, 'Fast-acting pain relief and fever reduction'],
        ['Brufen 400mg', 'Ibuprofen 400mg', 1, 2, 'BATCH-BRF-001', '2025-11-30', 3.20, 6.50, 80, 15, 'Anti-inflammatory pain reliever'],
        ['Aspirin Protect', 'Acetylsalicylic Acid 100mg', 1, 3, 'BATCH-ASP-001', '2025-10-31', 4.00, 8.00, 120, 25, 'Cardiovascular protection and pain relief'],

        // Antibiotics
        ['Amoxicillin 500mg', 'Amoxicillin Trihydrate', 2, 1, 'BATCH-AMX-001', '2025-09-30', 8.50, 16.00, 60, 10, 'Broad-spectrum antibiotic for bacterial infections'],
        ['Ciprofloxacin 500mg', 'Ciprofloxacin HCl', 2, 2, 'BATCH-CIP-001', '2025-08-31', 12.00, 22.50, 45, 8, 'Treatment for various bacterial infections'],
        ['Azithromycin 250mg', 'Azithromycin Dihydrate', 2, 4, 'BATCH-AZI-001', '2025-07-31', 15.00, 28.00, 35, 5, 'Macrolide antibiotic for respiratory infections'],

        // Vitamins
        ['Vitamin C 1000mg', 'Ascorbic Acid', 3, 1, 'BATCH-VITC-001', '2026-03-31', 5.50, 11.00, 200, 30, 'Immune system support and antioxidant'],
        ['Vitamin D3 1000IU', 'Cholecalciferol', 3, 3, 'BATCH-VITD-001', '2026-04-30', 7.00, 14.00, 180, 25, 'Bone health and immune function'],
        ['Multivitamin Complex', 'Multiple Vitamins & Minerals', 3, 5, 'BATCH-MVIT-001', '2026-05-31', 12.50, 25.00, 90, 15, 'Complete daily nutritional supplement'],

        // Cardiovascular
        ['Concor 5mg', 'Bisoprolol Fumarate', 4, 2, 'BATCH-CON-001', '2025-12-31', 18.00, 35.00, 40, 8, 'Beta blocker for hypertension and heart conditions'],
        ['Coversyl 5mg', 'Perindopril Erbumine', 4, 4, 'BATCH-COV-001', '2025-11-30', 22.00, 42.00, 35, 6, 'ACE inhibitor for blood pressure control'],
        ['Lipitor 20mg', 'Atorvastatin Calcium', 4, 1, 'BATCH-LIP-001', '2025-10-31', 25.00, 48.00, 50, 10, 'Cholesterol-lowering medication'],

        // Diabetes
        ['Glucophage 850mg', 'Metformin HCl', 5, 3, 'BATCH-GLU-001', '2025-09-30', 6.50, 13.00, 110, 20, 'First-line treatment for type 2 diabetes'],
        ['Januvia 100mg', 'Sitagliptin Phosphate', 5, 2, 'BATCH-JAN-001', '2025-08-31', 45.00, 85.00, 25, 5, 'DPP-4 inhibitor for diabetes management'],
        ['Victoza 6mg/ml', 'Liraglutide', 5, 5, 'BATCH-VIC-001', '2025-07-31', 120.00, 220.00, 15, 3, 'GLP-1 receptor agonist injection'],

        // Antihistamines
        ['Claritine 10mg', 'Loratadine', 6, 1, 'BATCH-CLA-001', '2026-02-28', 8.00, 16.00, 95, 15, 'Non-drowsy allergy relief'],
        ['Zyrtec 10mg', 'Cetirizine HCl', 6, 4, 'BATCH-ZYR-001', '2026-03-31', 9.50, 19.00, 85, 12, '24-hour allergy symptom relief'],

        // Antacids
        ['Gaviscon Liquid', 'Aluminum Hydroxide & Magnesium Carbonate', 7, 3, 'BATCH-GAV-001', '2026-04-30', 12.00, 24.00, 70, 10, 'Fast-acting heartburn and indigestion relief'],
        ['Rennie Tablets', 'Calcium Carbonate & Magnesium Carbonate', 7, 2, 'BATCH-REN-001', '2026-05-31', 6.00, 12.00, 130, 20, 'Chewable antacid for acid reflux'],

        // Dermatological
        ['Betnovate Cream', 'Betamethasone Valerate', 8, 5, 'BATCH-BET-001', '2025-12-31', 15.00, 30.00, 45, 8, 'Topical corticosteroid for skin conditions'],
        ['Canesten Cream', 'Clotrimazole', 8, 1, 'BATCH-CAN-001', '2025-11-30', 11.00, 22.00, 60, 10, 'Antifungal cream for skin infections'],

        // Respiratory
        ['Ventolin Inhaler', 'Salbutamol Sulfate', 9, 4, 'BATCH-VEN-001', '2025-10-31', 28.00, 55.00, 30, 5, 'Bronchodilator for asthma relief'],
        ['Flixotide Inhaler', 'Fluticasone Propionate', 9, 3, 'BATCH-FLI-001', '2025-09-30', 35.00, 68.00, 25, 4, 'Preventive asthma treatment'],

        // Mental Health
        ['Cipralex 10mg', 'Escitalopram Oxalate', 10, 2, 'BATCH-CIP-002', '2025-08-31', 32.00, 62.00, 40, 6, 'SSRI antidepressant for depression and anxiety'],
        ['Xanax 0.5mg', 'Alprazolam', 10, 5, 'BATCH-XAN-001', '2025-07-31', 18.00, 35.00, 55, 8, 'Anxiolytic for anxiety disorders']
    ];

    $medicine_stmt = $pdo->prepare("INSERT IGNORE INTO medicines (medicine_name, generic_name, category_id, supplier_id, batch_number, expiry_date, purchase_price, selling_price, quantity_in_stock, min_stock_level, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($medicines as $medicine) {
        $medicine_stmt->execute($medicine);
    }
    $success_messages[] = "✅ Added " . count($medicines) . " medicines";

    // 5. ADDITIONAL USERS
    $users = [
        ['pharmacist1', 'Pharmacist User 1', 'pharmacist1@pharmacy.com', '+96550011111', 'pharmacist'],
        ['pharmacist2', 'Pharmacist User 2', 'pharmacist2@pharmacy.com', '+96550011112', 'pharmacist'],
        ['assistant1', 'Assistant User 1', 'assistant1@pharmacy.com', '+96550011113', 'assistant'],
        ['assistant2', 'Assistant User 2', 'assistant2@pharmacy.com', '+96550011114', 'assistant']
    ];

    $user_stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $user_stmt->execute([$user[0], $password_hash, $user[1], $user[2], $user[3], $user[4]]);
    }
    $success_messages[] = "✅ Added additional users (password: password123)";

    // 6. SAMPLE SALES AND SALE ITEMS
    // This creates realistic sales data for the past 30 days
    $customer_ids = [1, 2, 3, 4, 5, 6, 7, 8];
    $medicine_ids = range(1, 25); // Assuming we have 25 medicines
    $payment_methods = ['cash', 'card', 'mobile'];

    // Create sales for the past 30 days
    for ($i = 0; $i < 50; $i++) {
        $customer_id = $customer_ids[array_rand($customer_ids)];
        $user_id = rand(1, 3); // Random user (admin, pharmacist1, pharmacist2)
        $payment_method = $payment_methods[array_rand($payment_methods)];

        // Random date within last 30 days
        $days_ago = rand(0, 30);
        $sale_date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));

        // Create sale
        $sale_query = "INSERT INTO sales (customer_id, user_id, total_amount, discount, tax_amount, final_amount, payment_method, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $sale_stmt = $pdo->prepare($sale_query);

        // Calculate random amounts
        $total_amount = rand(1500, 8000) / 100; // Between 15.00 and 80.00 KD
        $discount = rand(0, 500) / 100; // Between 0 and 5.00 KD discount
        $tax_amount = $total_amount * 0.05; // 5% tax
        $final_amount = $total_amount + $tax_amount - $discount;

        $sale_stmt->execute([$customer_id, $user_id, $total_amount, $discount, $tax_amount, $final_amount, $payment_method, $sale_date]);
        $sale_id = $pdo->lastInsertId();

        // Add 1-4 items to each sale
        $num_items = rand(1, 4);
        $selected_medicines = array_rand($medicine_ids, $num_items);

        if (!is_array($selected_medicines)) {
            $selected_medicines = [$selected_medicines];
        }

        foreach ($selected_medicines as $medicine_index) {
            $medicine_id = $medicine_ids[$medicine_index];
            $quantity = rand(1, 3);

            // Get medicine price
            $price_query = "SELECT selling_price FROM medicines WHERE medicine_id = ?";
            $price_stmt = $pdo->prepare($price_query);
            $price_stmt->execute([$medicine_id]);
            $unit_price = $price_stmt->fetchColumn();

            $total_price = $unit_price * $quantity;

            // Insert sale item
            $item_query = "INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $pdo->prepare($item_query);
            $item_stmt->execute([$sale_id, $medicine_id, $quantity, $unit_price, $total_price]);

            // Update medicine stock (in real system, this would be done during sale)
            $update_stock = "UPDATE medicines SET quantity_in_stock = quantity_in_stock - ? WHERE medicine_id = ?";
            $update_stmt = $pdo->prepare($update_stock);
            $update_stmt->execute([$quantity, $medicine_id]);
        }
    }
    $success_messages[] = "✅ Generated 50 sample sales with items";

    // 7. SAMPLE PURCHASES
    $purchases = [
        [1, 1, 100, 2.30, '2024-01-15'],
        [2, 4, 50, 7.80, '2024-01-18'],
        [3, 7, 200, 4.80, '2024-01-20'],
        [4, 10, 30, 16.50, '2024-01-22'],
        [5, 13, 80, 5.80, '2024-01-25'],
        [1, 2, 60, 2.90, '2024-02-01'],
        [2, 5, 40, 10.50, '2024-02-03'],
        [3, 8, 150, 6.20, '2024-02-05'],
        [4, 11, 25, 20.00, '2024-02-08'],
        [5, 14, 20, 42.00, '2024-02-10']
    ];

    $purchase_stmt = $pdo->prepare("INSERT IGNORE INTO purchases (supplier_id, medicine_id, quantity, unit_price, purchase_date) VALUES (?, ?, ?, ?, ?)");
    foreach ($purchases as $purchase) {
        $total_amount = $purchase[2] * $purchase[3];
        $purchase_stmt->execute([$purchase[0], $purchase[1], $purchase[2], $purchase[3], $purchase[4]]);
    }
    $success_messages[] = "✅ Added sample purchase records";

    // 8. SAMPLE NOTIFICATIONS
    $notifications = [
        ['stock', 'Low Stock Alert', 'Panadol Extra is running low (15 items left)', 'high'],
        ['stock', 'Reorder Needed', 'Amoxicillin 500mg needs to be reordered (8 items left)', 'medium'],
        ['expiry', 'Expiry Warning', 'Vitamin C 1000mg expires in 30 days', 'medium'],
        ['sale', 'High Value Sale', 'A sale worth 78.50 KD was completed', 'low'],
        ['system', 'System Update', 'Database backup completed successfully', 'low']
    ];

    $notification_stmt = $pdo->prepare("INSERT IGNORE INTO notifications (type, title, message, priority) VALUES (?, ?, ?, ?)");
    foreach ($notifications as $notification) {
        $notification_stmt->execute($notification);
    }
    $success_messages[] = "✅ Added sample notifications";
} catch (PDOException $e) {
    $error_messages[] = "Database error: " . $e->getMessage();
}

// Display results
echo "<div class='container mt-4'>";
echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'><h4>Sample Data Loading Complete</h4></div>";
echo "<div class='card-body'>";

if (!empty($success_messages)) {
    echo "<div class='alert alert-success'>";
    echo "<h5>Data Successfully Loaded:</h5>";
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

// Display summary
echo "<div class='alert alert-info'>";
echo "<h5>📊 Sample Data Summary:</h5>";
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<ul class='list-unstyled'>";
echo "<li>🏥 <strong>10 Categories</strong> of medicines</li>";
echo "<li>🏢 <strong>5 Suppliers</strong> with contact information</li>";
echo "<li>👥 <strong>8 Customers</strong> with loyalty points</li>";
echo "<li>💊 <strong>25 Different Medicines</strong> with realistic pricing</li>";
echo "</ul>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<ul class='list-unstyled'>";
echo "<li>👤 <strong>4 Additional Users</strong> (pharmacists & assistants)</li>";
echo "<li>💰 <strong>50 Sample Sales</strong> from the past 30 days</li>";
echo "<li>📦 <strong>10 Purchase Orders</strong> for inventory</li>";
echo "<li>🔔 <strong>5 Sample Notifications</strong> for alerts</li>";
echo "</ul>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "<div class='card-footer'>";
echo "<a href='modules/dashboard/' class='btn btn-success me-2'>Go to Dashboard</a>";
echo "<a href='modules/medicines/' class='btn btn-primary me-2'>View Medicines</a>";
echo "<a href='modules/sales/' class='btn btn-info me-2'>View Sales</a>";
echo "<a href='modules/reports/' class='btn btn-warning'>View Reports</a>";
echo "</div>";
echo "</div>";
echo "</div>";
