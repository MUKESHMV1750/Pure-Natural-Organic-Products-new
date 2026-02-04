<?php
session_start();
include '../config/db.php';

if(isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, name, password, role FROM users WHERE email = ? AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if ($password === $row['password']) { 
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login - Organic Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f0f0f0;">
    <div class="container" style="max-width:400px; margin-top:100px;">
        <h2 style="text-align:center;">Admin Portal</h2>
        <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Admin Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn" style="width:100%;">Login</button>
        </form>
        <p style="text-align:center;"><a href="../index.php">Back to Site</a></p>
    </div>
</body>
</html>
