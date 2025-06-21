<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'seller') {
    $_SESSION['error'] = "You must be a seller to access this page.";
    header("Location: index.php");
    exit();
}

if ($_SESSION['seller_level'] == 'verified') {
    $_SESSION['error'] = "Your account is already verified.";
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_seller'])) {
    try {
        $aadhar_number = filter_input(INPUT_POST, 'aadhar_number', FILTER_SANITIZE_STRING);
        if (!preg_match('/^\d{12}$/', $aadhar_number)) {
            throw new Exception("Invalid Aadhar number. It must be 12 digits.");
        }

        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        if (!empty($_FILES['aadhar_image']['tmp_name'])) {
            $imageFileType = strtolower(pathinfo($_FILES['aadhar_image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($imageFileType, $allowed_types)) {
                throw new Exception("Invalid image format. Only JPG, JPEG, and PNG are allowed.");
            }

            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;

            if (!move_uploaded_file($_FILES['aadhar_image']['tmp_name'], $target_file)) {
                throw new Exception("Error uploading Aadhar image.");
            }
        } else {
            throw new Exception("Aadhar image is required.");
        }

        $stmt = $conn->prepare("UPDATE users SET aadhar_number = ?, aadhar_image = ?, seller_level = 'pending', is_approved = FALSE WHERE id = ?");
        $stmt->bind_param("ssi", $aadhar_number, $target_file, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Verification request submitted. Awaiting admin approval.";
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Error submitting verification: " . $conn->error);
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
    <title>Verify Seller - CarBazaar</title>
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
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                    <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                    <?php if ($_SESSION['user_type'] == 'admin'): ?>
                        <li><a href="admin_dashboard.php"><i class="fas fa-user-shield"></i> Admin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">
                    Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <?php if ($_SESSION['seller_level'] == 'verified'): ?>
                        <span class="verified-badge">Verified Seller</span>
                    <?php endif; ?>
                </div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="search-section">
            <div class="search-title">
                <h2>Verify Seller Account</h2>
                <p>Submit your Aadhar details to become a verified seller</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="search-form">
                <div class="form-group">
                    <label for="aadhar_number"><i class="fas fa-id-card"></i> Aadhar Number</label>
                    <input type="text" id="aadhar_number" name="aadhar_number" class="form-control" placeholder="Enter 12-digit Aadhar number" maxlength="12" required>
                </div>
                <div class="form-group">
                    <label for="aadhar_image"><i class="fas fa-image"></i> Aadhar Image</label>
                    <input type="file" id="aadhar_image" name="aadhar_image" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" name="verify_seller" class="btn btn-primary"><i class="fas fa-shield-alt"></i> Submit Verification</button>
                    <a href="profile.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
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
</body>
</html>
