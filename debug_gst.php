<?php
include 'config/db.php';

$result = $conn->query("SELECT id, name, price, gst FROM products LIMIT 5");
if ($result) {
    echo "Query Successful.<br>";
    while ($row = $result->fetch_assoc()) {
        echo "Product: " . $row['name'] . " | Price: " . $row['price'] . " | GST: " . var_export($row['gst'], true) . "<br>";
    }
} else {
    echo "Query Failed: " . $conn->error;
}
?>