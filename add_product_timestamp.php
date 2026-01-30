<?php
// add_product_timestamp.php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'organic_store';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add created_at column to products if it doesn't exist
$sql = "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";

if ($conn->query($sql) === TRUE) {
    echo "Column 'created_at' added successfully to 'products' table.";
} else {
    echo "Error evaluating SQL: " . $conn->error;
}

$conn->close();
?>