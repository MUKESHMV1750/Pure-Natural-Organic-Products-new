<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch Order Details
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    echo "Order not found or access denied.";
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch Order Items
$items_sql = "SELECT oi.*, p.name, p.image FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Define status steps
$statuses = ['pending', 'processing', 'shipped', 'delivered'];
$current_status = strtolower($order['status']);
$current_step = array_search($current_status, $statuses);
if($current_step === false) {
    if ($current_status == 'completed') $current_step = 3; 
    else $current_step = -1; // cancelled or unknown
}

$cart_count = 0;
// Fetch Cart Count (for header)
$c_sql = "SELECT SUM(quantity) as val FROM cart WHERE user_id = $user_id";
$c_res = $conn->query($c_sql);
if($c_res && $row = $c_res->fetch_assoc()){
    $cart_count = $row['val'] ? $row['val'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_id; ?> - PureOrganic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #558b2f;
            --light-color: #f1f8e9;
            --dark-color: #1b5e20;
            --text-color: #333;
            --border-color: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --radius: 8px;
        }

        body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        /* Header styles reused */
        header { background: white; box-shadow: var(--shadow); margin-bottom: 30px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .logo { display: flex; align-items: center; gap: 10px; color: var(--primary-color); font-size: 1.8rem; font-weight: 700; }
        nav key { list-style: none; display: flex; gap: 20px; }
        nav ul { display: flex; list-style: none; gap: 25px; margin: 0; padding: 0;}
        nav a { text-decoration: none; color: var(--text-color); font-weight: 600; }
        .cart-icon { position:relative; }
        .cart-count { position: absolute; top:-8px; right:-8px; background: var(--primary-color); color: white; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:0.8rem; }
        .header-icons { display: flex; gap: 20px; align-items: center; }

        /* Tracking Styles */
        .order-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .order-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .order-id { font-size: 1.5rem; color: var(--dark-color); margin: 0; }
        .order-date { color: #666; font-size: 0.9rem; }

        /* Tracking Progress */
        .track-progress {
            position: relative;
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            padding: 0 20px;
        }
        
        .track-progress::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 40px;
            right: 40px;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 80px;
        }
        
        .step-icon {
            width: 35px;
            height: 35px;
            background: #e0e0e0;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .step.active .step-icon {
            background: var(--primary-color);
            transform: scale(1.2);
        }
        
        .step.completed .step-icon {
            background: var(--primary-color);
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #999;
            font-weight: 600;
        }
        
        .step.active .step-label, .step.completed .step-label {
            color: var(--dark-color);
        }
        
        /* Progress Bar Fill */
        .progress-line-fill {
            position: absolute;
            top: 15px;
            left: 40px;
            height: 4px;
            background: var(--primary-color);
            z-index: 1;
            width: 0%;
            transition: width 0.5s ease;
        }

        /* Items List */
        .order-items h3 { margin-bottom: 20px; color: var(--dark-color); }
        
        .item-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-row:last-child { border-bottom: none; }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            background: #f1f1f1;
        }
        
        .item-details { flex: 1; }
        .item-name { font-weight: 600; margin-bottom: 5px; display: block; }
        .item-meta { font-size: 0.9rem; color: #666; }
        .item-price { font-weight: 700; color: var(--primary-color); }
        
        .order-summary {
            background: var(--light-color);
            padding: 20px;
            border-radius: var(--radius);
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .summary-row.total {
            border-top: 1px solid rgba(0,0,0,0.1);
            padding-top: 10px;
            margin-top: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark-color);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                padding-bottom: 10px;
            }
            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .header-icons {
                margin-top: 10px;
            }
        }

        @media (max-width: 576px) {
            .track-progress { font-size: 0.8rem; }
            .step { width: auto; }
            .step-label { display: none; }
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" style="text-decoration:none;">
                    <div class="logo">
                        <i class="fas fa-leaf"></i>
                        <span>PureOrganic</span>
                    </div>
                </a>
                
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="shop.php">Shop</a></li>
                        <li><a href="my_orders.php" class="active">My Orders</a></li>
                    </ul>
                </nav>
                
                <div class="header-icons">
                    <a href="cart.php" style="text-decoration:none;">
                        <div class="cart-icon">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        </div>
                    </a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="my_orders.php" style="display:inline-block; margin-bottom:20px; color:#666; text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        
        <div class="order-card">
            <div class="order-header">
                <div>
                    <h2 class="order-id">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h2>
                    <span class="order-date">Placed on <?php echo date('F j, Y h:i A', strtotime($order['order_date'])); ?></span>
                </div>
                <div>
                    <span style="font-weight: 600; margin-right: 10px;">Total: ₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <?php if($current_status != 'cancelled'): ?>
            <div class="track-progress">
                <?php
                    $width = 0;
                    if ($current_step > 0) $width = ($current_step / 3) * 100;
                ?>
                <div class="progress-line-fill" style="width: <?php echo $width; ?>%;"></div>
                
                <div class="step <?php echo $current_step >= 0 ? 'active' : ''; ?> <?php echo $current_step > 0 ? 'completed' : ''; ?>">
                    <div class="step-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="step-label">Pending</div>
                </div>
                
                <div class="step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>">
                    <div class="step-icon"><i class="fas fa-box-open"></i></div>
                    <div class="step-label">Processing</div>
                </div>
                
                <div class="step <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $current_step > 2 ? 'completed' : ''; ?>">
                    <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                    <div class="step-label">Shipped</div>
                </div>
                
                <div class="step <?php echo $current_step >= 3 ? 'active' : ''; ?>">
                    <div class="step-icon"><i class="fas fa-check"></i></div>
                    <div class="step-label">Delivered</div>
                </div>
            </div>
            <?php else: ?>
                <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 30px;">
                    <i class="fas fa-times-circle"></i> This order has been cancelled.
                </div>
            <?php endif; ?>
            
            <div class="order-items">
                <h3>Items in your order</h3>
                <?php while($item = $items_result->fetch_assoc()): 
                     $img = (filter_var($item['image'], FILTER_VALIDATE_URL) ? $item['image'] : (($item['image'] && file_exists('assets/images/products/'.$item['image'])) ? 'assets/images/products/'.$item['image'] : 'https://placehold.co/100x100?text=No+Image'));
                ?>
                <div class="item-row">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Product" class="item-image" onerror="this.src='https://placehold.co/100x100?text=Err';">
                    <div class="item-details">
                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-meta">Qty: <?php echo $item['quantity']; ?></span>
                    </div>
                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>