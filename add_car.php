<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'seller' && $_SESSION['user_type'] != 'admin')) {
    $_SESSION['error'] = "You must be a seller or admin to add a car.";
    header("Location: index.php");
    exit();
}

if ($_SESSION['user_type'] == 'seller' && !$_SESSION['is_approved'] && $_SESSION['seller_level'] != 'first_time') {
    $_SESSION['error'] = "Your seller account is pending approval.";
    header("Location: index.php");
    exit();
}

if ($_SESSION['user_type'] == 'seller' && $_SESSION['seller_level'] == 'first_time') {
    $stmt = $conn->prepare("SELECT COUNT(*) as car_count FROM cars WHERE seller_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['car_count'] >= 3) {
        $_SESSION['error'] = "You have reached the limit of 3 cars. Please verify your account to add more.";
        header("Location: verify_seller.php");
        exit();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_car'])) {
    try {
        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $stmt = $conn->prepare("INSERT INTO cars (seller_id, model, brand, year, price, km_driven, fuel_type, transmission, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $seller_id = $_SESSION['user_id'];
        $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);
        $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
        $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $km_driven = filter_input(INPUT_POST, 'km_driven', FILTER_SANITIZE_NUMBER_INT);
        $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
        $transmission = filter_input(INPUT_POST, 'transmission', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        $stmt->bind_param("issidssss", $seller_id, $model, $brand, $year, $price, $km_driven, $fuel_type, $transmission, $description);

        if ($stmt->execute()) {
            $car_id = $conn->insert_id;
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['size'][$key] > 0) {
                    $imageFileType = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (!in_array($imageFileType, $allowed_types)) {
                        throw new Exception("Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.");
                    }

                    $new_filename = uniqid() . '.' . $imageFileType;
                    $target_file = $target_dir . $new_filename;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $image_stmt = $conn->prepare("INSERT INTO car_images (car_id, image_path) VALUES (?, ?)");
                        $image_stmt->bind_param("is", $car_id, $target_file);
                        $image_stmt->execute();
                        $image_stmt->close();
                    } else {
                        throw new Exception("Error uploading image file.");
                    }
                }
            }

            $_SESSION['message'] = "Car added successfully!";
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Error adding car: " . $conn->error);
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
    <title>Add Car - CarBazaar</title>
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
                <h2>Add New Car</h2>
                <p>List your car for sale on CarBazaar</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="search-form">
                <div class="form-group">
                    <label for="brand"><i class="fas fa-car"></i> Brand</label>
                    <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Toyota" required>
                </div>
                <div class="form-group">
                    <label for="model"><i class="fas fa-car-side"></i> Model</label>
                    <input type="text" id="model" name="model" class="form-control" placeholder="e.g. Corolla" required>
                </div>
                <div class="form-group">
                    <label for="year"><i class="fas fa-calendar-alt"></i> Year</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" placeholder="e.g. 2020" required>
                </div>
                <div class="form-group">
                    <label for="price"><i class="fas fa-rupee-sign"></i> Price (₹)</label>
                    <input type="number" id="price" name="price" class="form-control" min="0" step="1" placeholder="e.g. 500000" required>
                </div>
                <div class="form-group">
                    <label for="km_driven"><i class="fas fa-tachometer-alt"></i> Kilometers Driven</label>
                    <input type="number" id="km_driven" name="km_driven" class="form-control" min="0" placeholder="e.g. 25000" required>
                </div>
                <div class="form-group">
                    <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control" required>
                        <option value="">Select Fuel Type</option>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="CNG">CNG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transmission"><i class="fas fa-cog"></i> Transmission</label>
                    <select id="transmission" name="transmission" class="form-control" required>
                        <option value="">Select Transmission</option>
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="images"><i class="fas fa-image"></i> Car Images (Multiple)</label>
                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple required>
                </div>
                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Add details about the car's condition, features, etc." required></textarea>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" name="add_car" class="btn btn-primary"><i class="fas fa-plus"></i> Add Car</button>
                    <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
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
