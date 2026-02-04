<?php
include 'config/db.php';

$alter_queries = [
    "ALTER TABLE users ADD COLUMN phone VARCHAR(20)",
    "ALTER TABLE users ADD COLUMN house_no VARCHAR(100)",
    "ALTER TABLE users ADD COLUMN street_address TEXT",
    "ALTER TABLE users ADD COLUMN city VARCHAR(100)",
    "ALTER TABLE users ADD COLUMN district VARCHAR(100)",
    "ALTER TABLE users ADD COLUMN pin_code VARCHAR(10)"
];

echo "Updating users table...<br>";

foreach ($alter_queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Column added successfully: " . htmlspecialchars($sql) . "<br>";
    } else {
        echo "Error (or column exists): " . htmlspecialchars($conn->error) . "<br>";
    }
}

echo "Database update process finished.";
?>