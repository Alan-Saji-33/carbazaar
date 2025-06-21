<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, user_type, location, seller_level, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_type = filter_input(INPUT_POST, 'user_type', FILTER_SANITIZE_STRING);
        $seller_level = $user_type == 'seller' ? 'first_time' : null;
        $is_approved = $user_type == 'seller' ? false : true; // Sellers need admin approval

        if (!$username || !$email || !$password || !$user_type) {
            throw new Exception("Invalid input data.");
        }

        $stmt->bind_param("ssssssss", $username, $password, $email, $phone, $user_type, $location, $seller_level, $is_approved);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful! " . ($user_type == 'seller' ? "Awaiting admin approval." : "Please login.");
            header("Location: login.php");
            exit();
        } else {
            throw new Exception("Registration failed: " . $conn->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon"><i class="fas fa-car"></i></div>
                <div class="logo-text">Car<span>Bazaar</span></div>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
                    <li><a href="index.php#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="index.php#contact"><i class="fas fa-phone-alt"></i> Contact</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="search-section">
            <div class="search-title">
                <h2>Create Your Account</h2>
                <p>Join CarBazaar to buy or sell cars today!</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>

            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="user_type"><i class="fas fa-user"></i> Account Type</label>
                    <select id="user_type" name="user_type" class="form-control" required onchange="toggleForm()">
                        <option value="">Select Type</option>
                        <option value="buyer">Buyer</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>

                <div id="buyer-form" style="display: none;">
                    <div class="form-group">
                        <label for="buyer-username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="buyer-username" name="username" class="form-control" placeholder="Choose a username" required>
                    </div>
                    <div class="form-group">
                        <label for="buyer-email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="buyer-email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="buyer-phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="buyer-phone" name="phone" class="form-control" placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label for="buyer-location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" id="buyer-location" name="location" class="form-control" placeholder="Enter your city">
                    </div>
                    <div class="form-group">
                        <label for="buyer-password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="buyer-password" name="password" class="form-control" placeholder="Create a password" required>
                    </div>
                </div>

                <div id="seller-form" style="display: none;">
                    <div class="form-group">
                        <label for="seller-username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="seller-username" name="username" class="form-control" placeholder="Choose a username" required>
                    </div>
                    <div class="form-group">
                        <label for="seller-email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="seller-email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="seller-phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="seller-phone" name="phone" class="form-control" placeholder="Enter your phone number" required>
                    </div>
                    <div class="form-group">
                        <label for="seller-location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" id="seller-location" name="location" class="form-control" placeholder="Enter your city" required>
                    </div>
                    <div class="form-group">
                        <label for="seller-password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="seller-password" name="password" class="form-control" placeholder="Create a password" required>
                    </div>
                </div>

                <div class="form-group form-actions">
                    <button type="submit" name="register" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
                    <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-column">
                    <h3>CarBazaar</h3>
                    <p>Your trusted platform for buying and selling quality used cars across India.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#cars">Browse Cars</a></li>
                        <li><a href="index.php#about">About Us</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Help & Support</h3>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Shipping Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Street, Mumbai, India</li>
                        <li><i class="fas fa-phone-alt"></i> +91 9876543210</li>
                        <li><i class="fas fa-envelope"></i> info@carbazaar.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> CarBazaar. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleForm() {
            const userType = document.getElementById('user_type').value;
            document.getElementById('buyer-form').style.display = userType === 'buyer' ? 'block' : 'none';
            document.getElementById('seller-form').style.display = userType === 'seller' ? 'block' : 'none';
        }
    </script>
</body>
</html>
