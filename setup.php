<?php
$host = 'localhost';
$user = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $user, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/database/organic_store.sql');

if ($conn->multi_query($sql)) {
    echo "<h1>Setup Complete!</h1>";
    echo "<p>Database 'organic_store' created and tables imported successfully.</p>";
    echo "<p>Admin: admin@organic.com / admin123</p>";
    echo "<p>User: john@gmail.com / password123</p>";
    echo "<p><a href='index.php'>Go to Website</a></p>";
    
    // Clear out results to avoid sync errors
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    echo "Error setting up database: " . $conn->error;
}

$conn->close();
?>
