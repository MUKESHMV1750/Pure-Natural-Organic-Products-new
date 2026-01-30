<?php 
session_start();
include 'config/db.php';

// Handle contact form submission
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message_text)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        // Save to database (if you have a contacts table)
        // $sql = "INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
        // $stmt = $conn->prepare($sql);
        // $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message_text);
        
        // if ($stmt->execute()) {
            // For now, just show success message
            $message = "Thank you for contacting us! We'll get back to you within 24 hours.";
            $message_type = "success";
            
            // Clear form
            $_POST = [];
        // } else {
        //     $message = "Sorry, there was an error sending your message. Please try again.";
        //     $message_type = "error";
        // }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

// Get store info
$store_info = [
    'name' => 'Organic Store',
    'address' => '123 Green Street, Eco City, EC 12345',
    'phone' => '+1 (555) 123-4567',
    'email' => 'info@organicstore.com',
    'hours' => 'Monday - Friday: 9:00 AM - 8:00 PM<br>Saturday: 10:00 AM - 6:00 PM<br>Sunday: 11:00 AM - 5:00 PM'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Organic Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4c8334;
            --primary-light: #8bc34a;
            --secondary: #3a6627;
            --dark: #2d5016;
            --light: #f9fff5;
            --gray: #6b8c5d;
            --gray-light: #e8f5e0;
            --danger: #e74c3c;
            --success: #27ae60;
            --border-radius: 12px;
            --box-shadow: 0 5px 20px rgba(76, 131, 52, 0.1);
            --transition: all 0.3s ease;
        }

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
            transition: var(--transition);
        }

        nav ul li a:hover {
            background-color: #e8f5e0;
            color: #2d5016;
        }

        .cart-icon {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 5%;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 0;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="%23e8f5e0" d="M0,0 L100,0 L100,100 L0,100 Z"/><circle cx="20" cy="20" r="10" fill="%234c8334" opacity="0.1"/><circle cx="80" cy="30" r="15" fill="%234c8334" opacity="0.1"/><circle cx="40" cy="70" r="12" fill="%234c8334" opacity="0.1"/></svg>');
            border-radius: var(--border-radius);
        }

        .page-header h1 {
            font-size: 2.8rem;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header p {
            color: var(--gray);
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Contact Layout */
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-bottom: 4rem;
        }

        @media (max-width: 992px) {
            .contact-layout {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
        }

        /* Contact Info */
        .contact-info {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--box-shadow);
        }

        .contact-info h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-info h2 i {
            color: var(--primary);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 1.8rem;
            padding-bottom: 1.8rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
            flex-shrink: 0;
        }

        .info-content h3 {
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .info-content p {
            color: var(--gray);
            line-height: 1.6;
        }

        .info-content a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .info-content a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Business Hours */
        .business-hours {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .business-hours h3 {
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .business-hours h3 i {
            color: var(--primary);
        }

        .hours-list {
            list-style: none;
        }

        .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed var(--gray-light);
        }

        .hours-list li:last-child {
            border-bottom: none;
        }

        .day {
            font-weight: 600;
            color: var(--dark);
        }

        .time {
            color: var(--gray);
        }

        /* Contact Form */
        .contact-form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--box-shadow);
        }

        .contact-form-container h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-form-container h2 i {
            color: var(--primary);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group.required label::after {
            content: " *";
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(76, 131, 52, 0.2);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
            line-height: 1.6;
        }

        /* Message Display */
        .message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s ease;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid var(--success);
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid var(--danger);
        }

        /* Buttons */
        .btn {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 131, 52, 0.3);
        }

        .btn-full {
            width: 100%;
        }

        /* Map Section */
        .map-section {
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 2px solid var(--gray-light);
        }

        .map-section h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .map-section h2 i {
            color: var(--primary);
        }

        .map-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            height: 400px;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1.2rem;
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 2px solid var(--gray-light);
        }

        .faq-section h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .faq-section h2 i {
            color: var(--primary);
        }

        .faq-list {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .faq-item:hover {
            box-shadow: 0 8px 25px rgba(76, 131, 52, 0.15);
        }

        .faq-question {
            padding: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--dark);
            background: var(--light);
        }

        .faq-question i {
            color: var(--primary);
            transition: var(--transition);
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding: 1.5rem;
            max-height: 500px;
        }

        .faq-answer p {
            color: var(--gray);
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            nav ul {
                gap: 1rem;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 2.2rem;
                flex-direction: column;
                gap: 10px;
            }
            
            .contact-info, .contact-form-container {
                padding: 1.5rem;
            }
            
            .info-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .info-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }
            
            .page-header {
                padding: 2rem 1rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-header p {
                font-size: 1rem;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .contact-layout, .map-section, .faq-section {
            animation: fadeIn 0.5s ease;
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
                <li><a href="shop.php">Shop</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="cart-icon">
                        <a href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-comments"></i>
                Get in Touch
            </h1>
            <p>We're here to help! Contact us with any questions about our organic products or your shopping experience.</p>
        </div>

        <!-- Contact Layout -->
        <div class="contact-layout">
            <!-- Contact Information -->
            <div class="contact-info">
                <h2><i class="fas fa-info-circle"></i> Contact Information</h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Our Location</h3>
                        <p><?php echo htmlspecialchars($store_info['address']); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Phone Number</h3>
                        <p><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $store_info['phone']); ?>">
                            <?php echo htmlspecialchars($store_info['phone']); ?>
                        </a></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Address</h3>
                        <p><a href="mailto:<?php echo htmlspecialchars($store_info['email']); ?>">
                            <?php echo htmlspecialchars($store_info['email']); ?>
                        </a></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="info-content">
                        <h3>Customer Support</h3>
                        <p>Our support team is available to help you with any questions or concerns about our products and services.</p>
                    </div>
                </div>
                
                <!-- Business Hours -->
                <div class="business-hours">
                    <h3><i class="fas fa-clock"></i> Business Hours</h3>
                    <ul class="hours-list">
                        <?php 
                        $hours = explode('<br>', $store_info['hours']);
                        foreach ($hours as $hour): 
                            $parts = explode(':', $hour, 2);
                            if (count($parts) == 2):
                        ?>
                            <li>
                                <span class="day"><?php echo trim($parts[0]); ?></span>
                                <span class="time"><?php echo trim($parts[1]); ?></span>
                            </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-container">
                <h2><i class="fas fa-paper-plane"></i> Send us a Message</h2>
                
                <?php if($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contactForm">
                    <div class="form-group required">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               placeholder="Enter your full name" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group required">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter your email address" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="Enter your phone number"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group required">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" class="form-control" required>
                            <option value="">Select a subject</option>
                            <option value="General Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="Product Questions" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Product Questions') ? 'selected' : ''; ?>>Product Questions</option>
                            <option value="Order Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Order Support') ? 'selected' : ''; ?>>Order Support</option>
                            <option value="Shipping & Delivery" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Shipping & Delivery') ? 'selected' : ''; ?>>Shipping & Delivery</option>
                            <option value="Returns & Refunds" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Returns & Refunds') ? 'selected' : ''; ?>>Returns & Refunds</option>
                            <option value="Wholesale Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Wholesale Inquiry') ? 'selected' : ''; ?>>Wholesale Inquiry</option>
                            <option value="Other" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group required">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" 
                                  placeholder="How can we help you today? Please provide as much detail as possible..."
                                  rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-full">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2><i class="fas fa-map-marked-alt"></i> Find Our Store</h2>
            <div class="map-container">
                <div style="text-align: center;">
                    <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <p><?php echo htmlspecialchars($store_info['address']); ?></p>
                    <p style="font-size: 0.9rem; color: var(--gray); margin-top: 1rem;">
                        <i class="fas fa-info-circle"></i> Interactive map would be embedded here
                    </p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>What makes your products organic?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>All our products are certified organic, meaning they are grown without synthetic pesticides, fertilizers, or genetically modified organisms. We work directly with certified organic farms that follow sustainable agricultural practices.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>What are your delivery options?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer several delivery options including standard shipping (3-5 business days), express shipping (1-2 business days), and local pickup. Shipping costs vary based on your location and order size. Free shipping is available for orders over ₹50.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I return or exchange products?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we offer a 30-day return policy for most products. Items must be in their original packaging and unused. Perishable items have different return conditions. Please contact our support team for return instructions.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Do you offer wholesale pricing?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we offer wholesale pricing for businesses, restaurants, and organizations. Please use the "Wholesale Inquiry" subject in our contact form, and our wholesale team will get back to you with pricing and terms.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I track my order?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Once your order ships, you will receive a confirmation email with a tracking number and link. You can also track your order by logging into your account on our website and visiting the "My Orders" section.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
                
                // Close other items
                document.querySelectorAll('.faq-item').forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
            });
        });

        // Form validation
        const contactForm = document.getElementById('contactForm');
        const submitBtn = contactForm ? contactForm.querySelector('button[type="submit"]') : null;
        
        if (contactForm && submitBtn) {
            contactForm.addEventListener('submit', function(e) {
                // Basic validation
                let isValid = true;
                const requiredFields = this.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = 'var(--danger)';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                } else {
                    // Show loading state
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    submitBtn.disabled = true;
                    
                    // Re-enable after 3 seconds (in case of error)
                    setTimeout(() => {
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
            
            // Remove error styling when user starts typing
            const inputs = contactForm.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.style.borderColor = '';
                });
            });
        }

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = value.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                    this.value = !value[2] ? value[1] : '(' + value[1] + ') ' + value[2] + (value[3] ? '-' + value[3] : '');
                }
            });
        }

        // Auto-hide success message
        setTimeout(() => {
            const message = document.querySelector('.message');
            if (message) {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 500);
            }
        }, 5000);

        // Open first FAQ by default
        document.querySelector('.faq-item')?.classList.add('active');
    </script>
</body>
</html>