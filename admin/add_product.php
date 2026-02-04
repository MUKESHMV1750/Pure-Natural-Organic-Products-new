<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../config/db.php';

$message = "";
$message_type = ""; // success or error

// Fetch categories for dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY category_name");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $gst = floatval($_POST['gst']);
    $description = trim($_POST['description']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0; // Default to 0 if not set
    $stock = intval($_POST['stock']);
    
    // Image Handling
    $image_path = "";
    $target_dir = "../assets/images/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        
        // Generate unique filename
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $unique_filename;
        
        // Validate file
        if (in_array($file_extension, $allowed_extensions)) {
            if ($_FILES["image"]["size"] <= 5000000) { // 5MB limit
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = $unique_filename;
                    $message_type = "success";
                } else {
                    $message = "Error uploading image file.";
                    $message_type = "error";
                }
            } else {
                $message = "File is too large. Maximum size is 5MB.";
                $message_type = "error";
            }
        } else {
            $message = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            $message_type = "error";
        }
    } elseif (isset($_POST['image_url']) && !empty(trim($_POST['image_url']))) {
        $image_url = trim($_POST['image_url']);
        // Basic URL validation
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            $image_path = $image_url;
            $message_type = "success";
        } else {
            $message = "Please enter a valid image URL.";
            $message_type = "error";
        }
    } else {
        // Use default placeholder
        $image_path = "products/placeholder.png";
        $message_type = "success";
    }
    
    if ($message_type != "error") {
        $sql = "INSERT INTO products (name, price, gst, description, image, category_id, stock) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddssii", $name, $price, $gst, $description, $image_path, $category_id, $stock);
        
        if ($stmt->execute()) {
            $message = "Product added successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
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
    <title>Add Product - Organic Store Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
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
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: white;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
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

        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e6ed;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent);
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }

        .form-group.required label::after {
            content: " *";
            color: var(--danger);
        }

        .form-group .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e6ed;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-group .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(76, 131, 52, 0.2);
        }

        .form-group textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
            line-height: 1.6;
        }

        .form-group select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
            padding-right: 45px;
        }

        /* Image Upload */
        .image-upload-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .image-upload-method {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .upload-method-btn {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: var(--border-radius);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-method-btn:hover {
            background: #e9ecef;
            border-color: var(--accent);
        }

        .upload-method-btn i {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 10px;
            display: block;
        }

        .upload-method-btn.active {
            background: #e8f5e0;
            border-color: var(--accent);
            color: var(--accent);
        }

        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .image-upload-area:hover {
            border-color: var(--accent);
            background: #f9fff5;
        }

        .image-upload-area i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }

        .image-upload-area input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .image-preview-container {
            margin-top: 20px;
            display: none;
        }

        .image-preview {
            width: 100%;
            max-width: 300px;
            height: 300px;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin: 0 auto;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            margin-top: 30px;
            border-top: 1px solid #e0e6ed;
        }

        /* Helper Text */
        .helper-text {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .helper-text i {
            color: var(--warning);
        }

        /* Character Counter */
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .char-counter.near-limit {
            color: var(--warning);
        }

        .char-counter.over-limit {
            color: var(--danger);
        }

        /* Responsive */
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .image-upload-method {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
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
                <h1><i class="fas fa-box-open"></i> Add New Product</h1>
            </div>
            
            <div class="header-actions">
                <a href="view_products.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data" id="productForm">
                <div class="form-grid">
                    <!-- Left Column -->
                    <div>
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-info-circle"></i> Product Information</h3>
                            
                            <div class="form-group required">
                                <label for="name">Product Name</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       placeholder="Enter product name" required
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                <div class="helper-text">
                                    <i class="fas fa-info-circle"></i> Enter a descriptive name for your product
                                </div>
                            </div>

                            <div class="form-group required">
                                <label for="price">Price (₹)</label>
                                <input type="number" id="price" name="price" class="form-control" 
                                       step="0.01" min="0" required
                                       placeholder="0.00"
                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                                <div class="helper-text">
                                    <i class="fas fa-info-circle"></i> Enter price in INR
                                </div>
                            </div>
                            
                            <div class="form-group required">
                                <label for="gst">GST (%)</label>
                                <input type="number" id="gst" name="gst" class="form-control" 
                                       step="0.01" min="0" required
                                       placeholder="0"
                                       value="<?php echo isset($_POST['gst']) ? htmlspecialchars($_POST['gst']) : '0'; ?>">
                                <div class="helper-text">
                                    <i class="fas fa-info-circle"></i> Enter GST percentage (e.g. 5, 12, 18)
                                </div>
                            </div>

                            <!-- Category Field Removed
                            <div class="form-group required">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">Select a category</option>
                                    <?php while($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['id']; ?>"
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="helper-text">
                                    <i class="fas fa-info-circle"></i> Choose the appropriate category for your product
                                </div>
                            </div>
                            -->

                            <div class="form-group required">
                                <label for="stock">Stock Quantity</label>
                                <input type="number" id="stock" name="stock" class="form-control" 
                                       min="0" required
                                       value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '0'; ?>">
                                <div class="helper-text">
                                    <i class="fas fa-info-circle"></i> Enter the available quantity in stock
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-image"></i> Product Image</h3>
                            
                            <div class="image-upload-container">
                                <div class="image-upload-method">
                                    <div class="upload-method-btn active" id="uploadBtn">
                                        <i class="fas fa-upload"></i>
                                        <span>Upload Image</span>
                                    </div>
                                    <div class="upload-method-btn" id="urlBtn">
                                        <i class="fas fa-link"></i>
                                        <span>Image URL</span>
                                    </div>
                                </div>

                                <!-- Image Upload Section -->
                                <div id="uploadSection">
                                    <div class="image-upload-area">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <h4>Drag & drop your image here</h4>
                                        <p>or click to browse</p>
                                        <input type="file" name="image" id="imageUpload" accept="image/*">
                                        <div class="helper-text">
                                            <i class="fas fa-info-circle"></i> Supported formats: JPG, PNG, GIF, WEBP (Max 5MB)
                                        </div>
                                    </div>
                                    
                                    <div class="image-preview-container" id="previewContainer">
                                        <h4>Preview</h4>
                                        <div class="image-preview" id="imagePreview">
                                            <i class="fas fa-image" style="font-size: 2rem; color: #bdc3c7;"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- URL Input Section -->
                                <div id="urlSection" style="display: none;">
                                    <div class="form-group">
                                        <label for="image_url">Image URL</label>
                                        <input type="url" id="image_url" name="image_url" class="form-control" 
                                               placeholder="https://example.com/product-image.jpg"
                                               value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>">
                                        <div class="helper-text">
                                            <i class="fas fa-info-circle"></i> Make sure to copy the direct <strong>Image Address</strong>, not the website URL.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-align-left"></i> Description</h3>
                            <div class="form-group">
                                <label for="description">Product Description</label>
                                <textarea id="description" name="description" class="form-control" 
                                          placeholder="Describe your product in detail..."
                                          rows="6"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="char-counter" id="charCounter">
                                    <span id="charCount">0</span> / 2000 characters
                                </div>
                                <div class="helper-text">
                                    <i class="fas fa-info-circle"></i> Include details about features, benefits, and usage
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="view_products.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Image upload method toggle
        const uploadBtn = document.getElementById('uploadBtn');
        const urlBtn = document.getElementById('urlBtn');
        const uploadSection = document.getElementById('uploadSection');
        const urlSection = document.getElementById('urlSection');
        const imageUpload = document.getElementById('imageUpload');
        const imagePreview = document.getElementById('imagePreview');
        const previewContainer = document.getElementById('previewContainer');
        
        uploadBtn.addEventListener('click', () => {
            uploadBtn.classList.add('active');
            urlBtn.classList.remove('active');
            uploadSection.style.display = 'block';
            urlSection.style.display = 'none';
            document.querySelector('input[name="image_url"]').required = false;
            imageUpload.required = true;
        });
        
        urlBtn.addEventListener('click', () => {
            urlBtn.classList.add('active');
            uploadBtn.classList.remove('active');
            urlSection.style.display = 'block';
            uploadSection.style.display = 'none';
            imageUpload.required = false;
            document.querySelector('input[name="image_url"]').required = true;
        });
        
        // Image preview for File Upload
        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    // Use consistent styling for the preview
                    imagePreview.innerHTML = `<img src="${reader.result}" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
                    previewContainer.style.display = 'block';
                });
                
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });

        // Enhanced Drag & Drop Support
        const uploadArea = document.querySelector('.image-upload-area');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.style.borderColor = 'var(--accent)';
            uploadArea.style.backgroundColor = '#f1f8e9'; // Light green background
            uploadArea.style.transform = 'scale(1.02)';
        }

        function unhighlight(e) {
            uploadArea.style.borderColor = '#dee2e6';
            uploadArea.style.backgroundColor = '';
            uploadArea.style.transform = 'scale(1)';
        }

        uploadArea.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files && files.length > 0) {
                // Manually assign the dropped files to the file input
                imageUpload.files = files;
                // Manually trigger the change event to update preview
                const event = new Event('change');
                imageUpload.dispatchEvent(event);
            }
        });

        // Image preview for URL Input
        const urlInput = document.querySelector('input[name="image_url"]');
        urlInput.addEventListener('input', function() {
            const url = this.value.trim();
            if (url) {
                // Use a placeholder if it fails, instead of destroying the container
                imagePreview.innerHTML = `<img src="${url}" alt="Preview" style="max-height: 200px; border-radius: 8px;" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Preview+Error';">`;
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
        });
        
        // Character counter for description
        const description = document.getElementById('description');
        const charCounter = document.getElementById('charCounter');
        const charCount = document.getElementById('charCount');
        
        description.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            if (length > 1800) {
                charCounter.classList.remove('near-limit');
                charCounter.classList.add('over-limit');
            } else if (length > 1500) {
                charCounter.classList.add('near-limit');
                charCounter.classList.remove('over-limit');
            } else {
                charCounter.classList.remove('near-limit', 'over-limit');
            }
        });
        
        // Initialize character count
        charCount.textContent = description.value.length;
        
        // Form validation
        const productForm = document.getElementById('productForm');
        
        productForm.addEventListener('submit', function(e) {
            let isValid = true;
            const price = parseFloat(document.getElementById('price').value);
            const stock = parseInt(document.getElementById('stock').value);
            
            // Validate price
            if (price < 0) {
                alert('Price cannot be negative.');
                isValid = false;
            }
            
            // Validate stock
            if (stock < 0) {
                alert('Stock quantity cannot be negative.');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Product...';
                submitBtn.disabled = true;
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