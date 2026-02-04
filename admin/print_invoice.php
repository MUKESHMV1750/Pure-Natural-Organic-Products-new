<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    die("Access denied");
}
include '../config/db.php';

if (!isset($_GET['id'])) {
    die("Order ID missing");
}

$order_id = intval($_GET['id']);

// Fetch Order Details
$sql = "SELECT o.*, u.name as user_name, u.email as user_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = $order_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Order not found");
}

$order = $result->fetch_assoc();

// Fetch Order Items
$items_sql = "SELECT oi.*, p.name as product_name 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #eee;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 2px solid #4c8334;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4c8334;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .box {
            width: 45%;
        }
        .box h3 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-size: 16px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #f8f9fa;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .grand-total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        .print-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #4c8334;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 40px;
            text-align: center;
            text-decoration: none;
        }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .invoice-container { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="logo">Organic Store</div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p>Date: <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
            </div>
        </div>

        <div class="invoice-details">
            <div class="box">
                <h3>Billed To:</h3>
                <p><strong><?php echo htmlspecialchars($order['user_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['user_email']); ?></p>
                <p><?php echo htmlspecialchars($order['phone']); ?></p>
            </div>
            <div class="box">
                <h3>Shipped To:</h3>
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip_code']); ?></p>
                <p><?php echo htmlspecialchars($order['country']); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="totals">
            <?php 
                $subtotal = $order['total_amount'] / 1.08; // Roughly estimating back from total if tax was included
                $tax = $order['total_amount'] - $subtotal;
            ?>
            <div class="totals-row grand-total">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="totals-row" style="color: #666; font-size: 0.9rem; margin-top: 10px;">
                <span>Payment Method:</span>
                <span style="text-transform: uppercase;"><?php echo htmlspecialchars($order['payment_method']); ?></span>
            </div>
        </div>
        
        <div style="clear: both;"></div>

        <div style="margin-top: 50px; text-align: center; font-size: 0.9rem; color: #777;">
            <p>Thank you for your business!</p>
            <p>For any questions, please contact support@organicstore.com</p>
        </div>

        <button onclick="window.print()" class="print-btn">Print Invoice</button>
    </div>
</body>
</html>