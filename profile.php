<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to view this page.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);

        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, location = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $email, $phone, $location, $user_id);

        if ($stmt->execute()) {
            if (!empty($_FILES['profile_image']['tmp_name'])) {
                $target_dir = "Uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $imageFileType = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($imageFileType, $allowed_types)) {
                    throw new Exception("Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.");
                }

                $new_filename = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $image_stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $image_stmt->bind_param("si", $target_file, $user_id);
                    $image_stmt->execute();
                    $image_stmt->close();
                } else {
                    throw new Exception("Error uploading profile image.");
                }
            }

            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Error updating profile: " . $conn->error);
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
    <title>Profile - CarBazaar</title>
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
        <div class="section-header">
            <h2 class="section-title">My Profile</h2>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="search-section">
            <form method="POST" enctype="multipart/form-data" class="search-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location']); ?>">
                </div>
                <div class="form-group">
                    <label for="profile_image"><i class="fas fa-image"></i> Profile Image</label>
                    <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*">
                    <?php if ($user['profile_image']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" style="width: 100px; margin-top: 10px;">
                    <?php endif; ?>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                </div>
            </form>

            <?php if ($user['user_type'] == 'seller' && $user['seller_level'] != 'verified'): ?>
                <div class="section-header">
                    <a href="verify_seller.php" class="btn btn-primary"><i class="fas fa-shield-alt"></i> Verify Seller Account</a>
                </div>
            <?php endif; ?>
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
