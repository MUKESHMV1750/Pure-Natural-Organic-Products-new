<?php
session_start();
include 'config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: shop.php");
    exit();
}

$product = $result->fetch_assoc();

// Get related products (same category)
$related_sql = "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4";
$stmt = $conn->prepare($related_sql);
$stmt->bind_param("ii", $product['category_id'], $product['id']);
$stmt->execute();
$related_result = $stmt->get_result();

// Check stock status
$stock_class = '';
$stock_text = '';
if ($product['stock'] == 0) {
    $stock_class = 'out-of-stock';
    $stock_text = 'Out of Stock';
} elseif ($product['stock'] < 10) {
    $stock_class = 'low-stock';
    $stock_text = 'Low Stock';
} else {
    $stock_class = 'in-stock';
    $stock_text = 'In Stock';
}

// Check if product is in cart
$in_cart = false;
$cart_quantity = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_check = $conn->query("SELECT quantity FROM cart WHERE user_id = $user_id AND product_id = $id");
    if ($cart_check->num_rows > 0) {
        $in_cart = true;
        $cart_quantity = $cart_check->fetch_assoc()['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Organic Store</title>
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
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .breadcrumb i {
            font-size: 0.8rem;
        }

        /* Product Container */
        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        @media (max-width: 992px) {
            .product-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        /* Product Gallery */
        .product-gallery {
            position: relative;
        }

        .main-image {
            width: 100%;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-height: 400px;
            padding: 20px;
        }

        .image-placeholder {
            width: 100%;
            height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray);
        }

        .image-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--gray-light);
        }

        .product-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
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

        .stock-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
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

        /* Product Info */
        .product-info {
            padding: 1rem 0;
        }

        .product-title {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--gray);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffb400;
        }

        .product-category {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-light);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-price span {
            font-size: 1.5rem;
            color: var(--gray);
            text-decoration: line-through;
        }

        .product-description {
            color: #555;
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .product-details {
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--gray-light);
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark);
        }

        .detail-value {
            color: var(--gray);
        }

        /* Add to Cart Form */
        .cart-form {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
        }

        .quantity-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 100px;
        }

        .quantity-input-group {
            display: flex;
            align-items: center;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            overflow: hidden;
            width: 150px;
        }

        .quantity-btn {
            background: var(--gray-light);
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--dark);
            font-weight: bold;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: white;
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border: none;
            padding: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            background: white;
        }

        .quantity-input:focus {
            outline: none;
        }

        .stock-warning {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--warning);
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .stock-warning i {
            font-size: 1rem;
        }

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
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 131, 52, 0.3);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .in-cart-message {
            background: #e8f5e0;
            color: var(--secondary);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Related Products */
        .related-products {
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 2px solid var(--gray-light);
        }

        .section-title {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(76, 131, 52, 0.15);
        }

        .product-card-image {
            height: 200px;
            overflow: hidden;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .product-card:hover .product-card-image img {
            transform: scale(1.05);
        }

        .product-card-content {
            padding: 1.5rem;
        }

        .product-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8rem;
        }

        .product-card-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .product-card-btn {
            background: var(--gray-light);
            color: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
        }

        .product-card-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* No Related Products */
        .no-related {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .no-related i {
            font-size: 3rem;
            color: var(--gray-light);
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .product-title {
                font-size: 1.8rem;
            }
            
            .product-price {
                font-size: 2rem;
            }
            
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
            
            .main-image {
                min-height: 300px;
            }
            
            .image-placeholder {
                height: 300px;
            }
            
            .quantity-control {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .product-container {
                gap: 1.5rem;
            }
            
            .product-title {
                font-size: 1.5rem;
            }
            
            .cart-form {
                padding: 1.5rem;
            }
            
            .main-image {
                min-height: 250px;
            }
            
            .image-placeholder {
                height: 250px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .product-container, .related-products {
            animation: fadeIn 0.5s ease;
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
                            <span class="cart-count">0</span>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="shop.php">Shop</a>
            <i class="fas fa-chevron-right"></i>
            <?php if($product['category_name']): ?>
                <a href="shop.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
                <i class="fas fa-chevron-right"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Product Container -->
        <div class="product-container">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <div class="product-badge badge-organic">
                    <i class="fas fa-leaf"></i> Organic
                </div>
                
                <div class="stock-badge <?php echo $stock_class; ?>">
                    <?php echo $stock_text; ?>
                </div>
                
                <div class="main-image">
                    <?php if($product['image'] && file_exists('assets/images/products/' . $product['image'])): ?>
                        <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-carrot"></i>
                            <span>Product Image</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta">
                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span style="color: var(--gray); margin-left: 5px;">(4.5)</span>
                    </div>
                    
                    <?php if($product['category_name']): ?>
                        <div class="product-category">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-price">
                    ₹<?php echo number_format($product['price'], 2); ?>
                </div>
                
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <div class="product-details">
                    <div class="detail-item">
                        <span class="detail-label">Product ID</span>
                        <span class="detail-value">#<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Availability</span>
                        <span class="detail-value <?php echo $stock_class; ?>"><?php echo $stock_text; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Items in Stock</span>
                        <span class="detail-value"><?php echo $product['stock']; ?> units</span>
                    </div>
                </div>

                <!-- Add to Cart Form -->
                <div class="cart-form">
                    <?php if($in_cart): ?>
                        <div class="in-cart-message">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Already in your cart!</strong><br>
                                Current quantity: <?php echo $cart_quantity; ?> units
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="cart.php" method="POST" id="addToCartForm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div class="quantity-control">
                            <label class="quantity-label">Quantity:</label>
                            <div class="quantity-input-group">
                                <button type="button" class="quantity-btn decrease">-</button>
                                <input type="number" name="quantity" class="quantity-input" 
                                       value="<?php echo $in_cart ? max(1, $cart_quantity) : 1; ?>" 
                                       min="1" max="<?php echo $product['stock']; ?>" 
                                       id="quantityInput">
                                <button type="button" class="quantity-btn increase">+</button>
                            </div>
                        </div>
                        
                        <?php if($product['stock'] < 10 && $product['stock'] > 0): ?>
                            <div class="stock-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Only <?php echo $product['stock']; ?> left in stock - order soon!
                            </div>
                        <?php endif; ?>
                        
                        <?php if($product['stock'] > 0): ?>
                            <button type="submit" class="btn" id="addToCartBtn">
                                <i class="fas fa-shopping-cart"></i>
                                <?php echo $in_cart ? 'Update Cart' : 'Add to Cart'; ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn" disabled>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </button>
                            <button type="button" class="btn btn-secondary" style="margin-top: 10px;">
                                <i class="fas fa-bell"></i> Notify When Available
                            </button>
                        <?php endif; ?>
                    </form>
                    
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <div style="text-align: center; margin-top: 1rem; color: var(--gray);">
                            <i class="fas fa-info-circle"></i>
                            <a href="login.php" style="color: var(--primary); font-weight: 600; margin-left: 5px;">
                                Login
                            </a> to purchase this item
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if($related_result->num_rows > 0): ?>
            <div class="related-products">
                <h2 class="section-title">
                    <i class="fas fa-heart"></i> You May Also Like
                </h2>
                
                <div class="products-grid">
                    <?php while($related = $related_result->fetch_assoc()): 
                        $related_stock_class = $related['stock'] == 0 ? 'out-of-stock' : ($related['stock'] < 10 ? 'low-stock' : 'in-stock');
                    ?>
                        <div class="product-card">
                            <a href="product.php?id=<?php echo $related['id']; ?>" style="text-decoration: none; color: inherit;">
                                <div class="product-card-image">
                                    <?php if($related['image'] && file_exists('assets/images/products/' . $related['image'])): ?>
                                        <img src="assets/images/products/<?php echo htmlspecialchars($related['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--gray-light);">
                                            <i class="fas fa-carrot" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-card-content">
                                    <h3 class="product-card-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                                    <div class="product-card-price">$<?php echo number_format($related['price'], 2); ?></div>
                                    <div style="font-size: 0.9rem; color: var(--gray); margin-bottom: 1rem;">
                                        <span class="<?php echo $related_stock_class; ?>" style="padding: 3px 8px; border-radius: 10px;">
                                            <?php echo $related['stock'] == 0 ? 'Out of Stock' : ($related['stock'] < 10 ? 'Low Stock' : 'In Stock'); ?>
                                        </span>
                                    </div>
                                    <button type="button" class="product-card-btn">
                                        View Product
                                    </button>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-related">
                <i class="fas fa-seedling"></i>
                <h3>No related products found</h3>
                <p>Check out our other organic products!</p>
                <a href="shop.php" class="btn" style="margin-top: 1rem; width: auto; display: inline-block;">
                    <i class="fas fa-store"></i> Browse All Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Quantity controls
        const quantityInput = document.getElementById('quantityInput');
        const decreaseBtn = document.querySelector('.quantity-btn.decrease');
        const increaseBtn = document.querySelector('.quantity-btn.increase');
        const addToCartBtn = document.getElementById('addToCartBtn');
        const addToCartForm = document.getElementById('addToCartForm');
        
        if (decreaseBtn && increaseBtn && quantityInput) {
            decreaseBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value);
                if (value > parseInt(quantityInput.min)) {
                    quantityInput.value = value - 1;
                }
            });
            
            increaseBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value);
                if (value < parseInt(quantityInput.max)) {
                    quantityInput.value = value + 1;
                }
            });
            
            quantityInput.addEventListener('change', () => {
                let value = parseInt(quantityInput.value);
                const min = parseInt(quantityInput.min);
                const max = parseInt(quantityInput.max);
                
                if (value < min) quantityInput.value = min;
                if (value > max) quantityInput.value = max;
            });
        }
        
        // Add to cart form submission
        if (addToCartForm) {
            addToCartForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    // Change button text to show loading
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                    submitBtn.disabled = true;
                    
                    // Revert after 3 seconds (in case of error)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
        }
        
        // Cart count update (you would typically get this from PHP session)
        function updateCartCount() {
            // This would normally be fetched via AJAX
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                // For demo, show a static number or fetch from session
                <?php 
                if(isset($_SESSION['user_id'])) {
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM cart WHERE user_id = {$_SESSION['user_id']}");
                    $cart_count = $count_result->fetch_assoc()['count'];
                    echo "cartCount.textContent = '$cart_count';";
                    echo "cartCount.style.display = '$cart_count > 0 ? \"flex\" : \"none\"';";
                } else {
                    echo "cartCount.style.display = 'none';";
                }
                ?>
            }
        }
        
        // Initialize cart count
        updateCartCount();
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Notify when available button
        const notifyBtn = document.querySelector('button[type="button"]:not([class*="quantity-"])');
        if (notifyBtn && notifyBtn.textContent.includes('Notify')) {
            notifyBtn.addEventListener('click', () => {
                const email = prompt('Enter your email to be notified when this product is back in stock:');
                if (email) {
                    // Here you would typically send this to your server
                    alert('Thank you! We will notify you at ' + email + ' when this product is back in stock.');
                }
            });
        }
    </script>
</body>
</html>