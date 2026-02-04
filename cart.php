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

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);

        // Check if exists in cart
        $check = $conn->query("SELECT id, quantity FROM cart WHERE user_id=$user_id AND product_id=$product_id");
        if ($check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $new_qty = $row['quantity'] + $quantity;
            $conn->query("UPDATE cart SET quantity=$new_qty WHERE id={$row['id']}");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
        }
        $message = "Product added to cart!";
    } 
    elseif ($action == 'update') {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        if($quantity > 0) {
            $conn->query("UPDATE cart SET quantity=$quantity WHERE id=$cart_id AND user_id=$user_id");
        }
    }
    elseif ($action == 'remove') {
        $cart_id = intval($_POST['cart_id']);
        $conn->query("DELETE FROM cart WHERE id=$cart_id AND user_id=$user_id");
        $message = "Item removed from cart!";
    }
    elseif ($action == 'clear') {
        $conn->query("DELETE FROM cart WHERE user_id=$user_id");
        $message = "Cart cleared!";
    }
}

// Fetch Cart
$sql_with_gst = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.description, p.price, p.gst, p.image, p.stock
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = $user_id
        ORDER BY c.id DESC";
        
$result = $conn->query($sql_with_gst);

if ($result === false) {
    // If it fails (missing column), use the backup query
    $sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.description, p.price, p.image, p.stock
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = $user_id
        ORDER BY c.id DESC";
    $result = $conn->query($sql);
}
$total = 0;
$total_gst = 0;
$item_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Organic Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            min-height: 60px;
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
            transition: all 0.3s ease;
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

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .cart-header h2 {
            color: #3a6627;
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-header h2 i {
            color: #4c8334;
        }

        .user-greeting {
            color: #6b8c5d;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
        }

        .cart-items {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(76, 131, 52, 0.1);
            padding: 1.5rem;
        }

        .cart-summary {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(76, 131, 52, 0.1);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #6b8c5d;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #e0f0d6;
            margin-bottom: 1rem;
        }

        .empty-cart h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #3a6627;
        }

        .empty-cart p {
            margin-bottom: 2rem;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e8f5e0;
            transition: background-color 0.3s ease;
        }

        .cart-item:hover {
            background-color: #f9fff5;
        }

        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
            margin-right: 1.5rem;
            background-color: #f9fff5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8bc34a;
            font-size: 2.5rem;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: #6b8c5d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            max-width: 400px;
        }

        .product-price {
            font-weight: 600;
            color: #4c8334;
            font-size: 1.1rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            background: #f0f7ec;
            padding: 5px;
            border-radius: 25px;
        }

        .quantity-btn {
            background: white;
            border: 1px solid #e0f0d6;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #3a6627;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #4c8334;
            color: white;
            border-color: #4c8334;
        }

        .quantity-input {
            width: 40px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: bold;
            color: #2d5016;
            font-size: 1rem;
            -moz-appearance: textfield; /* Remove arrows in Firefox */
        }
        
        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none; /* Remove arrows in Chrome/Safari */
            margin: 0;
        }
            font-weight: 600;
            color: #2d5016;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #4c8334;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-btn:hover {
            background-color: #ffebee;
        }

        .item-subtotal {
            font-weight: 600;
            color: #2d5016;
            font-size: 1.2rem;
            margin-left: 1rem;
            min-width: 100px;
            text-align: right;
        }

        .summary-title {
            font-size: 1.5rem;
            color: #3a6627;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e8f5e0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #e0f0d6;
        }

        .summary-row.total {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: 700;
            color: #3a6627;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #e8f5e0;
        }

        .summary-label {
            color: #6b8c5d;
        }

        .summary-value {
            font-weight: 600;
            color: #2d5016;
        }

        .btn {
            background: linear-gradient(to right, #4c8334, #6fa352);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            background: linear-gradient(to right, #3a6627, #4c8334);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 131, 52, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #4c8334;
            border: 2px solid #4c8334;
        }

        .btn-secondary:hover {
            background: #e8f5e0;
            color: #3a6627;
            transform: none;
            box-shadow: none;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-buttons .btn {
            margin-top: 0;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .continue-shopping {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #4c8334;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: color 0.3s ease;
        }

        .continue-shopping:hover {
            color: #3a6627;
        }

        .stock-warning {
            color: #e67e22;
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stock-warning.low {
            color: #e74c3c;
        }

        .mobile-remove {
            display: none;
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                position: relative;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .quantity-controls {
                width: 100%;
                justify-content: center;
                margin: 1rem 0;
            }
            
            .item-subtotal {
                text-align: left;
                margin-left: 0;
                margin-top: 1rem;
            }
            
            .item-actions {
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
            }
            
            .remove-btn span {
                display: none;
            }
            
            .mobile-remove {
                display: block;
            }
            
            .desktop-remove {
                display: none;
            }
            
            .cart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
                height: auto;
            }
            
            nav ul {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .container {
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
                <li class="cart-icon">
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($result->num_rows > 0): ?>
                            <span class="cart-count"><?php echo $result->num_rows; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="cart-header">
            <h2><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h2>
            <div class="user-greeting">
                Welcome back, <?php echo htmlspecialchars($user_name); ?>!
            </div>
        </div>

        <?php if($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): 
            $item_count = $result->num_rows;
            $result->data_seek(0); // Reset pointer
        ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php while($row = $result->fetch_assoc()): 
                        $subtotal = $row['price'] * $row['quantity'];
                        $gst_rate = isset($row['gst']) ? $row['gst'] : 0;
                        $gst_amount = ($row['price'] * ($gst_rate / 100)) * $row['quantity'];
                        $total += $subtotal;
                        $total_gst += $gst_amount;
                        $stock_class = $row['stock'] < 10 ? 'low' : '';
                    ?>
                        <div class="cart-item" id="item-<?php echo $row['cart_id']; ?>">
                            <div class="product-image">
                                <?php if($row['image']): ?>
                                    <img src="assets/images/products/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-carrot"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-details">
                                <div class="product-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <?php if($row['description']): ?>
                                    <div class="product-description"><?php echo htmlspecialchars($row['description']); ?></div>
                                <?php endif; ?>
                                <div class="product-price">
                                    ₹<?php echo number_format($row['price'], 2); ?>
                                    <?php if(isset($row['gst']) && $row['gst'] > 0): ?>
                                        <div style="font-size: 0.8em; color: #666; font-weight: normal;">(<?php echo $row['gst']; ?>% GST)</div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($row['stock'] < $row['quantity']): ?>
                                    <div class="stock-warning low">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Only <?php echo $row['stock']; ?> left in stock
                                    </div>
                                <?php elseif($row['stock'] < 10): ?>
                                    <div class="stock-warning <?php echo $stock_class; ?>">
                                        <i class="fas fa-info-circle"></i>
                                        Low stock: <?php echo $row['stock']; ?> remaining
                                    </div>
                                <?php endif; ?>
                                
                                <div class="quantity-controls">
                                    <form method="POST" action="cart.php" class="quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                                        
                                        <button type="button" class="quantity-btn decrease" onclick="updateQuantity(<?php echo $row['cart_id']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" min="1" max="<?php echo $row['stock']; ?>" 
                                               class="quantity-input" id="quantity-<?php echo $row['cart_id']; ?>"
                                               onchange="submitQuantity(<?php echo $row['cart_id']; ?>)">
                                               
                                        <button type="button" class="quantity-btn increase" onclick="updateQuantity(<?php echo $row['cart_id']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                    
                                    <div class="item-actions">
                                        <button class="mobile-remove" onclick="removeItem(<?php echo $row['cart_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <form method="POST" action="cart.php" class="desktop-remove">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                                            <button type="submit" class="remove-btn">
                                                <i class="fas fa-trash"></i> <span>Remove</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="item-subtotal">
                                ₹<?php echo number_format($subtotal, 2); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Items (<?php echo $item_count; ?>)</span>
                        <span class="summary-value">₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Shipping</span>
                        <span class="summary-value">FREE</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">GST</span>
                        <span class="summary-value">₹<?php echo number_format($total_gst, 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">₹<?php echo number_format($total + $total_gst, 2); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>
                    
                    <div class="action-buttons">
                        <form method="POST" action="cart.php" style="width: 100%;">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to clear your cart?')">
                                <i class="fas fa-trash-alt"></i> Clear Cart
                            </button>
                        </form>
                        <a href="shop.php" class="btn btn-secondary">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any organic goodies to your cart yet!</p>
                <a href="shop.php" class="btn">
                    <i class="fas fa-store"></i> Start Shopping
                </a>
                <a href="index.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(cartId, change) {
            const input = document.getElementById(`quantity-${cartId}`);
            let newValue = parseInt(input.value) + change;
            const max = parseInt(input.max);
            const min = parseInt(input.min);
            
            if (newValue >= min && newValue <= max) {
                input.value = newValue;
                submitQuantity(cartId);
            }
        }
        
        function submitQuantity(cartId) {
            const form = document.querySelector(`#item-${cartId} .quantity-form`);
            form.submit();
        }
        
        function removeItem(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cart.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'remove';
                
                const cartIdInput = document.createElement('input');
                cartIdInput.type = 'hidden';
                cartIdInput.name = 'cart_id';
                cartIdInput.value = cartId;
                
                form.appendChild(actionInput);
                form.appendChild(cartIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-hide success message after 3 seconds
        setTimeout(() => {
            const message = document.querySelector('.message');
            if (message) {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 500);
            }
        }, 3000);
    </script>
</body>
</html>