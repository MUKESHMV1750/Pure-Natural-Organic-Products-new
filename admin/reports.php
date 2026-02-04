<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../config/db.php';

// Default Date Range: This Month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Basic Reporting Query
$sales_query = "SELECT 
                    COUNT(id) as total_orders, 
                    SUM(total_amount) as total_revenue 
                FROM orders 
                WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date'";

// Try executing to catch errors if table/columns don't exist exactly as guessed
$sales_data = ['total_orders' => 0, 'total_revenue' => 0];
try {
    $sales_result = $conn->query($sales_query);
    if($sales_result && $sales_result->num_rows > 0) {
        $sales_data = $sales_result->fetch_assoc();
    }
} catch (Exception $e) {
    // Table might not exist or columns differ
}

// Fetch Recent Orders for the period
$orders_query = "SELECT id, user_id, total_amount, status, order_date 
                 FROM orders 
                 WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' 
                 ORDER BY order_date DESC";
$orders_result = false;
try {
    $orders_result = $conn->query($orders_query);
} catch (Exception $e) {
    // 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Organic Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            background-color: #f4f7f6;
            min-height: 100vh;
        }
        
        /* Validated Sidebar Styles from edit_product.php */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
            position: fixed;
            height: 100%;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: #34495e;
            text-align: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2ecc71;
            margin-bottom: 10px;
        }
        
        .admin-info {
            font-size: 0.9rem;
            color: #bdc3c7;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            flex: 1;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: #34495e;
            color: #ecf0f1;
            border-left-color: #2ecc71;
        }
        
        .menu-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
            width: calc(100% - 250px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
        }

        /* Report Specific Styles */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-info h3 {
            font-size: 0.9rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .card-info .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background-color: #e8f8f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #2ecc71;
        }

        .card.revenue .card-icon {
            background-color: #ebf5fb;
            color: #3498db;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.9rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #27ae60;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th, .report-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-completed { background-color: #e8f8f5; color: #2ecc71; }
        .status-pending { background-color: #fef9e7; color: #f1c40f; }
        .status-cancelled { background-color: #fdedec; color: #e74c3c; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; cursor: pointer; font-size: 1.5rem; }
            .header-actions { display: none; }
        }
        
        .menu-toggle { display: none; }
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
            <a href="view_products.php" class="menu-item"><i class="fas fa-box"></i><span>Products</span></a>
            <a href="view_orders.php" class="menu-item"><i class="fas fa-shopping-cart"></i><span>Orders</span></a>
            <a href="reports.php" class="menu-item active"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <a href="manage_users.php" class="menu-item"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
            <h1><i class="fas fa-chart-line"></i> Sales Reports</h1>
            <div class="header-actions">
                <a href="#" onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print Report</a>
            </div>
        </div>

        <form class="filters">
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn">Apply Filter</button>
            </div>
        </form>

        <div class="summary-cards">
            <div class="card">
                <div class="card-info">
                    <h3>Total Orders</h3>
                    <div class="number"><?php echo number_format($sales_data['total_orders'] ?? 0); ?></div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
            <div class="card revenue">
                <div class="card-info">
                    <h3>Total Revenue</h3>
                    <div class="number">₹<?php echo number_format($sales_data['total_revenue'] ?? 0, 2); ?></div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h3>Detailed Transactions</h3>
            <br>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>User ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                        <?php while($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo $order['user_id']; ?></td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No records found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        if(menuToggle){
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
