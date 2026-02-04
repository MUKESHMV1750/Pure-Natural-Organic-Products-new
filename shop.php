<?php
session_start();
include 'config/db.php';

// Get all categories for sidebar
$categories_sql = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$search = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT p.*, c.category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

if ($category_id) {
    $sql .= " AND p.category_id = $category_id";
}
if ($search) {
    $sql .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

// Sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

// Get total count for pagination
$count_sql = str_replace("SELECT p.*, c.category_name", "SELECT COUNT(*) as total", $sql);
$count_result = $conn->query($count_sql);
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Add pagination to main query
$sql .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Get cart count for logged in users
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = $user_id";
    $cart_result = $conn->query($cart_sql);
    $cart_count = $cart_result->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Organic Products - Organic Store</title>
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
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 5%;
            max-width: 1200px;
            height: 80px;
            margin: 0 auto;
        }

        /* Added Login/Register Button Styles */
        .auth-btn {
            background-color: var(--primary);
            color: white !important;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            margin-left: 10px;
        }

        .auth-btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
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
        }

        /* Shop Header */
        .shop-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="%23e8f5e0" d="M0,0 L100,0 L100,100 L0,100 Z"/><circle cx="20" cy="20" r="10" fill="%234c8334" opacity="0.1"/><circle cx="80" cy="30" r="15" fill="%234c8334" opacity="0.1"/><circle cx="40" cy="70" r="12" fill="%234c8334" opacity="0.1"/></svg>');
            border-radius: var(--border-radius);
        }

        .shop-header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .shop-header p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Shop Layout */
        .shop-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .shop-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .sidebar-title {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-title i {
            color: var(--primary);
        }

        /* Categories */
        .category-list {
            list-style: none;
        }

        .category-item {
            margin-bottom: 8px;
        }

        .category-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: var(--light);
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray);
            transition: var(--transition);
        }

        .category-link:hover, .category-link.active {
            background: var(--primary);
            color: white;
        }

        .category-link .count {
            background: white;
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .category-link.active .count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Filter Options */
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: var(--light);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-option:hover {
            background: var(--gray-light);
        }

        .filter-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .filter-option label {
            flex: 1;
            cursor: pointer;
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(76, 131, 52, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        /* Shop Content */
        .shop-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Toolbar */
        .shop-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-count {
            color: var(--gray);
            font-weight: 600;
        }

        .results-count strong {
            color: var(--dark);
        }

        .sort-options {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sort-options select {
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            background: white;
            color: var(--dark);
            font-weight: 500;
            cursor: pointer;
        }

        .sort-options select:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Product Card */
        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(76, 131, 52, 0.15);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        .badge-organic {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 3px 10px rgba(76, 131, 52, 0.3);
        }

        .badge-new {
            background: linear-gradient(to right, var(--warning), #ff9800);
            color: white;
        }

        .badge-sale {
            background: linear-gradient(to right, var(--danger), #e74c3c);
            color: white;
        }

        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .in-stock {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .low-stock {
            background-color: #fff3cd;
            color: #856404;
        }

        .out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }

        .product-image {
            height: 200px;
            overflow: hidden;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-image-placeholder {
            color: var(--gray);
            text-align: center;
            padding: 20px;
        }

        .product-image-placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: var(--gray-light);
        }

        .quick-view {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            opacity: 0;
            transition: var(--transition);
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-card:hover .quick-view {
            opacity: 1;
            pointer-events: auto;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-category {
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .product-category i {
            font-size: 0.8rem;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8rem;
        }

        .product-name a {
            color: inherit;
            text-decoration: none;
            transition: var(--transition);
        }

        .product-name a:hover {
            color: var(--primary);
        }

        .product-description {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8rem;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .current-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .original-price {
            font-size: 1rem;
            color: var(--gray);
            text-decoration: line-through;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            flex: 1;
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

        .btn-out-of-stock {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-out-of-stock:hover {
            background: #ccc;
            transform: none;
            box-shadow: none;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 3rem;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            min-width: 40px;
            text-align: center;
        }

        .pagination a {
            background: white;
            color: var(--secondary);
            border: 1px solid var(--gray-light);
        }

        .pagination a:hover, .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
            border-color: #e0e0e0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
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
            
            .shop-header h1 {
                font-size: 2rem;
            }
            
            .shop-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .sort-options, .results-count {
                width: 100%;
                justify-content: space-between;
            }
        }
                gap: 1rem;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .shop-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .sort-options {
                width: 100%;
            }
            
            .sort-options select {
                flex: 1;
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
                <li><a href="my_orders.php">My Orders</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="cart-icon">
                        <a href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if($cart_count > 0): ?>
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="auth-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="auth-btn" style="background-color: var(--secondary);"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Shop Header -->
        <div class="shop-header">
            <h1>Organic Products</h1>
            <p>Discover our selection of fresh, organic products. From farm to table, we ensure the highest quality for your health and wellness.</p>
        </div>

        <!-- Shop Layout -->
        <div class="shop-layout">
            <!-- Sidebar -->
            <aside class="shop-sidebar">
                <!-- Search -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-search"></i> Search
                    </h3>
                    <form method="GET" action="shop.php" class="search-box">
                        <input type="text" name="search" placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <i class="fas fa-search"></i>
                        <?php if($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Categories -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-tags"></i> Categories
                    </h3>
                    <ul class="category-list">
                        <li class="category-item">
                            <a href="shop.php" class="category-link <?php echo !$category_id ? 'active' : ''; ?>">
                                <span>All Products</span>
                                <span class="count"><?php 
                                    $all_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
                                    echo $all_count;
                                ?></span>
                            </a>
                        </li>
                        <?php while($category = $categories_result->fetch_assoc()): 
                            $cat_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = {$category['id']}")->fetch_assoc()['count'];
                            if ($cat_count > 0): ?>
                                <li class="category-item">
                                    <a href="shop.php?category=<?php echo $category['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="category-link <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                                        <span class="count"><?php echo $cat_count; ?></span>
                                    </a>
                                </li>
                            <?php endif;
                        endwhile; ?>
                    </ul>
                </div>

                <!-- Sort By -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-sort-amount-down"></i> Sort By
                    </h3>
                    <form method="GET" action="shop.php" class="filter-options" id="sortForm">
                        <?php if($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <?php if($search): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <div class="filter-option">
                            <input type="radio" id="sort_newest" name="sort" value="newest" 
                                   <?php echo $sort == 'newest' ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <label for="sort_newest">Newest Arrivals</label>
                        </div>
                        
                        <div class="filter-option">
                            <input type="radio" id="sort_price_low" name="sort" value="price_low" 
                                   <?php echo $sort == 'price_low' ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <label for="sort_price_low">Price: Low to High</label>
                        </div>
                        
                        <div class="filter-option">
                            <input type="radio" id="sort_price_high" name="sort" value="price_high" 
                                   <?php echo $sort == 'price_high' ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <label for="sort_price_high">Price: High to Low</label>
                        </div>
                        
                        <div class="filter-option">
                            <input type="radio" id="sort_name" name="sort" value="name" 
                                   <?php echo $sort == 'name' ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <label for="sort_name">Name: A to Z</label>
                        </div>
                    </form>
                </div>

                <!-- Featured Products -->
                <?php 
                $featured_sql = "SELECT * FROM products ORDER BY RAND() LIMIT 3";
                $featured_result = $conn->query($featured_sql);
                if ($featured_result->num_rows > 0): ?>
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-star"></i> Featured
                        </h3>
                        <div class="featured-products">
                            <?php while($featured = $featured_result->fetch_assoc()): ?>
                                <a href="product.php?id=<?php echo $featured['id']; ?>" 
                                   class="featured-product" 
                                   style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding: 10px; background: var(--light); border-radius: 8px; text-decoration: none; color: inherit; transition: var(--transition);">
                                    <div style="width: 50px; height: 50px; border-radius: 6px; overflow: hidden; background: var(--gray-light); display: flex; align-items: center; justify-content: center;">
                                        <?php if($featured['image'] && file_exists('assets/images/products/' . $featured['image'])): ?>
                                            <img src="assets/images/products/<?php echo htmlspecialchars($featured['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($featured['name']); ?>"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-carrot" style="color: var(--primary);"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 3px;"><?php echo htmlspecialchars($featured['name']); ?></div>
                                        <div style="font-weight: 700; color: var(--primary); font-size: 0.9rem;">₹<?php echo number_format($featured['price'], 2); ?></div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>

            <!-- Main Content -->
            <main class="shop-content">
                <!-- Toolbar -->
                <div class="shop-toolbar">
                    <div class="results-count">
                        Showing <strong><?php echo $total_products > 0 ? (($page - 1) * $limit + 1) : 0; ?>-<?php echo min($page * $limit, $total_products); ?></strong> 
                        of <strong><?php echo $total_products; ?></strong> products
                        <?php if($search): ?>
                            for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                        <?php endif; ?>
                        <?php if($category_id && $categories_result->num_rows > 0): 
                            $categories_result->data_seek(0);
                            while($cat = $categories_result->fetch_assoc()) {
                                if ($cat['id'] == $category_id) {
                                    echo ' in <strong>' . htmlspecialchars($cat['category_name']) . '</strong>';
                                    break;
                                }
                            }
                        endif; ?>
                    </div>
                    
                    <div class="sort-options">
                        <label for="mobileSort" style="font-weight: 600; color: var(--dark);">Sort by:</label>
                        <select id="mobileSort" onchange="window.location.href=this.value">
                            <option value="shop.php?<?php 
                                echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                     ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                     'sort=newest'; ?>" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                            <option value="shop.php?<?php 
                                echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                     ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                     'sort=price_low'; ?>" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="shop.php?<?php 
                                echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                     ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                     'sort=price_high'; ?>" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="shop.php?<?php 
                                echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                     ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                     'sort=name'; ?>" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                        </select>
                    </div>
                </div>

                <!-- Product Grid -->
                <?php if ($result->num_rows > 0): ?>
                    <div class="product-grid">
                        <?php while($row = $result->fetch_assoc()): 
                            $stock_class = '';
                            $stock_text = '';
                            if ($row['stock'] == 0) {
                                $stock_class = 'out-of-stock';
                                $stock_text = 'Out of Stock';
                            } elseif ($row['stock'] < 10) {
                                $stock_class = 'low-stock';
                                $stock_text = 'Low Stock';
                            } else {
                                $stock_class = 'in-stock';
                                $stock_text = 'In Stock';
                            }
                            
                            // Determine if product is new (added within last 30 days)
                            $is_new = strtotime($row['created_at']) > strtotime('-30 days');
                            $badge_class = $is_new ? 'badge-new' : 'badge-organic';
                            $badge_text = $is_new ? 'New' : 'Organic';
                        ?>
                            <div class="product-card">
                                <div class="product-badge <?php echo $badge_class; ?>">
                                    <?php echo $badge_text; ?>
                                </div>
                                
                                <div class="stock-badge <?php echo $stock_class; ?>">
                                    <?php echo $stock_text; ?>
                                </div>
                                
                                <div class="product-image">
                                    <?php 
                                        $img_src = '';
                                        if (filter_var($row['image'], FILTER_VALIDATE_URL)) {
                                            $img_src = htmlspecialchars($row['image']);
                                        } elseif ($row['image'] && file_exists('assets/images/products/' . $row['image'])) {
                                            $img_src = 'assets/images/products/' . htmlspecialchars($row['image']);
                                        }
                                    ?>
                                    
                                    <?php if($img_src): ?>
                                        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="fas fa-carrot"></i>
                                            <div>Product Image</div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="quick-view">
                                        <i class="fas fa-eye"></i> Quick View
                                    </div>
                                </div>
                                
                                <div class="product-info">
                                    <?php if($row['category_name']): ?>
                                        <div class="product-category">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($row['category_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="product-name">
                                        <a href="product.php?id=<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if($row['description']): ?>
                                        <p class="product-description">
                                            <?php echo htmlspecialchars(substr($row['description'], 0, 100)) . '...'; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="product-price">
                                        <span class="current-price">₹<?php echo number_format($row['price'], 2); ?></span>
                                        <?php if(!empty($row['original_price']) && $row['original_price'] > $row['price']): ?>
                                            <span class="original-price">₹<?php echo number_format($row['original_price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="product.php?id=<?php echo $row['id']; ?>" class="btn-secondary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if($row['stock'] > 0 && isset($_SESSION['user_id'])): ?>
                                            <form action="cart.php" method="POST" style="display: contents;">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn">
                                                    <i class="fas fa-cart-plus"></i> Add
                                                </button>
                                            </form>
                                        <?php elseif($row['stock'] == 0): ?>
                                            <button class="btn btn-out-of-stock" disabled>
                                                <i class="fas fa-times"></i> Sold Out
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php" class="btn">
                                                <i class="fas fa-sign-in-alt"></i> Login to Buy
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="shop.php?<?php 
                                    echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                         ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                         ($sort != 'newest' ? 'sort=' . $sort . '&' : '') . 
                                         'page=1'; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="shop.php?<?php 
                                    echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                         ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                         ($sort != 'newest' ? 'sort=' . $sort . '&' : '') . 
                                         'page=' . ($page - 1); ?>">
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
                                <a href="shop.php?<?php 
                                    echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                         ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                         ($sort != 'newest' ? 'sort=' . $sort . '&' : '') . 
                                         'page=' . $i; ?>" 
                                   class="<?php echo $i == $page ? 'current' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            if($end < $total_pages) echo '<span>...</span>';

                            <?php if($page < $total_pages): ?>
                                <a href="shop.php?<?php 
                                    echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                         ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                         ($sort != 'newest' ? 'sort=' . $sort . '&' : '') . 
                                         'page=' . ($page + 1); ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="shop.php?<?php 
                                    echo ($category_id ? 'category=' . $category_id . '&' : '') . 
                                         ($search ? 'search=' . urlencode($search) . '&' : '') . 
                                         ($sort != 'newest' ? 'sort=' . $sort . '&' : '') . 
                                         'page=' . $total_pages; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled"><i class="fas fa-angle-right"></i></span>
                                <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No products found</h3>
                        <p><?php 
                            if($search) {
                                echo "No products match your search for \"" . htmlspecialchars($search) . "\".";
                            } elseif($category_id) {
                                echo "No products found in this category.";
                            } else {
                                echo "No products available at the moment.";
                            }
                        ?></p>
                        <a href="shop.php" class="btn" style="width: auto; display: inline-block;">
                            <i class="fas fa-store"></i> Browse All Products
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // Quick search functionality
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.trim().length >= 2 || this.value.trim().length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }

        // Quick add to cart
        document.querySelectorAll('.product-actions form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    submitBtn.disabled = true;
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 2000);
                }
            });
        });

        // Quick view functionality
        document.querySelectorAll('.quick-view').forEach(quickView => {
            quickView.addEventListener('click', function(e) {
                e.preventDefault();
                const productCard = this.closest('.product-card');
                const productName = productCard.querySelector('.product-name').textContent;
                alert('Quick view for: ' + productName + '\n\nFull product details available on the product page.');
            });
        });

        // Category filter
        document.querySelectorAll('.category-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.classList.contains('active')) {
                    e.preventDefault();
                    // Remove search parameter if present
                    const url = new URL(window.location.href);
                    url.searchParams.delete('search');
                    window.location.href = url.toString();
                }
            });
        });

        // Mobile menu toggle (if needed)
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.shop-sidebar');
            sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
        }

        // Initialize any necessary elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add active class to current sort option
            const sortForm = document.getElementById('sortForm');
            if (sortForm) {
                const currentSort = '<?php echo $sort; ?>';
                sortForm.querySelectorAll('input[type="radio"]').forEach(radio => {
                    if (radio.value === currentSort) {
                        radio.checked = true;
                    }
                });
            }

            // Update cart count
            const cartCount = document.querySelector('.cart-count');
            <?php if($cart_count > 0): ?>
                if (cartCount) {
                    cartCount.textContent = '<?php echo $cart_count; ?>';
                    cartCount.style.display = 'flex';
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>