<?php
session_start();
include 'config/db.php';

if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    // Ideally hash password: $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $checkEmail = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            $success = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Organic Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9fff5;
            color: #2d5016;
            line-height: 1.6;
        }

        header {
            background-color: #ffffff;
            box-shadow: 0 2px 15px rgba(76, 131, 52, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4c8334;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav ul li a {
            text-decoration: none;
            color: #3a6627;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #e8f5e0;
            color: #2d5016;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 5%;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: calc(100vh - 120px);
        }

        .register-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 2rem 0;
        }

        .register-card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(76, 131, 52, 0.15);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            border-top: 5px solid #4c8334;
            position: relative;
            overflow: hidden;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #4c8334, #8bc34a, #4c8334);
            background-size: 200% 100%;
            animation: shimmer 3s infinite linear;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            color: #3a6627;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: #6b8c5d;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #3a6627;
            font-weight: 600;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8bc34a;
            font-size: 1.2rem;
        }

        .input-with-icon input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0f0d6;
            border-radius: 10px;
            font-size: 1rem;
            color: #2d5016;
            background-color: #f9fff5;
            transition: all 0.3s ease;
        }

        .input-with-icon input:focus {
            outline: none;
            border-color: #4c8334;
            box-shadow: 0 0 0 3px rgba(76, 131, 52, 0.2);
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .strength-meter {
            height: 5px;
            flex-grow: 1;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-text {
            font-weight: 600;
            min-width: 80px;
        }

        .btn {
            background: linear-gradient(to right, #4c8334, #6fa352);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .btn:hover {
            background: linear-gradient(to right, #3a6627, #4c8334);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 131, 52, 0.3);
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #c62828;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 1.2rem;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #4caf50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message i {
            font-size: 1.2rem;
        }

        .success-message a {
            color: #3a6627;
            font-weight: 600;
            text-decoration: none;
        }

        .success-message a:hover {
            text-decoration: underline;
        }

        .register-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6b8c5d;
        }

        .register-footer a {
            color: #4c8334;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-footer a:hover {
            color: #3a6627;
            text-decoration: underline;
        }

        .organic-decoration {
            position: absolute;
            opacity: 0.1;
            z-index: -1;
        }

        .leaf-1 {
            top: 10%;
            left: 5%;
            font-size: 8rem;
            color: #4c8334;
            transform: rotate(45deg);
        }

        .leaf-2 {
            bottom: 10%;
            right: 5%;
            font-size: 6rem;
            color: #8bc34a;
            transform: rotate(-20deg);
        }

        .leaf-3 {
            top: 40%;
            right: 10%;
            font-size: 5rem;
            color: #6fa352;
            transform: rotate(15deg);
        }

        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 1.5rem;
            padding: 10px;
            background-color: #f9fff5;
            border-radius: 8px;
        }

        .terms-group input[type="checkbox"] {
            margin-top: 3px;
            accent-color: #4c8334;
        }

        .terms-group label {
            font-size: 0.9rem;
            color: #6b8c5d;
            font-weight: normal;
        }

        .terms-group a {
            color: #4c8334;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .register-card {
                padding: 2rem;
                margin: 1rem;
            }
            
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            nav ul {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .logo {
                gap: 1rem;
            }
            
            .logo {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 1.5rem;
            }
            
            .register-header h2 {
                font-size: 1.8rem;
            }
            
            .btn {
                padding: 12px 20px;
            }
            
            .terms-group {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Organic Store
            </div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <div class="register-wrapper">
            <div class="register-card">
                <div class="organic-decoration leaf-1">
                    <i class="fas fa-leaf"></i>
                </div>
                
                <div class="organic-decoration leaf-2">
                    <i class="fas fa-seedling"></i>
                </div>
                
                <div class="organic-decoration leaf-3">
                    <i class="fas fa-spa"></i>
                </div>
                
                <div class="register-header">
                    <h2>Join Our Community</h2>
                    <p>Create an account to start your organic shopping journey</p>
                </div>
                
                <?php if($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                        </div>
                        <div class="password-strength">
                            <span class="strength-text" id="strengthText">Strength</span>
                            <div class="strength-meter">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password" required>
                        </div>
                        <small id="passwordMatch" style="color: #c62828; display: none;">
                            <i class="fas fa-exclamation-circle"></i> Passwords do not match
                        </small>
                    </div>
                    
                    <div class="terms-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn" id="submitBtn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <div class="register-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const registerForm = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const termsCheckbox = document.getElementById('terms');
            
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check password length
                if (password.length >= 8) strength += 25;
                if (password.length >= 12) strength += 15;
                
                // Check for lowercase letters
                if (/[a-z]/.test(password)) strength += 15;
                
                // Check for uppercase letters
                if (/[A-Z]/.test(password)) strength += 15;
                
                // Check for numbers
                if (/[0-9]/.test(password)) strength += 15;
                
                // Check for special characters
                if (/[^A-Za-z0-9]/.test(password)) strength += 15;
                
                // Update strength meter
                strengthFill.style.width = `${strength}%`;
                
                // Update strength text and color
                if (strength < 30) {
                    strengthFill.style.backgroundColor = '#f44336';
                    strengthText.textContent = 'Weak';
                } else if (strength < 70) {
                    strengthFill.style.backgroundColor = '#ff9800';
                    strengthText.textContent = 'Fair';
                } else if (strength < 90) {
                    strengthFill.style.backgroundColor = '#8bc34a';
                    strengthText.textContent = 'Good';
                } else {
                    strengthFill.style.backgroundColor = '#4caf50';
                    strengthText.textContent = 'Strong';
                }
                
                // Check password match
                checkPasswordMatch();
            });
            
            // Password confirmation checker
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    passwordMatch.style.display = 'block';
                    confirmPasswordInput.style.borderColor = '#f44336';
                    return false;
                } else {
                    passwordMatch.style.display = 'none';
                    if (confirmPassword.length > 0) {
                        confirmPasswordInput.style.borderColor = '#4c8334';
                    } else {
                        confirmPasswordInput.style.borderColor = '#e0f0d6';
                    }
                    return true;
                }
            }
            
            // Form validation before submission
            registerForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Check password match
                if (!checkPasswordMatch()) {
                    isValid = false;
                    e.preventDefault();
                }
                
                // Check terms agreement
                if (!termsCheckbox.checked) {
                    isValid = false;
                    alert('Please agree to the Terms of Service and Privacy Policy');
                    e.preventDefault();
                }
                
                // If valid, show loading state
                if (isValid) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                    submitBtn.disabled = true;
                }
            });
            
            // Add focus effect to inputs
            const inputs = document.querySelectorAll('.input-with-icon input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if(this.value === '') {
                        this.parentElement.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>
</html>