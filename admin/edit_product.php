<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";
$message_type = "";

// Fetch Product
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view_products.php");
    exit();
}

$product = $result->fetch_assoc();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $gst = floatval($_POST['gst']);
    $description = trim($_POST['description']);
    // $category_id = intval($_POST['category_id']); // Removed category update
    $stock = intval($_POST['stock']);
    $image_path = $product['image'];

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($filetype, $allowed)) {
            $new_filename = uniqid() . "." . $filetype;
            if (move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/products/" . $new_filename)) {
                $image_path = $new_filename;
            } else {
                $message = "Failed to upload image.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid file type.";
            $message_type = "error";
        }
    } elseif (isset($_POST['image_url']) && !empty(trim($_POST['image_url']))) {
        $image_url = trim($_POST['image_url']);
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            $image_path = $image_url;
        }
    }

    if (empty($message)) {
        $update_sql = "UPDATE products SET name=?, price=?, gst=?, description=?, image=?, stock=? WHERE id=?"; // Removed category_id from SQL
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sddssii", $name, $price, $gst, $description, $image_path, $stock, $id); // Removed category_id from bind

        if ($stmt->execute()) {
            $product['name'] = $name;
            $product['price'] = $price;
            $product['gst'] = $gst;
            $product['description'] = $description;
            $product['image'] = $image_path;
            // $product['category_id'] = $category_id; // Update in object not needed
            $product['stock'] = $stock;
            
            $message = "Product updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating product: " . $conn->error;
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
    <title>Edit Product - Organic Store</title>
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e6ed;
            border-radius: var(--border-radius);
            font-size: 1rem;
            background-color: #fff;
        }

        .btn {
            background: linear-gradient(to right, var(--accent), #6fa352);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .btn-secondary {
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .current-image {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-leaf"></i><span>Organic Store</span></div>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="view_products.php" class="menu-item active"><i class="fas fa-box"></i><span>Products</span></a>
            <a href="view_orders.php" class="menu-item"><i class="fas fa-shopping-cart"></i><span>Orders</span></a>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <a href="manage_users.php" class="menu-item"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
            <h1><i class="fas fa-edit"></i> Edit Product</h1>
            <div class="header-actions">
                <a href="view_products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
        </div>

        <div class="form-container">
            
            <?php if($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" value="<?php echo intval($product['stock']); ?>" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo floatval($product['price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>GST (%)</label>
                        <input type="number" step="0.01" name="gst" class="form-control" value="<?php echo isset($product['gst']) ? floatval($product['gst']) : '0'; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <div style="margin-top: 10px;">
                        <label style="font-size: 0.9em; color: #666;">Or Image URL:</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://example.com/image.jpg" 
                               value="<?php echo filter_var($product['image'], FILTER_VALIDATE_URL) ? htmlspecialchars($product['image']) : ''; ?>">
                    </div>
                    <?php if($product['image']): ?>
                        <div style="margin-top: 10px;">
                            <p style="font-size: 0.9em; margin-bottom: 5px;">Current Image:</p>
                            <?php 
                            if (filter_var($product['image'], FILTER_VALIDATE_URL)) {
                                $img_src = $product['image'];
                            } elseif (file_exists("../assets/images/products/" . $product['image'])) {
                                $img_src = "../assets/images/products/" . $product['image'];
                            } else {
                                $img_src = "https://placehold.co/100x100?text=No+Image";
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                 class="current-image" 
                                 onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Broken+Image';">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group" style="display: flex; gap: 15px;">
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Update Product</button>
                    <a href="view_products.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Image Preview Logic
        const urlInput = document.querySelector('input[name="image_url"]');
        const fileInput = document.querySelector('input[name="image"]');
        
        // Helper to update preview
        function updatePreview(src) {
            let currentImage = document.querySelector('.current-image');
            if (currentImage) {
                currentImage.src = src;
            }
        }

        if(urlInput) {
            urlInput.addEventListener('input', function() {
                const url = this.value.trim();
                if (url) {
                    updatePreview(url);
                }
            });
        }

        if(fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        updatePreview(e.target.result);
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }
    </script>
</body>
</html>