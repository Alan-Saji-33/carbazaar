<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to view favorites.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT cars.*, users.username AS seller_name, car_images.image_path 
                        FROM favorites 
                        JOIN cars ON favorites.car_id = cars.id 
                        JOIN users ON cars.seller_id = users.id 
                        LEFT JOIN car_images ON cars.id = car_images.car_id 
                        WHERE favorites.user_id = ? 
                        GROUP BY cars.id");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites_result = $stmt->get_result();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_favorite'])) {
    $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $user_id, $car_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car removed from favorites!";
    } else {
        $_SESSION['error'] = "Error removing favorite: " . $conn->error;
    }
    $stmt->close();
    header("Location: favorites.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorites - CarBazaar</title>
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
            <h2 class="section-title">My Favorites</h2>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <section>
            <div class="cars-grid">
                <?php if ($favorites_result->num_rows > 0): ?>
                    <?php while ($car = $favorites_result->fetch_assoc()): ?>
                        <div class="car-card">
                            <?php if ($car['is_sold']): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php else: ?>
                                <div class="car-badge">NEW</div>
                            <?php endif; ?>
                            <div class="car-image">
                                <img src="<?php echo htmlspecialchars($car['image_path'] ?? 'Uploads/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                            </div>
                            <div class="car-details">
                                <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
                                <div class="car-specs">
                                    <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                    <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['km_driven']); ?> km</span>
                                    <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                    <span class="car-spec"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                </div>
                                <div class="car-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="remove_favorite" class="favorite-btn active">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                    <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No favorite cars found. Start adding cars to your favorites!</p>
                <?php endif; ?>
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
