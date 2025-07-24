<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'seller' && $_SESSION['user_type'] !== 'admin')) {
    $_SESSION['error'] = "Please login as a seller or admin to delete a car.";
    header("Location: login.php");
    exit();
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "car_rental_db";

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No car specified.";
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND seller_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("ii", $car_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0 && $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "Car not found or you don't have permission to delete it.";
    header("Location: index.php");
    exit();
}
$car = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $car_id);
        if ($stmt->execute()) {
            // Delete associated images
            foreach (['main_image', 'sub_image1', 'sub_image2', 'sub_image3'] as $img_field) {
                if ($car[$img_field] && file_exists($car[$img_field])) {
                    unlink($car[$img_field]);
                }
            }
            $_SESSION['message'] = "Car deleted successfully!";
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Failed to delete car: " . $stmt->error);
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
    <title>Delete Car - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --dark: #1b263b;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f72585;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 28px;
            color: var(--primary);
            margin-right: 10px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .logo-text span {
            color: var(--primary);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 25px;
        }

        nav ul li a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        nav ul li a i {
            margin-right: 8px;
            font-size: 18px;
        }

        nav ul li a:hover {
            color: var(--primary);
        }

        .user-actions {
            display: flex;
            align-items: center;
        }

        .user-greeting {
            margin-right: 20px;
            font-weight: 500;
            color: var(--dark);
        }

        .user-greeting span {
            color: var(--primary);
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            outline: none;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d1145a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(247, 37, 133, 0.2);
        }

        /* Auth Container Styles */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            margin: 50px auto;
            max-width: 600px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: slideIn 0.5s ease;
        }

        .auth-form-container {
            padding: 40px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 10px;
            position: relative;
        }

        .auth-header h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: var(--primary);
            margin: 10px auto;
        }

        .auth-header p {
            font-size: 16px;
            color: var(--gray);
            margin: 0;
        }

        .car-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .car-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 15px;
            text-align: center;
        }

        .car-main-image {
            width: 100%;
            max-width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid var(--primary);
        }

        .car-price {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .form-group {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .alert-success {
            background-color: var(--success);
            color: white;
        }

        .alert-error {
            background-color: var(--danger);
            color: white;
        }

        /* Footer Styles */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 40px 0;
            margin-top: 40px;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column li {
            margin-bottom: 10px;
        }

        .footer-column a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-column a:hover {
            color: var(--primary);
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .footer-social a {
            color: white;
            font-size: 18px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        /* Animation */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                margin: 20px;
                padding: 15px;
            }

            .auth-form-container {
                padding: 20px;
            }

            .auth-header h2 {
                font-size: 24px;
            }

            .auth-header p {
                font-size: 14px;
            }

            .car-main-image {
                max-width: 250px;
                height: 150px;
            }

            .car-card {
                padding: 15px;
            }
        }
    </style>
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
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="auth-container">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h2>Delete Car</h2>
                    <p>Are you sure you want to delete this car listing?</p>
                </div>
                <div class="car-card">
                    <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                    <img src="<?php echo htmlspecialchars($car['main_image']); ?>" alt="Car Image" class="car-main-image">
                    <div class="car-price">₹<?php echo number_format($car['price'], 0, '', ','); ?></div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-trash"></i> Confirm Delete
                        </button>
                    </div>
                    <div class="form-footer">
                        <p><a href="profile.php">Cancel</a></p>
                    </div>
                </form>
            </div>
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
                        <li><a href="favorites.php">Favorites</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Help & Support</h3>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">How to Sell</a></li>
                        <li><a href="#">Buyer Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Street, Mumbai, Maharashtra, India</li>
                        <li><i class="fas fa-phone-alt"></i> +91 9876543210</li>
                        <li><i class="fas fa-envelope"></i> support@carbazaar.com</li>
                        <li><i class="fas fa-clock"></i> Mon-Fri: 9 AM - 6 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> CarBazaar. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html><?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'seller' && $_SESSION['user_type'] !== 'admin')) {
    $_SESSION['error'] = "Please login as a seller or admin to delete a car.";
    header("Location: login.php");
    exit();
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "car_rental_db";

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No car specified.";
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND seller_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("ii", $car_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0 && $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "Car not found or you don't have permission to delete it.";
    header("Location: index.php");
    exit();
}
$car = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $car_id);
        if ($stmt->execute()) {
            // Delete associated images
            foreach (['main_image', 'sub_image1', 'sub_image2', 'sub_image3'] as $img_field) {
                if ($car[$img_field] && file_exists($car[$img_field])) {
                    unlink($car[$img_field]);
                }
            }
            $_SESSION['message'] = "Car deleted successfully!";
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Failed to delete car: " . $stmt->error);
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
    <title>Delete Car - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --dark: #1b263b;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f72585;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 28px;
            color: var(--primary);
            margin-right: 10px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .logo-text span {
            color: var(--primary);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 25px;
        }

        nav ul li a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        nav ul li a i {
            margin-right: 8px;
            font-size: 18px;
        }

        nav ul li a:hover {
            color: var(--primary);
        }

        .user-actions {
            display: flex;
            align-items: center;
        }

        .user-greeting {
            margin-right: 20px;
            font-weight: 500;
            color: var(--dark);
        }

        .user-greeting span {
            color: var(--primary);
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            outline: none;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d1145a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(247, 37, 133, 0.2);
        }

        /* Auth Container Styles */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            margin: 50px auto;
            max-width: 600px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: slideIn 0.5s ease;
        }

        .auth-form-container {
            padding: 40px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 10px;
            position: relative;
        }

        .auth-header h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: var(--primary);
            margin: 10px auto;
        }

        .auth-header p {
            font-size: 16px;
            color: var(--gray);
            margin: 0;
        }

        .car-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .car-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 15px;
            text-align: center;
        }

        .car-main-image {
            width: 100%;
            max-width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid var(--primary);
        }

        .car-price {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .form-group {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .alert-success {
            background-color: var(--success);
            color: white;
        }

        .alert-error {
            background-color: var(--danger);
            color: white;
        }

        /* Footer Styles */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 40px 0;
            margin-top: 40px;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column li {
            margin-bottom: 10px;
        }

        .footer-column a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-column a:hover {
            color: var(--primary);
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .footer-social a {
            color: white;
            font-size: 18px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        /* Animation */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                margin: 20px;
                padding: 15px;
            }

            .auth-form-container {
                padding: 20px;
            }

            .auth-header h2 {
                font-size: 24px;
            }

            .auth-header p {
                font-size: 14px;
            }

            .car-main-image {
                max-width: 250px;
                height: 150px;
            }

            .car-card {
                padding: 15px;
            }
        }
    </style>
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
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="auth-container">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h2>Delete Car</h2>
                    <p>Are you sure you want to delete this car listing?</p>
                </div>
                <div class="car-card">
                    <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                    <img src="<?php echo htmlspecialchars($car['main_image']); ?>" alt="Car Image" class="car-main-image">
                    <div class="car-price">₹<?php echo number_format($car['price'], 0, '', ','); ?></div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-trash"></i> Confirm Delete
                        </button>
                    </div>
                    <div class="form-footer">
                        <p><a href="profile.php">Cancel</a></p>
                    </div>
                </form>
            </div>
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
                        <li><a href="favorites.php">Favorites</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Help & Support</h3>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">How to Sell</a></li>
                        <li><a href="#">Buyer Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Street, Mumbai, Maharashtra, India</li>
                        <li><i class="fas fa-phone-alt"></i> +91 9876543210</li>
                        <li><i class="fas fa-envelope"></i> support@carbazaar.com</li>
                        <li><i class="fas fa-clock"></i> Mon-Fri: 9 AM - 6 PM</li>
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