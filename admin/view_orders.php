<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../config/db.php';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $conn->query("UPDATE orders SET status='$status' WHERE id=$order_id");
    $_SESSION['success'] = "Order status updated successfully!";
    header("Location: view_orders.php");
    exit();
}

// Display success message
$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Organic Store Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #4c8334;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --border-radius: 8px;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .sidebar-header .logo i {
            color: #4c8334;
            font-size: 1.8rem;
        }

        .admin-info {
            margin-top: 15px;
            text-align: center;
        }

        .admin-info .admin-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .admin-info .admin-role {
            font-size: 0.85rem;
            color: #bdc3c7;
            background: rgba(255, 255, 255, 0.1);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding-left: 30px;
        }

        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--accent);
        }

        .menu-item i {
            width: 20px;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 25px;
            border-bottom: 1px solid #e0e6ed;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-secondary {
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #e8f5e0;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e0e6ed;
            background: #f8f9fa;
            font-weight: 600;
            color: var(--secondary);
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr 1fr 200px;
            gap: 15px;
            align-items: center;
        }

        .table-row {
            padding: 20px;
            border-bottom: 1px solid #e0e6ed;
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr 1fr 200px;
            gap: 15px;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .table-row:hover {
            background-color: #f9f9f9;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d6d8d9; color: #1b1e21; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Form Controls inside table */
        select.form-select {
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: white;
            margin-right: 5px;
        }

        .btn-sm {
            padding: 6px 12px;
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .btn-sm:hover {
            background-color: #3a6627;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .table-header { display: none; }
            .table-row { grid-template-columns: 1fr; border: 1px solid #dee2e6; margin-bottom: 10px; border-radius: 8px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Organic Store
            </div>
            <div class="admin-info">
                <?php $admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?>
                <div class="admin-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="view_products.php" class="menu-item">
                <i class="fas fa-box-open"></i>
                <span>Products</span>
            </a>
            <a href="view_orders.php" class="menu-item active">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
            <a href="manage_users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="reports.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../logout.php" class="menu-item" style="margin-top: 30px;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
            <div class="header-actions">
                <a href="../index.php" target="_blank" class="btn-secondary">
                    <i class="fas fa-external-link-alt"></i> View Store
                </a>
            </div>
        </div>

        <?php if($success): ?>
            <div class="message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <div class="table-container">
            <div class="table-header">
                <div>Order ID</div>
                <div>Customer</div>
                <div>Date</div>
                <div>Total</div>
                <div>Status</div>
                <div>Action</div>
            </div>
            
            <?php
            // Updated query to fetch all customer details
            $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    ORDER BY o.order_date DESC";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()): 
                $status_class = 'status-' . strtolower($row['status']);
                ?>
                <div class="table-row">
                    <div>#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></div>
                    
                    <!-- Customer Full Info -->
                    <div>
                        <div style="font-weight:600; font-size: 1.05rem;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                        <div style="font-size: 0.85rem; color: #555; margin-top: 4px;">
                            <i class="fas fa-envelope" style="width: 15px;"></i> <?php echo htmlspecialchars($row['user_email']); ?>
                        </div>
                        <?php if(!empty($row['phone'])): ?>
                        <div style="font-size: 0.85rem; color: #555; margin-top: 2px;">
                            <i class="fas fa-phone" style="width: 15px;"></i> <?php echo htmlspecialchars($row['phone']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Collapsible Address -->
                        <div style="font-size: 0.85rem; color: #777; margin-top: 4px; border-top: 1px dashed #eee; padding-top: 4px;">
                            <i class="fas fa-map-marker-alt" style="width: 15px;"></i> 
                            <?php 
                                // Display shipping address nicely
                                if (!empty($row['shipping_address'])) {
                                    // If raw shipping_address contains the concatenated string "Name:..., House...," cleans it up
                                    $addr_display = str_replace("Name: " . $row['user_name'] . ", ", "", $row['shipping_address']);
                                    echo htmlspecialchars(substr($addr_display, 0, 50)) . (strlen($addr_display) > 50 ? '...' : '');
                                } else {
                                    echo htmlspecialchars($row['city'] . ', ' . $row['state']);
                                } 
                            ?>
                        </div>
                    </div>

                    <div style="color:var(--gray);"><?php echo date('M d, Y', strtotime($row['order_date'])); ?></div>
                    <div style="font-weight:bold; color:var(--dark);">₹<?php echo number_format($row['total_amount'], 2); ?></div>
                    <div>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                    <div>
                        <form method="POST" style="display:flex; flex-direction:column; gap:8px;">
                            <div style="display: flex; gap: 5px;">
                                <select name="status" class="form-select" style="width: 110px;">
                                    <option value="pending" <?php echo ($row['status']=='pending'?'selected':''); ?>>Pending</option>
                                    <option value="processing" <?php echo ($row['status']=='processing'?'selected':''); ?>>Processing</option>
                                    <option value="shipped" <?php echo ($row['status']=='shipped'?'selected':''); ?>>Shipped</option>
                                    <option value="delivered" <?php echo ($row['status']=='delivered'?'selected':''); ?>>Delivered</option>
                                    <option value="cancelled" <?php echo ($row['status']=='cancelled'?'selected':''); ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="update_status" class="btn-sm" title="Save Status">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                            
                            <a href="print_invoice.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-sm" style="background: #34495e; text-decoration: none; text-align: center;">
                                <i class="fas fa-print"></i> Print Invoice
                            </a>
                        </form>
                    </div>
                </div>
            <?php endwhile; 
            else: ?>
                <div style="padding:40px; text-align:center; color:var(--gray);">
                    <i class="fas fa-box-open" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No orders found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
