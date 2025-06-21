<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'seller' && $_SESSION['user_type'] != 'admin')) {
    $_SESSION['error'] = "You must be a seller or admin to edit a car.";
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid car ID.";
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND (seller_id = ? OR ? = 'admin')");
$stmt->bind_param("iis", $car_id, $_SESSION['user_id'], $_SESSION['user_type']);
$stmt->execute();
$car_result = $stmt->get_result();

if ($car_result->num_rows == 0) {
    $_SESSION['error'] = "Car not found or you don't have permission to edit it.";
    header("Location: index.php");
    exit();
}

$car = $car_result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_car'])) {
    try {
        $stmt = $conn->prepare("UPDATE cars SET model = ?, brand = ?, year = ?, price = ?, km_driven = ?, fuel_type = ?, transmission = ?, description = ? WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);
        $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
        $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $km_driven = filter_input(INPUT_POST, 'km_driven', FILTER_SANITIZE_NUMBER_INT);
        $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
        $transmission = filter_input(INPUT_POST, 'transmission', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        $stmt->bind_param("sssidsssi", $model, $brand, $year, $price, $km_driven, $fuel_type, $transmission, $description, $car_id);

        if ($stmt->execute()) {
            if (!empty($_FILES['images']['tmp_name'][0])) {
                $target_dir = "Uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

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
            }

            $_SESSION['message'] = "Car updated successfully!";
            header("Location: car_details.php?id=$car_id");
            exit();
        } else {
            throw new Exception("Error updating car: " . $conn->error);
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
    <title>Edit Car - CarBazaar</title>
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
                <h2>Edit Car Details</h2>
                <p>Update the details of your car listing</p>
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
                    <input type="text" id="brand" name="brand" class="form-control" value="<?php echo htmlspecialchars($car['brand']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="model"><i class="fas fa-car-side"></i> Model</label>
                    <input type="text" id="model" name="model" class="form-control" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="year"><i class="fas fa-calendar-alt"></i> Year</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($car['year']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price"><i class="fas fa-rupee-sign"></i> Price (₹)</label>
                    <input type="number" id="price" name="price" class="form-control" min="0" step="1" value="<?php echo htmlspecialchars($car['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="km_driven"><i class="fas fa-tachometer-alt"></i> Kilometers Driven</label>
                    <input type="number" id="km_driven" name="km_driven" class="form-control" min="0" value="<?php echo htmlspecialchars($car['km_driven']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control" required>
                        <option value="Petrol" <?php echo $car['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo $car['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo $car['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                        <option value="Hybrid" <?php echo $car['fuel_type'] == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="CNG" <?php echo $car['fuel_type'] == 'CNG' ? 'selected' : ''; ?>>CNG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transmission"><i class="fas fa-cog"></i> Transmission</label>
                    <select id="transmission" name="transmission" class="form-control" required>
                        <option value="Automatic" <?php echo $car['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo $car['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="images"><i class="fas fa-image"></i> Add New Images (Optional)</label>
                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple>
                </div>
                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($car['description']); ?></textarea>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" name="update_car" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="car_details.php?id=<?php echo $car_id; ?>" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
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
