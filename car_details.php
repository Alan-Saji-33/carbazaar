<?php
require_once 'functions.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid car ID.";
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("SELECT cars.*, users.username AS seller_name, users.phone AS seller_phone, users.email AS seller_email, users.profile_image, users.location, users.seller_level 
                        FROM cars 
                        JOIN users ON cars.seller_id = users.id 
                        WHERE cars.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$car_result = $stmt->get_result();

if ($car_result->num_rows == 0) {
    $_SESSION['error'] = "Car not found.";
    header("Location: index.php");
    exit();
}

$car = $car_result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT image_path FROM car_images WHERE car_id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$images_result = $stmt->get_result();
$images = [];
while ($row = $images_result->fetch_assoc()) {
    $images[] = $row['image_path'];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_car']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')) {
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car deleted successfully!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Error deleting car: " . $conn->error;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_sold']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')) {
    $stmt = $conn->prepare("UPDATE cars SET is_sold = TRUE WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car marked as sold!";
        header("Location: car_details.php?id=$car_id");
        exit();
    } else {
        $_SESSION['error'] = "Error marking car as sold: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?> - CarBazaar</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                        <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                        <?php if ($_SESSION['user_type'] == 'admin'): ?>
                            <li><a href="admin_dashboard.php"><i class="fas fa-user-shield"></i> Admin</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-greeting">
                        Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <?php if ($_SESSION['user_type'] == 'seller' && $_SESSION['seller_level'] == 'verified'): ?>
                            <span class="verified-badge">Verified Seller</span>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <section>
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h2>
            </div>

            <div class="car-details-container">
                <div class="car-details-image">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $image): ?>
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <img src="Uploads/placeholder.jpg" alt="No image available">
                    <?php endif; ?>
                </div>
                <div class="car-details-info">
                    <h2 class="car-details-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h2>
                    <div class="car-details-price">₹<?php echo number_format($car['price']); ?></div>
                    <div class="car-details-specs">
                        <div class="car-details-spec">
                            <div class="car-details-spec-label">Year:</div>
                            <div class="car-details-spec-value"><?php echo htmlspecialchars($car['year']); ?></div>
                        </div>
                        <div class="car-details-spec">
                            <div class="car-details-spec-label">Kilometers:</div>
                            <div class="car-details-spec-value"><?php echo number_format($car['km_driven']); ?> km</div>
                        </div>
                        <div class="car-details-spec">
                            <div class="car-details-spec-label">Fuel Type:</div>
                            <div class="car-details-spec-value"><?php echo htmlspecialchars($car['fuel_type']); ?></div>
                        </div>
                        <div class="car-details-spec">
                            <div class="car-details-spec-label">Transmission:</div>
                            <div class="car-details-spec-value"><?php echo htmlspecialchars($car['transmission']); ?></div>
                        </div>
                        <div class="car-details-spec">
                            <div class="car-details-spec-label">Status:</div>
                            <div class="car-details-spec-value"><?php echo $car['is_sold'] ? '<span style="color:var(--danger)">Sold</span>' : '<span style="color:var(--success)">Available</span>'; ?></div>
                        </div>
                    </div>
                    <div class="car-details-description">
                        <h4>Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="seller-info">
                <h4>Seller Information</h4>
                <img src="<?php echo htmlspecialchars($car['profile_image'] ?? 'Uploads/default_profile.jpg'); ?>" alt="Seller Profile">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($car['seller_name']); ?>
                    <?php if ($car['seller_level'] == 'verified'): ?>
                        <span class="verified-badge">Verified Seller</span>
                    <?php endif; ?>
                </p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($car['location'] ?? 'Not provided'); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($car['seller_phone'] ?? 'Not provided'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($car['seller_email']); ?></p>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] != 'seller'): ?>
                    <a href="messages.php?car_id=<?php echo $car['id']; ?>&receiver_id=<?php echo $car['seller_id']; ?>" class="btn btn-primary"><i class="fas fa-envelope"></i> Message Seller</a>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $car['seller_id'] || $_SESSION['user_type'] == 'admin')): ?>
                <div class="section-header">
                    <div class="form-actions">
                        <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Details</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <button type="submit" name="delete_car" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                        <?php if (!$car['is_sold']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                <button type="submit" name="mark_sold" class="btn btn-success"><i class="fas fa-check-circle"></i> Mark as Sold</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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
