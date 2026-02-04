<?php
session_start();
include 'config/db.php';

// Fetch Products
$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
$result = $conn->query($sql);
$js_products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $category = $row['category_name'] ? strtolower($row['category_name']) : 'other';
        
        $image_path = 'https://placehold.co/400x300?text='.urlencode($row['name']);
        if (filter_var($row['image'], FILTER_VALIDATE_URL)) {
           $image_path = $row['image'];
        } elseif ($row['image'] && file_exists('assets/images/products/'.$row['image'])) {
            $image_path = 'assets/images/products/'.$row['image'];
        }

        $js_products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category' => $category,
            'price' => (float)$row['price'],
            'image' => $image_path
        ];
    }
}

// Fetch Categories for Filter Buttons
$cat_sql = "SELECT * FROM categories";
$cat_result = $conn->query($cat_sql);
$categories = [];
while($row = $cat_result->fetch_assoc()){
    $categories[] = strtolower($row['category_name']);
}

// Fetch Cart Count
$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    $c_sql = "SELECT SUM(quantity) as val FROM cart WHERE user_id = $user_id";
    $c_res = $conn->query($c_sql);
    if($c_res && $row = $c_res->fetch_assoc()){
        $cart_count = $row['val'] ? $row['val'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PureOrganic - Natural & Healthy Products</title>
    <!-- Use FontAwesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
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
            color: var(--text-color);
            line-height: 1.6;
            background-color: #f9f9f9;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
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
        
        .logo i {
            font-size: 2rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }
        
        nav a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            font-size: 1.05rem;
            transition: color 0.3s;
        }
        
        nav a:hover, nav a.active {
            color: var(--primary-color);
        }
        
        .header-icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .cart-icon {
            position: relative;
            cursor: pointer;
            color: var(--text-color);
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--primary-color);
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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1674&q=80');
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .hero p {
            font-size: 1.2rem;
            color: var(--light-text);
            max-width: 700px;
            margin: 0 auto 30px;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--dark-color);
        }
        
        /* Products Section */
        .section-title {
            text-align: center;
            margin: 60px 0 30px;
            font-size: 2.2rem;
            color: var(--dark-color);
        }
        
        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background-color: white;
            border: 1px solid var(--border-color);
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .filter-btn.active, .filter-btn:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        
        .product-card {
            background-color: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-img {
            height: 180px;
            width: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        
        .product-category {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: block;
            text-transform: capitalize;
        }
        
        .product-price {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 10px;
            background-color: var(--light-color);
            color: var(--primary-color);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        
        .add-to-cart:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--light-color);
        }
        
        .footer-column p, .footer-column a {
            color: #ccc;
            margin-bottom: 10px;
            display: block;
            text-decoration: none;
        }
        
        .footer-column a:hover {
            color: white;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .social-icons a:hover {
            background-color: var(--primary-color);
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            font-size: 0.9rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                padding-bottom: 15px;
            }
            
            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .container {
                padding: 0 20px;
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .filter-buttons {
                gap: 10px;
            }
            
            .filter-btn {
                padding: 6px 15px;
                font-size: 0.9rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
                        <li><a href="index.php" class="active">Home</a></li>
                        <li><a href="shop.php">Shop</a></li>
                        <li><a href="about.php">About</a></li>                        
                        <li><a href="my_orders.php">My Orders</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                
                <div class="header-icons">
                    <a href="cart.php" style="text-decoration:none;">
                        <div class="cart-icon">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        </div>
                    </a>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" style="color:var(--text-color)" title="Logout">
                            <i class="fas fa-sign-out-alt fa-lg"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" style="color:var(--text-color)" title="Login">
                            <i class="fas fa-user fa-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Pure & Natural Organic Products</h1>
            <p>Discover our curated selection of certified organic foods, supplements, and natural products for a healthier lifestyle. Fresh from farm to your table.</p>
            <a href="shop.php" class="btn">Shop Now</a>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products">
        <div class="container">
            <h2 class="section-title">Our Organic Products</h2>
            
            <!-- <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All Products</button>
                <?php foreach($categories as $cat): ?>
                    <button class="filter-btn" data-filter="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></button>
                <?php endforeach; ?>
            </div> -->
            
            <div class="products-grid" id="productsGrid">
                <!-- Products will be loaded by JavaScript -->
                 <p style="text-align:center; width:100%;">Loading products...</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>PureOrganic</h3>
                    <p>Providing fresh, certified organic products since 2010. Our mission is to make healthy, sustainable living accessible to everyone.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <a href="index.php">Home</a>
                    <a href="shop.php">Shop</a>
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact</a>
                </div>
                
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <a href="#">FAQ</a>
                    <a href="#">Shipping Policy</a>
                    <a href="#">Returns & Refunds</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Organic Street, Green City</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@pureorganic.com</p>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2026 PureOrganic. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Data From PHP Backend
        const products = <?php echo json_encode($js_products); ?>;

        // DOM elements
        const productsGrid = document.getElementById('productsGrid');
        const filterButtons = document.querySelectorAll('.filter-btn');

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            renderProducts('all');
            // setupEventListeners(); // Removed event listeners for filter buttons
        });

        // Render products based on filter
        function renderProducts(filter) {
            productsGrid.innerHTML = '';
            
            if(products.length === 0) {
                productsGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">No products found.</p>';
                return;
            }

            const filteredProducts = filter === 'all' 
                ? products 
                : products.filter(product => product.category === filter);
            
            if(filteredProducts.length === 0) {
                productsGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">No products in this category.</p>';
                return;
            }

            filteredProducts.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'product-card';
                // Note: Changed "Add to Cart" to "View Details" to link to the PHP product page
                productCard.innerHTML = `
                    <div style="height: 180px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f9f9f9;">
                        <img src="${product.image}" alt="${product.name}" style="height: 100%; width: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/400x300?text=Image+N/A';">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">${product.name}</h3>
                        <span class="product-category">${product.category}</span>
                        <div class="product-price">₹${product.price.toFixed(2)}</div>
                        <a href="product.php?id=${product.id}" class="add-to-cart">View Details</a>
                    </div>
                `;
                productsGrid.appendChild(productCard);
            });
        }

        // Setup event listeners
        function setupEventListeners() {
            // Filter buttons
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    // Render products with selected filter
                    const filter = this.getAttribute('data-filter');
                    renderProducts(filter);
                });
            });
        }
    </script>
</body>
</html>
