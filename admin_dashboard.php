<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    $_SESSION['error'] = "You must be an admin to access this page.";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_seller'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("UPDATE users SET seller_level = 'verified', is_approved = TRUE WHERE id = ? AND user_type = 'seller'");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Seller approved successfully!";
    } else {
        $_SESSION['error'] = "Error approving seller: " . $conn->error;
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deny_seller'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("UPDATE users SET seller_level = 'first_time', is_approved = FALSE WHERE id = ? AND user_type = 'seller'");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Seller verification denied.";
    } else {
        $_SESSION['error'] = "Error denying seller: " . $conn->error;
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

$pending_sellers = $conn->query("SELECT * FROM users WHERE user_type = 'seller' AND seller_level = 'pending' AND is_approved = FALSE");
$all_users = $conn->query("SELECT * FROM users");
$all_cars = $conn->query("SELECT cars.*, users.username AS seller_name FROM cars JOIN users ON cars.seller_id = users.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarBazaar</title>
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
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Admin Dashboard</h2>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <section>
            <h3>Pending Seller Verifications</h3>
            <div class="cars-grid">
                <?php if ($pending_sellers->num_rows > 0): ?>
                    <?php while ($seller = $pending_sellers->fetch_assoc()): ?>
                        <div class="car-card">
                            <div class="car-details">
                                <h3 class="car-title"><?php echo htmlspecialchars($seller['username']); ?></h3>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($seller['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($seller['phone']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($seller['location']); ?></p>
                                <p><strong>Aadhar Number:</strong> <?php echo htmlspecialchars($seller['aadhar_number']); ?></p>
                                <p><strong>Aadhar Image:</strong> <a href="<?php echo htmlspecialchars($seller['aadhar_image']); ?>" target="_blank">View</a></p>
                                <div class="car-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                        <button type="submit" name="approve_seller" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                        <button type="submit" name="deny_seller" class="btn btn-danger"><i class="fas fa-times"></i> Deny</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No pending verifications.</p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h3>All Users</h3>
            <div class="cars-grid">
                <?php while ($user = $all_users->fetch_assoc()): ?>
                    <div class="car-card">
                        <div class="car-details">
                            <h3 class="car-title"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($user['user_type']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Seller Level:</strong> <?php echo htmlspecialchars($user['seller_level'] ?? 'N/A'); ?></p>
                            <p><strong>Approved:</strong> <?php echo $user['is_approved'] ? 'Yes' : 'No'; ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section>
            <h3>All Cars</h3>
            <div class="cars-grid">
                <?php while ($car = $all_cars->fetch_assoc()): ?>
                    <div class="car-card">
                        <div class="car-details">
                            <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                            <p><strong>Seller:</strong> <?php echo htmlspecialchars($car['seller_name']); ?></p>
                            <p><strong>Price:</strong> ₹<?php echo number_format($car['price']); ?></p>
                            <p><strong>Status:</strong> <?php echo $car['is_sold'] ? 'Sold' : 'Available'; ?></p>
                            <div class="car-actions">
                                <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
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
