<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../config/db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id=$id");
    $_SESSION['success'] = "Product deleted successfully!";
    header("Location: view_products.php");
    exit();
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    if (isset($_POST['selected_products']) && !empty($_POST['selected_products'])) {
        $selected_ids = implode(',', array_map('intval', $_POST['selected_products']));
        
        if ($_POST['bulk_action'] == 'delete') {
            $conn->query("DELETE FROM products WHERE id IN ($selected_ids)");
            $_SESSION['success'] = "Selected products deleted successfully!";
        } elseif ($_POST['bulk_action'] == 'update_stock') {
            $stock_value = intval($_POST['bulk_stock_value']);
            $conn->query("UPDATE products SET stock = $stock_value WHERE id IN ($selected_ids)");
            $_SESSION['success'] = "Stock updated for selected products!";
        }
        
        header("Location: view_products.php");
        exit();
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? "WHERE name LIKE '%$search%' OR description LIKE '%$search%'" : '';

// Get total products for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM products $search_condition");
$total_products = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Fetch products with pagination
$result = $conn->query("SELECT * FROM products $search_condition ORDER BY id DESC LIMIT $limit OFFSET $offset");

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
    <title>Manage Products - Organic Store Admin</title>
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

        .menu-item span {
            font-weight: 500;
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

        .header h1 i {
            color: var(--accent);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            color: var(--accent);
        }

        .btn {
            background: linear-gradient(to right, var(--accent), #6fa352);
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover {
            background: linear-gradient(to right, #3a6627, var(--accent));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 131, 52, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .btn-secondary:hover {
            background: #e8f5e0;
            color: #3a6627;
        }

        .btn-danger {
            background: linear-gradient(to right, var(--danger), #e74c3c);
        }

        .btn-danger:hover {
            background: linear-gradient(to right, #c0392b, var(--danger));
        }

        /* Search and Filter */
        .search-filter {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e0e6ed;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(76, 131, 52, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
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

        /* Bulk Actions */
        .bulk-actions {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .bulk-select {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bulk-select input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }

        .bulk-actions-form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            flex-wrap: wrap;
        }

        .bulk-stock-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bulk-stock-input input {
            width: 80px;
            padding: 8px 12px;
            border: 2px solid #e0e6ed;
            border-radius: var(--border-radius);
        }

        /* Products Table */
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
            grid-template-columns: 50px 80px 2fr 1fr 1fr 1fr 150px;
            gap: 15px;
            align-items: center;
        }

        .table-row {
            padding: 20px;
            border-bottom: 1px solid #e0e6ed;
            display: grid;
            grid-template-columns: 50px 80px 2fr 1fr 1fr 1fr 150px;
            gap: 15px;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .table-row:hover {
            background-color: #f9f9f9;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 1.5rem;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .product-description {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-weight: 700;
            color: var(--accent);
            font-size: 1.1rem;
        }

        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .stock-high { background: #e8f5e9; color: #2e7d32; }
        .stock-medium { background: #fff3cd; color: #856404; }
        .stock-low { background: #f8d7da; color: #721c24; }
        .stock-out { background: #f5f5f5; color: #6c757d; }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .edit-btn:hover {
            background: #bbdefb;
        }

        .delete-btn {
            background: #ffebee;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }

        .delete-btn:hover {
            background: #ffcdd2;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
        }

        .pagination a {
            background: white;
            color: var(--secondary);
            border: 1px solid #e0e6ed;
        }

        .pagination a:hover, .pagination .current {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .pagination .disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
            border-color: #e0e0e0;
        }

        /* Stats Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.products { background: #3498db; }
        .stat-icon.stock { background: #2ecc71; }
        .stat-icon.value { background: #9b59b6; }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: #e0f0d6;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--secondary);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .table-header, .table-row {
                grid-template-columns: 50px 80px 2fr 1fr 1fr 150px;
            }
            
            .product-description {
                display: none;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .table-header, .table-row {
                grid-template-columns: 50px 80px 1fr 1fr 150px;
            }
            
            .stock-indicator {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .table-header {
                display: none;
            }
            
            .table-row {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 15px;
                border: 1px solid #e0e6ed;
                border-radius: var(--border-radius);
                margin-bottom: 15px;
            }
            
            .table-container {
                background: transparent;
                box-shadow: none;
            }
            
            .product-image {
                width: 100px;
                height: 100px;
                margin: 0 auto;
            }
            
            .action-buttons {
                justify-content: center;
            }
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Organic Store
            </div>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="view_products.php" class="menu-item active">
                <i class="fas fa-box-open"></i>
                <span>Products</span>
            </a>
            <a href="view_orders.php" class="menu-item">
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
            <div class="header-left">
                <span class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </span>
                <h1><i class="fas fa-box-open"></i> Products Management</h1>
            </div>
            
            <div class="header-actions">
                <a href="../index.php" target="_blank" class="btn-secondary" style="font-size: 0.9rem; padding: 10px 15px;">
                    <i class="fas fa-external-link-alt"></i> View Store
                </a>
            </div>
        </div>

        <?php if($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-boxes"></i> All Products
            </h2>
            <a href="add_product.php" class="btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <!-- Stats Summary -->
        <?php
        $total_stock = $conn->query("SELECT SUM(stock) as total FROM products")->fetch_assoc()['total'] ?? 0;
        $total_value = $conn->query("SELECT SUM(price * stock) as total FROM products")->fetch_assoc()['total'] ?? 0;
        ?>
        <div class="stats-summary">
            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Products</h3>
                    <p><?php echo $total_products; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stock">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Stock</h3>
                    <p><?php echo number_format($total_stock); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon value">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Inventory Value</h3>
                    <p>₹<?php echo number_format($total_value, 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter">
            <form method="GET" action="view_products.php" class="search-form">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search products by name or description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if($search): ?>
                    <a href="view_products.php" class="btn" style="background: var(--gray);">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form method="POST" action="view_products.php" class="bulk-actions" id="bulkActionsForm">
            <div class="bulk-select">
                <input type="checkbox" id="selectAll">
                <label for="selectAll" style="font-weight: 600; color: var(--dark);">Select All</label>
            </div>
            
            <div class="bulk-actions-form">
                <select name="bulk_action" class="bulk-action-select" style="padding: 10px; border-radius: var(--border-radius); border: 2px solid #e0e6ed;">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    <option value="update_stock">Update Stock</option>
                </select>
                
                <div class="bulk-stock-input" style="display: none;" id="stockInput">
                    <label>Set stock to:</label>
                    <input type="number" name="bulk_stock_value" min="0" value="0" style="padding: 8px 12px; border: 2px solid #e0e6ed; border-radius: var(--border-radius);">
                </div>
                
                <button type="submit" class="btn" id="applyBulkAction">
                    <i class="fas fa-check"></i> Apply
                </button>
                
                <span id="selectedCount" style="color: var(--gray); font-size: 0.9rem; margin-left: auto;">
                    0 items selected
                </span>
            </div>
        </form>

        <!-- Products Table -->
        <div class="table-container">
            <?php if($result->num_rows > 0): ?>
                <div class="table-header">
                    <div></div>
                    <div>Image</div>
                    <div>Product</div>
                    <div>Price</div>
                    <div>Stock</div>
                    <div>Status</div>
                    <div>Actions</div>
                </div>
                
                <form method="POST" action="view_products.php">
                    <?php while($row = $result->fetch_assoc()): 
                        $img = $row['image'] ? "../assets/images/products/" . $row['image'] : '../assets/images/placeholder.jpg';
                        
                        // Determine stock status
                        if ($row['stock'] == 0) {
                            $stock_class = 'stock-out';
                            $stock_text = 'Out of Stock';
                        } elseif ($row['stock'] < 10) {
                            $stock_class = 'stock-low';
                            $stock_text = 'Low Stock';
                        } elseif ($row['stock'] < 50) {
                            $stock_class = 'stock-medium';
                            $stock_text = 'Medium';
                        } else {
                            $stock_class = 'stock-high';
                            $stock_text = 'In Stock';
                        }
                    ?>
                        <div class="table-row" data-product-id="<?php echo $row['id']; ?>">
                            <div>
                                <input type="checkbox" name="selected_products[]" value="<?php echo $row['id']; ?>" class="product-checkbox">
                            </div>
                            
                            <div class="product-image">
                                <?php 
                                $img_src = null;
                                if (filter_var($row['image'], FILTER_VALIDATE_URL)) {
                                    $img_src = $row['image'];
                                } elseif ($row['image'] && file_exists('../assets/images/products/' . $row['image'])) {
                                    $img_src = "../assets/images/products/" . $row['image'];
                                }
                                
                                if($img_src): ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'fas fa-carrot\'></i>';">
                                <?php else: ?>
                                    <i class="fas fa-carrot"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <?php if($row['description']): ?>
                                    <div class="product-description"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . '...'; ?></div>
                                <?php endif; ?>
                                <div style="font-size: 0.85rem; color: var(--gray); margin-top: 5px;">
                                    ID: <?php echo $row['id']; ?> • Category: <?php echo $row['category'] ?? 'Uncategorized'; ?>
                                </div>
                            </div>
                            
                            <div class="product-price">₹<?php echo number_format($row['price'], 2); ?></div>
                            
                            <div class="stock-indicator">
                                <span style="font-weight: 600; color: var(--dark);"><?php echo $row['stock']; ?></span>
                                <span class="stock-badge <?php echo $stock_class; ?>"><?php echo $stock_text; ?></span>
                            </div>
                            
                            <div>
                                <?php 
                                $status = isset($row['status']) ? $row['status'] : 'active';
                                if($status == 'active'): 
                                ?>
                                    <span class="stock-badge stock-high" style="min-width: auto;">Active</span>
                                <?php else: ?>
                                    <span class="stock-badge stock-out" style="min-width: auto;">Inactive</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this product?\n\nProduct: <?php echo addslashes($row['name']); ?>');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No products found</h3>
                    <p><?php echo $search ? 'Try a different search term.' : 'Add your first product to get started.'; ?></p>
                    <a href="add_product.php" class="btn" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                    <span class="disabled"><i class="fas fa-angle-left"></i></span>
                <?php endif; ?>

                <?php 
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if($start > 1) echo '<span>...</span>';
                
                for($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="<?php echo $i == $page ? 'current' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if($end < $total_pages) echo '<span>...</span>'; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-right"></i></span>
                    <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Bulk actions functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const selectedCount = document.getElementById('selectedCount');
        const bulkActionSelect = document.querySelector('.bulk-action-select');
        const stockInput = document.getElementById('stockInput');
        const applyBulkAction = document.getElementById('applyBulkAction');
        const bulkActionsForm = document.getElementById('bulkActionsForm');
        
        // Select All functionality
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateSelectedCount();
        });
        
        // Update selected count
        function updateSelectedCount() {
            const selected = document.querySelectorAll('.product-checkbox:checked');
            selectedCount.textContent = selected.length + ' items selected';
            
            if (selected.length > 0) {
                applyBulkAction.disabled = false;
            } else {
                applyBulkAction.disabled = true;
            }
        }
        
        // Listen to individual checkbox changes
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
        
        // Show/hide stock input based on bulk action selection
        bulkActionSelect.addEventListener('change', function() {
            if (this.value === 'update_stock') {
                stockInput.style.display = 'flex';
            } else {
                stockInput.style.display = 'none';
            }
        });
        
        // Bulk actions form submission
        bulkActionsForm.addEventListener('submit', function(e) {
            const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
            
            if (selectedProducts.length === 0) {
                e.preventDefault();
                alert('Please select at least one product.');
                return;
            }
            
            const action = bulkActionSelect.value;
            if (!action) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return;
            }
            
            if (action === 'delete') {
                if (!confirm(`Are you sure you want to delete ${selectedProducts.length} product(s)?`)) {
                    e.preventDefault();
                }
            }
        });
        
        // Auto-hide success message
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
        }, 5000);
    </script>
</body>
</html>