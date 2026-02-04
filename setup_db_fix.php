<?php
// setup_db_fix.php
// Run this file once to fix your database tables!

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'organic_store';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br>");
}

echo "<h1>Database Repair Tool</h1>";

// 1. Fix USERS table
$users_cols = [
    "phone" => "VARCHAR(20)",
    "house_no" => "VARCHAR(100)",
    "street_address" => "TEXT",
    "district" => "VARCHAR(100)",
    "pin_code" => "VARCHAR(10)",
    "city" => "VARCHAR(100)"
];

echo "<h3>Updating 'users' table...</h3>";
foreach ($users_cols as $col => $def) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN $col $def";
        if ($conn->query($sql)) {
            echo "Added '$col' column.<br>";
        } else {
            echo "Error adding '$col': " . $conn->error . "<br>";
        }
    } else {
        echo "'$col' already exists.<br>";
    }
}

// 2. Fix ORDERS table
$orders_cols = [
    "shipping_address" => "TEXT",
    "city" => "VARCHAR(100)",
    "state" => "VARCHAR(100)",
    "zip_code" => "VARCHAR(20)",
    "country" => "VARCHAR(100)",
    "phone" => "VARCHAR(20)",
    "payment_method" => "VARCHAR(50)",
    "notes" => "TEXT"
];

echo "<h3>Updating 'orders' table...</h3>";
foreach ($orders_cols as $col => $def) {
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE '$col'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE orders ADD COLUMN $col $def";
        if ($conn->query($sql)) {
            echo "Added '$col' column.<br>";
        } else {
            echo "Error adding '$col': " . $conn->error . "<br>";
        }
    } else {
        echo "'$col' already exists.<br>";
    }
}

echo "<h2>Fix Complete! You can now checkout.</h2>";
echo "<a href='checkout.php'>Go back to Checkout</a>";

$conn->close();
?>