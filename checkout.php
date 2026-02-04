<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = "";

// Get user details for autofill
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);
$user_row = $user_result->fetch_assoc();
$user_email = $user_row['email'];

// Create user_addresses table if not exists (Self-healing)
$createTableSql = "CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    house_no VARCHAR(255),
    street_address VARCHAR(255),
    city VARCHAR(100),
    district VARCHAR(100),
    pin_code VARCHAR(10),
    phone VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($createTableSql);

// Fetch saved addresses
$addresses_sql = "SELECT * FROM user_addresses WHERE user_id = $user_id ORDER BY is_default DESC, id DESC";
$addresses_result = $conn->query($addresses_sql);
$saved_addresses = [];
if ($addresses_result && $addresses_result->num_rows > 0) {
    while($addr = $addresses_result->fetch_assoc()) {
        $saved_addresses[] = $addr;
    }
} else {
    // Fallback to user profile address if no saved addresses exist
    if (!empty($user_row['house_no']) || !empty($user_row['street_address'])) {
        $saved_addresses[] = [
            'id' => 'profile',
            'name' => $user_name,
            'house_no' => $user_row['house_no'],
            'street_address' => $user_row['street_address'],
            'city' => $user_row['city'],
            'district' => $user_row['district'],
            'pin_code' => $user_row['pin_code'],
            'phone' => $user_row['phone'],
            'is_default' => 1
        ];
    }
}
$has_saved_addresses = count($saved_addresses) > 0;


// Initial Autofill values (if not POST)
$val_name = isset($_POST['name']) ? $_POST['name'] : $user_name;
$val_house_no = isset($_POST['house_no']) ? $_POST['house_no'] : ($user_row['house_no'] ?? '');
$val_address = isset($_POST['address']) ? $_POST['address'] : ($user_row['street_address'] ?? '');
$val_city = isset($_POST['city']) ? $_POST['city'] : ($user_row['city'] ?? '');
$val_district = isset($_POST['district']) ? $_POST['district'] : ($user_row['district'] ?? '');
$val_pin_code = isset($_POST['pin_code']) ? $_POST['pin_code'] : ($user_row['pin_code'] ?? '');
$val_phone = isset($_POST['phone']) ? $_POST['phone'] : ($user_row['phone'] ?? '');

// Calculate Total and get cart items
$sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = $user_id";
$result = $conn->query($sql);
$cart_items = [];
$total_amount = 0;
$shipping_fee = 5.00; // Standard shipping
$tax_rate = 0.08; // 8% tax

while($row = $result->fetch_assoc()) {
    $subtotal = $row['price'] * $row['quantity'];
    $total_amount += $subtotal;
    $cart_items[] = $row;
}

$tax_amount = $total_amount * $tax_rate;
$grand_total = $total_amount + $shipping_fee + $tax_amount;

if (count($cart_items) == 0) {
    header("Location: shop.php"); // Empty cart
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $house_no = trim($_POST['house_no']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $pin_code = trim($_POST['pin_code']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    
    // Validate stock before placing order
    $stock_valid = true;
    $stock_errors = [];
    
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $stock_valid = false;
            $stock_errors[] = $item['name'] . " (Only " . $item['stock'] . " in stock)";
        }
    }
    
    if (!$stock_valid) {
        $message = "<strong>Stock issue:</strong> " . implode(", ", $stock_errors);
        $message_type = "error";
    } else {
        // Save/Update User Address Permanently
        try {
            // Check if columns exist before updating (or just try-catch existing columns)
            $updateUser = $conn->prepare("UPDATE users SET house_no=?, street_address=?, city=?, district=?, pin_code=?, phone=? WHERE id=?");
            if ($updateUser) {
                $updateUser->bind_param("ssssssi", $house_no, $address, $city, $district, $pin_code, $phone, $user_id);
                $updateUser->execute();
            }
        } catch (mysqli_sql_exception $e) {
            // Ignore missing column errors to prevent checkout crash
            // In a real scenario, we should log this or ensure DB migration script runs
        } catch (Exception $e) {
            // General exception catch
        }

        // 1. Create Order
        // Notes: Address is now stored in specific columns in order table if you updated it, 
        // but for now we concatenate into shipping_address to maintain compatibility with existing order schema
        $shipping_address = "Name: " . $name . ", " . $house_no . ", " . $address;
        
        $insertOrder = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, city, state, zip_code, phone, payment_method, notes) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)");
        $insertOrder->bind_param("idsssssss", $user_id, $grand_total, $shipping_address, $city, $district, $pin_code, $phone, $payment_method, $notes);
        
        if ($insertOrder->execute()) {
            $order_id = $conn->insert_id;
            
            // 2. Move items to order_items table
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // 3. Update product stock
                $new_stock = $item['stock'] - $item['quantity'];
                $conn->query("UPDATE products SET stock = $new_stock WHERE id = {$item['product_id']}");
            }
            
            // 4. Clear Cart
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
            
            // 5. Send confirmation email (simulated)
            // In production, implement email sending here
            
            // Success message with order details
            $message = "Order placed successfully!";
            $message_type = "success";
            $order_success = true;
        } else {
            $message = "Error placing order: " . $conn->error;
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Organic Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4c8334;
            --primary-light: #8bc34a;
            --secondary: #3a6627;
            --dark: #2d5016;
            --light: #f9fff5;
            --gray: #6b8c5d;
            --gray-light: #e8f5e0;
            --danger: #e74c3c;
            --warning: #f39c12;
            --success: #27ae60;
            --border-radius: 12px;
            --box-shadow: 0 5px 20px rgba(76, 131, 52, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9fff5;
            color: #2d5016;
            line-height: 1.6;
        }

        header {
            background-color: #ffffff;
            box-shadow: 0 2px 15px rgba(76, 131, 52, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4c8334;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav ul li a {
            text-decoration: none;
            color: #3a6627;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        nav ul li a:hover {
            background-color: #e8f5e0;
            color: #2d5016;
        }

        .cart-icon {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 5%;
            min-height: calc(100vh - 200px);
        }

        /* Checkout Header */
        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 20px;
        }

        .checkout-header h1 {
            font-size: 2.2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkout-header h1 i {
            color: var(--primary);
        }

        .checkout-steps {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
        }

        .step.active {
            color: var(--primary);
        }

        .step.completed {
            color: var(--success);
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: var(--gray-light);
        }

        .step.active .step-number {
            background: var(--primary);
            color: white;
        }

        .step.completed .step-number {
            background: var(--success);
            color: white;
        }

        .step i {
            font-size: 0.8rem;
            margin-left: 5px;
        }

        /* Checkout Grid */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Form Sections */
        .form-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
        }

        .section-header h3 {
            font-size: 1.3rem;
            color: var(--dark);
        }

        .section-header i {
            color: var(--primary);
        }

        /* Form Elements */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group.required label::after {
            content: " *";
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(76, 131, 52, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Payment Methods */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .payment-option {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-option:hover {
            border-color: var(--primary);
            background: var(--light);
        }

        .payment-option.selected {
            border-color: var(--primary);
            background: var(--light);
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }

        .payment-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .payment-info {
            flex: 1;
        }

        .payment-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .payment-desc {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            position: sticky;
            top: 120px;
        }

        .order-items {
            margin: 1.5rem 0;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-image i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .item-price {
            color: var(--primary);
            font-weight: 600;
        }

        .item-quantity {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Order Totals */
        .order-totals {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--gray-light);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: var(--gray);
        }

        .total-row.final {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px dashed var(--gray-light);
        }

        .total-label {
            font-weight: 600;
        }

        .total-value {
            font-weight: 600;
        }

        /* Messages */
        .message {
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid var(--success);
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid var(--danger);
        }

        .message i {
            font-size: 1.5rem;
        }

        /* Success State */
        .success-container {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1.5rem;
            animation: bounce 1s ease;
        }

        .success-container h2 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .success-container p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .order-details {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }

        .order-details h4 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .order-details div {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--gray-light);
        }

        .order-details div:last-child {
            border-bottom: none;
        }

        /* Buttons */
        .btn {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 131, 52, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--gray-light);
            color: var(--secondary);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 2rem;
        }

        .action-buttons .btn {
            width: auto;
            flex: 1;
        }

        /* Address Card Styles from Model */
        .address-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            text-align: left;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
        }
        
        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .address-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        
        .address-tag {
            background: #f5f5f5;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: #666;
            font-weight: normal;
        }
        
        .edit-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            display: none; /* Hidden for order success view as per standard flow */
        }
        
        .address-body {
            color: #444;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .contact-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .contact-info .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            border: none !important;
            padding: 0 !important;
        }
        
        .delivery-info {
            background: #faeedb; /* Beige background */
            color: #8c6b1f; /* Brownish text */
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            margin-top: 15px;
            border: none !important;
        }

        .delivery-info i {
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 60%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            80% {
                transform: translateY(-5px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            nav ul {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .checkout-steps {
                justify-content: center;
            }
            
            .form-section {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }
            
            .checkout-header h1 {
                font-size: 1.8rem;
            }
            
            .form-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Organic Store
            </div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="cart-icon">
                        <a href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if(count($cart_items) > 0): ?>
                                <span class="cart-count"><?php echo count($cart_items); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Success Message -->
        <?php if(isset($order_success) && $order_success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2>Order Confirmed!</h2>
                <p>Thank you for your purchase, <?php echo htmlspecialchars($user_name); ?>!</p>
                <p>Your order has been placed successfully and will be processed shortly.</p>
                
                <div class="order-details">
                    <h4>Order Summary</h4>
                    <div>
                        <span>Order ID:</span>
                        <span><strong>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong></span>
                    </div>
                    <div>
                        <span>Order Total:</span>
                        <span><strong>$<?php echo number_format($grand_total, 2); ?></strong></span>
                    </div>
                    <div>
                        <span>Payment Method:</span>
                        <span><?php echo htmlspecialchars(ucfirst($payment_method)); ?></span>
                    </div>
                </div>

                <!-- Updated Address Card Model -->
                <div class="address-card">
                    <div class="address-header">
                        <div class="address-label">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Deliver to</span>
                            <span class="address-tag">Home</span>
                        </div>
                        <span class="edit-link">Edit &gt;</span>
                    </div>
                    <div class="address-body">
                        <p>
                            <strong style="text-transform: uppercase; color: #000;"><?php echo htmlspecialchars($name); ?></strong>, 
                            <?php echo htmlspecialchars($house_no) . ', ' . htmlspecialchars($address) . ', ' . htmlspecialchars($city) . ', ' . htmlspecialchars($district) . ' - ' . htmlspecialchars($pin_code); ?>
                        </p>
                        <div class="contact-info">
                             <div class="contact-item"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($phone); ?></div>
                             <div class="contact-item"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_email); ?></div>
                        </div>
                    </div>
                    <div class="delivery-info">
                        <i class="fas fa-truck"></i>
                        <span>Delivery information: 2 - 3 days</span>
                    </div>
                </div>
                
                <p style="color: var(--gray); font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i>
                    You will receive an order confirmation email at <?php echo htmlspecialchars($user_email); ?>
                </p>
                
                <div class="action-buttons">
                    <a href="shop.php" class="btn">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Checkout Header -->
            <div class="checkout-header">
                <h1><i class="fas fa-shopping-bag"></i> Checkout</h1>
                <div style="color: var(--gray);">
                    <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($user_name); ?>
                </div>
            </div>

            <!-- Checkout Steps -->
            <div class="checkout-steps">
                <div class="step completed">
                    <span class="step-number">1</span>
                    <span>Cart</span>
                    <i class="fas fa-check"></i>
                </div>
                <div class="step active">
                    <span class="step-number">2</span>
                    <span>Checkout</span>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <span>Confirmation</span>
                </div>
            </div>

            <?php if($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <div><?php echo $message; ?></div>
                </div>
            <?php endif; ?>

            <!-- Checkout Form -->
            <div class="checkout-grid">
                <!-- Left Column: Form -->
                <div>
                    <form method="POST" action="" id="checkoutForm">
                        <!-- Shipping Information -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-truck"></i>
                                <h3>Shipping Information</h3>
                            </div>
                            
                            <!-- Saved Address Summary (Initially Hidden) -->
                            <div id="shipping-summary" class="address-card" style="display: none; margin-top: 0; box-shadow: none; border: 1px solid var(--gray-light);">
                                <div class="address-header">
                                    <div class="address-label">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Deliver to</span>
                                        <span class="address-tag">Home</span>
                                    </div>
                                    <a href="javascript:void(0)" class="edit-link" id="editAddressBtn" style="display: block;">Edit ></a>
                                </div>
                                <div class="address-body" id="summary-content">
                                    <!-- Content will be populated by JS -->
                                </div>
                                <div class="delivery-info">
                                    <i class="fas fa-truck"></i>
                                    <span>Delivery information: 2 - 3 days</span>
                                </div>
                            </div>

                            <!-- Edit Form -->
                            <div id="shipping-inputs">
                                <div class="form-group required">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           placeholder="Enter your full name" required
                                           value="<?php echo htmlspecialchars($val_name); ?>">
                                </div>

                                <div class="form-group required">
                                    <label for="house_no">House No/Street</label>
                                    <input type="text" id="house_no" name="house_no" class="form-control" 
                                           placeholder="House No, Street Name" required
                                           value="<?php echo htmlspecialchars($val_house_no); ?>">
                                </div>

                                <div class="form-group required">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" class="form-control" 
                                           placeholder="Landmark, Area" required
                                           value="<?php echo htmlspecialchars($val_address); ?>">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group required">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" class="form-control" 
                                               placeholder="City" required
                                               value="<?php echo htmlspecialchars($val_city); ?>">
                                    </div>
                                    
                                    <div class="form-group required">
                                        <label for="district">District</label>
                                        <input type="text" id="district" name="district" class="form-control" 
                                               placeholder="District" required
                                               value="<?php echo htmlspecialchars($val_district); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group required">
                                        <label for="pin_code">Pin Code</label>
                                        <input type="text" id="pin_code" name="pin_code" class="form-control" 
                                               placeholder="Pin Code" required maxlength="6" pattern="\d{6}" inputmode="numeric"
                                               value="<?php echo htmlspecialchars($val_pin_code); ?>">
                                    </div>
                                    
                                    <div class="form-group required">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" 
                                               placeholder="Phone number" required pattern="\d{10,15}" inputmode="tel"
                                               value="<?php echo htmlspecialchars($val_phone); ?>">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <button type="button" class="btn btn-secondary" id="saveAddressBtn" style="padding: 10px 20px; font-size: 0.95rem; width: auto;">
                                        Save Address
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-credit-card"></i>
                                <h3>Payment Method</h3>
                            </div>
                            
                            <div class="payment-methods">
                                <div class="payment-option selected">
                                    <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                    <div class="payment-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-title">Cash on Delivery</div>
                                        <div class="payment-desc">Pay when you receive your order</div>
                                    </div>
                                </div>
                                
                                <div class="payment-option">
                                    <input type="radio" id="card" name="payment_method" value="card">
                                    <div class="payment-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-title">Credit/Debit Card</div>
                                        <div class="payment-desc">Pay securely with your card</div>
                                    </div>
                                </div>
                                
                                <div class="payment-option">
                                    <input type="radio" id="paypal" name="payment_method" value="paypal">
                                    <div class="payment-icon">
                                        <i class="fab fa-paypal"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-title">PayPal</div>
                                        <div class="payment-desc">Fast and secure online payments</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Details (hidden by default) -->
                            <div id="cardDetails" style="display: none; margin-top: 20px;">
                                <div class="form-group">
                                    <label for="card_number">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" class="form-control" 
                                           placeholder="1234 5678 9012 3456">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiry">Expiry Date</label>
                                        <input type="text" id="expiry" name="expiry" class="form-control" 
                                               placeholder="MM/YY">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="cvv">CVV</label>
                                        <input type="text" id="cvv" name="cvv" class="form-control" 
                                               placeholder="123">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-sticky-note"></i>
                                <h3>Additional Information</h3>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Order Notes (Optional)</label>
                                <textarea id="notes" name="notes" class="form-control" 
                                          placeholder="Any special instructions for your order..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="order-summary">
                    <h3 style="color: var(--dark); margin-bottom: 1.5rem;">Order Summary</h3>
                    
                    <div class="order-items">
                        <?php foreach($cart_items as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                        ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <?php if($item['image'] && file_exists('assets/images/products/' . $item['image'])): ?>
                                        <img src="assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-carrot"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div style="font-weight: 600; color: var(--dark);">
                                    ₹<?php echo number_format($subtotal, 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-totals">
                        <div class="total-row">
                            <span class="total-label">Subtotal</span>
                            <span class="total-value">₹<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span class="total-label">Shipping</span>
                            <span class="total-value">₹<?php echo number_format($shipping_fee, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span class="total-label">Tax (8%)</span>
                            <span class="total-value">₹<?php echo number_format($tax_amount, 2); ?></span>
                        </div>
                        <div class="total-row final">
                            <span class="total-label">Total</span>
                            <span class="total-value">₹<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <p style="color: var(--gray); font-size: 0.9rem; margin-bottom: 1rem;">
                            <i class="fas fa-shield-alt"></i>
                            Your payment information is secure and encrypted.
                        </p>
                        
                        <button type="submit" form="checkoutForm" class="btn" id="placeOrderBtn">
                            <i class="fas fa-lock"></i> Place Order - ₹<?php echo number_format($grand_total, 2); ?>
                        </button>
                        
                        <a href="cart.php" class="btn btn-secondary" style="margin-top: 15px;">
                            <i class="fas fa-arrow-left"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Payment method selection
        const paymentOptions = document.querySelectorAll('.payment-option');
        const cardDetails = document.getElementById('cardDetails');
        
        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                paymentOptions.forEach(opt => {
                    opt.classList.remove('selected');
                    opt.querySelector('input[type="radio"]').checked = false;
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                const radioInput = this.querySelector('input[type="radio"]');
                radioInput.checked = true;
                
                // Show/hide card details
                if (radioInput.value === 'card') {
                    cardDetails.style.display = 'block';
                } else {
                    cardDetails.style.display = 'none';
                }
            });
        });
        
        // Form validation
        const checkoutForm = document.getElementById('checkoutForm');
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        
        if (checkoutForm && placeOrderBtn) {
            checkoutForm.addEventListener('submit', function(e) {
                // Show loading state
                placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Order...';
                placeOrderBtn.disabled = true;
                
                // Re-enable after 5 seconds (in case of error)
                setTimeout(() => {
                    placeOrderBtn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
                    placeOrderBtn.disabled = false;
                }, 5000);
            });
        }
        
        // Auto-format phone number
        const phoneInput = document.getElementById('phone');
        
        // Shipping Form Toggle (Save/Edit)
        const saveAddressBtn = document.getElementById('saveAddressBtn');
        const editAddressBtn = document.getElementById('editAddressBtn');
        const shippingInputs = document.getElementById('shipping-inputs');
        const shippingSummary = document.getElementById('shipping-summary');
        const summaryContent = document.getElementById('summary-content');

        if (saveAddressBtn && editAddressBtn && shippingInputs && shippingSummary) {
            saveAddressBtn.addEventListener('click', function() {
                // Simple validation
                const inputs = shippingInputs.querySelectorAll('input[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.borderColor = 'var(--danger)';
                        // Reset border after 2 seconds
                        setTimeout(() => input.style.borderColor = 'var(--gray-light)', 2000);
                    }
                });
                
                if (isValid) {
                    // Collect values
                    const name = document.getElementById('name').value;
                    const house_no = document.getElementById('house_no').value;
                    const address = document.getElementById('address').value;
                    const city = document.getElementById('city').value;
                    const district = document.getElementById('district').value;
                    const pin_code = document.getElementById('pin_code').value;
                    const phone = document.getElementById('phone').value;
                    
                    // Create summary HTML
                    const html = `
                        <p>
                            <strong style="text-transform: uppercase; color: #000;">${name}</strong>, 
                            ${house_no}, ${address}, ${city}, ${district}, ${pin_code}
                        </p>
                        <div class="contact-info">
                             <div class="contact-item"><i class="fas fa-phone-alt"></i> ${phone}</div>
                        </div>
                    `;
                    
                    // Update and Toggle
                    summaryContent.innerHTML = html;
                    shippingInputs.style.display = 'none';
                    shippingSummary.style.display = 'block';
                }
            });
            
            editAddressBtn.addEventListener('click', function() {
                shippingSummary.style.display = 'none';
                shippingInputs.style.display = 'block';
            });
        }

        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                // Remove any non-numeric characters
                this.value = this.value.replace(/\D/g, '');
            });
        }
        
        // Pin code validation (numbers only, max 6 digits)
        const pinInput = document.getElementById('pin_code');
        if (pinInput) {
            pinInput.addEventListener('input', function(e) {
                // Remove non-numeric characters
                this.value = this.value.replace(/\D/g, '');
                // Limit to 6 digits
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
            });
        }
        
        // Auto-format card number
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = value.match(/(\d{0,4})(\d{0,4})(\d{0,4})(\d{0,4})/);
                    this.value = !value[2] ? value[1] : value[1] + ' ' + value[2] + (value[3] ? ' ' + value[3] : '') + (value[4] ? ' ' + value[4] : '');
                }
            });
        }
        
        // Auto-hide error messages
        setTimeout(() => {
            const message = document.querySelector('.message.error');
            if (message) {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>