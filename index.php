<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_favorite']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $user_id, $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
        $action = "removed from";
    } else {
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, car_id) VALUES (?, ?)");
        $action = "added to";
    }
    $stmt->bind_param("ii", $user_id, $car_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car $action your favorites!";
    } else {
        $_SESSION['error'] = "Error toggling favorite: " . $conn->error;
    }
    $stmt->close();
    header("Location: index.php");
    exit();
}

$search_where = "WHERE is_sold = FALSE";
$search_params = [];
$param_types = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    $min_price = filter_input(INPUT_GET, 'min_price', FILTER_SANITIZE_NUMBER_INT) ?: 0;
    $max_price = filter_input(INPUT_GET, 'max_price', FILTER_SANITIZE_NUMBER_INT) ?: 10000000;
    $fuel_type = filter_input(INPUT_GET, 'fuel_type', FILTER_SANITIZE_STRING);
    $transmission = filter_input(INPUT_GET, 'transmission', FILTER_SANITIZE_STRING);

    $search_where = "WHERE (model LIKE ? OR brand LIKE ? OR description LIKE ?) AND price BETWEEN ? AND ? AND is_sold = FALSE";
    $search_params = ["%$search%", "%$search%", "%$search%", $min_price, $max_price];
    $param_types = "sssii";

    if (!empty($fuel_type)) {
        $search_where .= " AND fuel_type = ?";
        $search_params[] = $fuel_type;
        $param_types .= "s";
    }

    if (!empty($transmission)) {
        $search_where .= " AND transmission = ?";
        $search_params[] = $transmission;
        $param_types .= "s";
    }
}

$sql_cars_list = "SELECT cars.*, users.username AS seller_name, users.phone AS seller_phone, users.email AS seller_email, users.seller_level 
                  FROM cars 
                  JOIN users ON cars.seller_id = users.id 
                  $search_where 
                  ORDER BY created_at DESC 
                  LIMIT 12";

$stmt = $conn->prepare($sql_cars_list);
if (!empty($search_params)) {
    $stmt->bind_param($param_types, ...$search_params);
}
$stmt->execute();
$cars_result = $stmt->get_result();

$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT car_id FROM favorites WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $favorites_result = $stmt->get_result();
    
    while ($row = $favorites_result->fetch_assoc()) {
        $favorites[] = $row['car_id'];
    }
    $stmt->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBazaar - Used Car Selling Platform</title>
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
                    <li><a href="#cars"><i class="fas fa-car"></i> Cars</a></li>
                    <li><a href="#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="#contact"><i class="fas fa-phone-alt"></i> Contact</a></li>
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

    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Used Car</h1>
            <p>Buy and sell quality used cars from trusted sellers across India</p>
            <div class="hero-buttons">
                <a href="#cars" class="btn btn-primary"><i class="fas fa-car"></i> Browse Cars</a>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <a href="add_car.php" class="btn btn-outline"><i class="fas fa-plus"></i> Add Car</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="search-section" id="search">
            <div class="search-title">
                <h2>Find Your Dream Car</h2>
                <p>Search through our extensive inventory of quality used cars</p>
            </div>
            
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Keywords</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Toyota, Honda, SUV..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="min_price"><i class="fas fa-rupee-sign"></i> Min Price</label>
                    <input type="number" id="min_price" name="min_price" class="form-control" min="0" placeholder="₹10,000" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_price"><i class="fas fa-rupee-sign"></i> Max Price</label>
                    <input type="number" id="max_price" name="max_price" class="form-control" min="0" placeholder="₹50,00,000" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '10000000'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control">
                        <option value="">Any Fuel Type</option>
                        <option value="Petrol" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Petrol') ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Electric') ? 'selected' : ''; ?>>Electric</option>
                        <option value="Hybrid" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="CNG" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'CNG') ? 'selected' : ''; ?>>CNG</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transmission"><i class="fas fa-cog"></i> Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="">Any Transmission</option>
                        <option value="Automatic" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search Cars</button>
                    <a href="index.php" class="btn btn-outline"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <section id="cars">
            <div class="section-header">
                <h2 class="section-title">Available Cars</h2>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <a href="add_car.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Car</a>
                <?php endif; ?>
            </div>
            
            <div class="cars-grid">
                <?php if ($cars_result->num_rows > 0): ?>
                    <?php while ($car = $cars_result->fetch_assoc()): ?>
                        <div class="car-card">
                            <?php if ($car['is_sold']): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php else: ?>
                                <div class="car-badge">NEW</div>
                            <?php endif; ?>
                            
                            <div class="car-image">
                                <?php
                                $stmt = $conn->prepare("SELECT image_path FROM car_images WHERE car_id = ? LIMIT 1");
                                $stmt->bind_param("i", $car['id']);
                                $stmt->execute();
                                $image_result = $stmt->get_result();
                                $image = $image_result->fetch_assoc();
                                $stmt->close();
                                ?>
                                <img src="<?php echo htmlspecialchars($image['image_path'] ?? 'Uploads/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
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
                                
                                <p class="car-description"><?php echo htmlspecialchars(substr($car['description'], 0, 100)); ?>...</p>
                                
                                <div class="car-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="toggle_favorite" class="favorite-btn <?php echo in_array($car['id'], $favorites) ? 'active' : ''; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                    <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-car" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3 style="color: var(--gray);">No cars found matching your criteria</h3>
                        <p>Try adjusting your search filters or check back later for new listings</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 20px;"><i class="fas fa-sync-alt"></i> Reset Search</a>
                    </div>
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

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'seek' });
                }
            });
        });
    </script>
</body>
</html>
