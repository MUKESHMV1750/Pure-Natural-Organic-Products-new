<?php
include 'config/db.php';
$result = $conn->query("SELECT id, name, image FROM products ORDER BY id DESC LIMIT 10");
echo "<pre>";
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Image: " . $row['image'] . "\n";
    echo "Is URL: " . (filter_var($row['image'], FILTER_VALIDATE_URL) ? 'Yes' : 'No') . "\n";
    
    $path = 'assets/images/products/' . $row['image'];
    echo "Local Path Check ($path): " . (file_exists($path) ? 'Exists' : 'Missing') . "\n";
    
    $path_admin = '../assets/images/products/' . $row['image']; // Relative to admin/
    // We are running this script from root so we don't need ../ but checking logic
    
    echo "-------------------\n";
}
echo "</pre>";
?>