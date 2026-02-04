<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_count = 0;
// Fetch Cart Count
$c_sql = "SELECT SUM(quantity) as val FROM cart WHERE user_id = $user_id";
$c_res = $conn->query($c_sql);
if($c_res && $row = $c_res->fetch_assoc()){
    $cart_count = $row['val'] ? $row['val'] : 0;
}

// Fetch User Orders
$orders_sql = "SELECT id, total_amount, status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - PureOrganic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Reusing styles from index/shop + specific styles */
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #558b2f;
            --light-color: #f1f8e9;
            --dark-color: #1b5e20;
            --text-color: #333;
            --light-text: #666;
            --border-color: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --radius: 8px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: var(--text-color);
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: white;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
            padding: 0;
            margin: 0;
        }
        nav a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
        }
        nav a:hover, nav a.active { color: var(--primary-color); }
        
        .header-icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .cart-icon { position: relative; }
        .cart-count {
            position: absolute;
            top: -8px; right: -8px;
            background: var(--primary-color); color: white;
            border-radius: 50%; width: 20px; height: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
        }    

        .page-title {
            margin-bottom: 30px;
            color: var(--dark-color);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .orders-table th, .orders-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .orders-table tr:hover {
            background-color: #fcfcfc;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-processing { background: #e3f2fd; color: #1565c0; }
        .status-shipped { background: #e8eaf6; color: #283593; }
        .status-delivered, .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }

        .btn-view {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .btn-view:hover {
            background-color: var(--dark-color);
        }
        
        .btn-track {
            display: inline-block;
            padding: 8px 16px;
            background-color: #fff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s;
            margin-right: 5px;
        }
        
        .btn-track:hover {
            background-color: var(--light-color);
        }

        .empty-orders {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .empty-orders i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .orders-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                padding: 15px 0;
            }

            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .header-icons {
                margin-top: 10px;
            }
            
            .container {
                width: 100%;
                padding: 0 15px;
            }
            
            .page-title {
                font-size: 1.5rem;
                text-align: center;
            }
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
        <h2 class="page-title"><i class="fas fa-shopping-bag"></i> My Orders</h2>

        <?php if ($orders_result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="track_order.php?id=<?php echo $order['id']; ?>" class="btn-track">
                                        <i class="fas fa-truck"></i> Track
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-orders">
                <i class="fas fa-box-open"></i>
                <h3>No orders yet</h3>
                <p>You haven't placed any orders yet. Start shopping now!</p>
                <a href="shop.php" class="btn-view" style="margin-top: 15px;">Browse Shop</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>