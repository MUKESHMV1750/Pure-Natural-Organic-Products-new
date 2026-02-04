<?php
include 'config/db.php';

$sql = "SELECT id, image FROM products WHERE image LIKE 'products/%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " products with incorrect image paths.<br>";
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $old_image = $row['image'];
        $new_image = str_replace('products/', '', $old_image);
        
        $update_sql = "UPDATE products SET image = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_image, $id);
        
        if ($stmt->execute()) {
            echo "Fixed Product ID: $id. Changed '$old_image' to '$new_image'<br>";
        } else {
            echo "Failed to fix Product ID: $id. Error: " . $conn->error . "<br>";
        }
    }
} else {
    echo "No products found with incorrect image paths.";
}
?>