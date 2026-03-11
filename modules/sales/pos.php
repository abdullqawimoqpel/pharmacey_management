<?php
// modules/sales/pos.php
$page_title = "Point of Sale";

$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/config/database.php';
require_once $root_path . '/includes/auth.php';
require_once $root_path . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLoggedIn();
$csrf_token = getCsrfToken();

// Handle sale completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    try {
        requireCsrfToken();
        $db->beginTransaction();

        $customer_id = $_POST['customer_id'] ?: null;
        $payment_method = sanitizeInput($_POST['payment_method']);
        $discount = floatval($_POST['discount'] ?? 0);
        $cart_items = json_decode($_POST['cart_items'], true);

        if (empty($cart_items)) {
            throw new Exception("Cart is empty. Please add items to complete the sale.");
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $tax_rate = 0.05; // 5% tax
        $tax_amount = $subtotal * $tax_rate;
        $final_amount = $subtotal + $tax_amount - $discount;

        // Insert sale record
        $sale_query = "INSERT INTO sales (customer_id, user_id, total_amount, discount, 
                       tax_amount, final_amount, payment_method) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $sale_stmt = $db->prepare($sale_query);
        $sale_stmt->execute([
            $customer_id,
            $_SESSION['user_id'],
            $subtotal,
            $discount,
            $tax_amount,
            $final_amount,
            $payment_method
        ]);

        $sale_id = $db->lastInsertId();

        // Insert sale items and update stock
        foreach ($cart_items as $item) {
            // Check stock availability
            $stock_check = "SELECT quantity_in_stock FROM medicines WHERE medicine_id = ?";
            $stock_stmt = $db->prepare($stock_check);
            $stock_stmt->execute([$item['id']]);
            $current_stock = $stock_stmt->fetchColumn();

            if ($current_stock < $item['quantity']) {
                throw new Exception("Insufficient stock for {$item['name']}. Available: {$current_stock}");
            }

            // Insert sale item
            $item_query = "INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) 
                          VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_total = $item['price'] * $item['quantity'];
            $item_stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $item_total]);

            // Update medicine stock
            $update_stock = "UPDATE medicines SET quantity_in_stock = quantity_in_stock - ? 
                            WHERE medicine_id = ?";
            $update_stmt = $db->prepare($update_stock);
            $update_stmt->execute([$item['quantity'], $item['id']]);
        }

        $db->commit();

        // Redirect to invoice
        $_SESSION['sale_success'] = "Sale completed successfully! Sale ID: {$sale_id}";
        header("Location: invoice.php?id={$sale_id}");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Sale failed: " . $e->getMessage();
    }
}

// Get customers for dropdown
$customers_query = "SELECT customer_id, customer_name, phone FROM customers ORDER BY customer_name";
$customers_stmt = $db->prepare($customers_query);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../../includes/header.php'; ?>

<div class="row">
    <!-- Medicine Search & Selection -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-search"></i> Search Medicines</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="searchMedicine" class="form-control"
                        placeholder="Search by medicine name...">
                </div>

                <div id="medicineList" style="max-height: 500px; overflow-y: auto;">
                    <?php
                    $medicines_query = "SELECT medicine_id, medicine_name, selling_price, quantity_in_stock 
                                      FROM medicines 
                                      WHERE is_active = 1 AND quantity_in_stock > 0 
                                      ORDER BY medicine_name";
                    $medicines_stmt = $db->prepare($medicines_query);
                    $medicines_stmt->execute();

                    while ($medicine = $medicines_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="medicine-item border-bottom py-2 cursor-pointer" 
                              data-id="' . $medicine['medicine_id'] . '"
                              data-name="' . htmlspecialchars($medicine['medicine_name']) . '"
                              data-price="' . $medicine['selling_price'] . '"
                              data-stock="' . $medicine['quantity_in_stock'] . '">
                                <div class="fw-bold">' . $medicine['medicine_name'] . '</div>
                                <small class="text-muted">' .
                            formatCurrency($medicine['selling_price']) . ' | Stock: ' .
                            $medicine['quantity_in_stock'] . '</small>
                              </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopping Cart -->
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-cart"></i> Shopping Cart</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="saleForm">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th width="100">Price</th>
                                    <th width="120">Quantity</th>
                                    <th width="120">Total</th>
                                    <th width="60">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cartItems">
                                <!-- Cart items will be added here by JavaScript -->
                            </tbody>
                            <tfoot id="cartSummary">
                                <!-- Cart summary will be added here by JavaScript -->
                            </tfoot>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer (Optional)</label>
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">Walk-in Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>">
                                            <?php echo $customer['customer_name']; ?>
                                            (<?php echo $customer['phone']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Credit/Debit Card</option>
                                    <option value="mobile">Mobile Payment</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount (<?php echo DEFAULT_CURRENCY; ?>)</label>
                                <input type="number" class="form-control" id="discount" name="discount"
                                    value="0" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="complete_sale" class="btn btn-success w-100 py-3"
                                id="completeSale" disabled>
                                <i class="bi bi-check-circle"></i> Complete Sale
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="cart_items" id="cartItemsInput">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // POS JavaScript functionality
    let cart = [];

    // Add medicine to cart
    document.querySelectorAll('.medicine-item').forEach(item => {
        item.addEventListener('click', function() {
            const medicine = {
                id: this.dataset.id,
                name: this.dataset.name,
                price: parseFloat(this.dataset.price),
                stock: parseInt(this.dataset.stock)
            };

            addToCart(medicine);
        });
    });

    function addToCart(medicine) {
        const existingItem = cart.find(item => item.id === medicine.id);

        if (existingItem) {
            if (existingItem.quantity < medicine.stock) {
                existingItem.quantity++;
            } else {
                alert(`Insufficient stock for ${medicine.name}. Available: ${medicine.stock}`);
                return;
            }
        } else {
            if (medicine.stock > 0) {
                cart.push({
                    ...medicine,
                    quantity: 1
                });
            } else {
                alert(`${medicine.name} is out of stock`);
                return;
            }
        }

        updateCart();
    }

    function updateCart() {
        const cartItems = document.getElementById('cartItems');
        const cartSummary = document.getElementById('cartSummary');
        const cartInput = document.getElementById('cartItemsInput');
        const completeBtn = document.getElementById('completeSale');

        cartItems.innerHTML = '';
        let subtotal = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;

            cartItems.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>${item.price.toFixed(2)} <?php echo DEFAULT_CURRENCY; ?></td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${item.quantity}" min="1" max="${item.stock}"
                           onchange="updateQuantity(${index}, this.value)">
                </td>
                <td>${itemTotal.toFixed(2)} <?php echo DEFAULT_CURRENCY; ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" 
                            onclick="removeFromCart(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        const tax = subtotal * 0.05;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const total = subtotal + tax - discount;

        cartSummary.innerHTML = `
        <tr>
            <td colspan="3" class="text-end fw-bold">Subtotal:</td>
            <td class="fw-bold">${subtotal.toFixed(2)} <?php echo DEFAULT_CURRENCY; ?></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" class="text-end fw-bold">Tax (5%):</td>
            <td class="fw-bold">${tax.toFixed(2)} <?php echo DEFAULT_CURRENCY; ?></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" class="text-end fw-bold">Discount:</td>
            <td class="fw-bold">-${discount.toFixed(2)} <?php echo DEFAULT_CURRENCY; ?></td>
            <td></td>
        </tr>
        <tr class="table-success">
            <td colspan="3" class="text-end fw-bold">Total Amount:</td>
            <td class="fw-bold">${total.toFixed(2)} <?php echo DEFAULT_CURRENCY; ?></td>
            <td></td>
        </tr>
    `;

        // Enable/disable complete button
        completeBtn.disabled = cart.length === 0;

        // Update hidden input
        cartInput.value = JSON.stringify(cart);
    }

    function updateQuantity(index, newQuantity) {
        newQuantity = parseInt(newQuantity);
        if (newQuantity > 0 && newQuantity <= cart[index].stock) {
            cart[index].quantity = newQuantity;
            updateCart();
        }
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCart();
    }

    // Search functionality
    document.getElementById('searchMedicine').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.medicine-item').forEach(item => {
            const medicineName = item.dataset.name.toLowerCase();
            if (medicineName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Update cart when discount changes
    document.getElementById('discount').addEventListener('input', updateCart);

    // Initialize empty cart
    updateCart();
</script>

<style>
    .medicine-item {
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .medicine-item:hover {
        background-color: #f8f9fa;
    }

    .cursor-pointer {
        cursor: pointer;
    }
</style>

<?php include '../../includes/footer.php'; ?>
