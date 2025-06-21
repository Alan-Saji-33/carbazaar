<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to view messages.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$car_id = filter_input(INPUT_GET, 'car_id', FILTER_SANITIZE_NUMBER_INT);
$receiver_id = filter_input(INPUT_GET, 'receiver_id', FILTER_SANITIZE_NUMBER_INT);

$conversations = [];
$stmt = $conn->prepare("SELECT DISTINCT c.id AS car_id, c.brand, c.model, u.id AS user_id, u.username 
                        FROM messages m 
                        JOIN cars c ON m.car_id = c.id 
                        JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id) 
                        WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ? 
                        ORDER BY m.created_at DESC");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

$messages = [];
$car_details = null;
if ($car_id && $receiver_id) {
    $stmt = $conn->prepare("SELECT m.*, u1.username AS sender_name, u2.username AS receiver_name 
                            FROM messages m 
                            JOIN users u1 ON m.sender_id = u1.id 
                            JOIN users u2 ON m.receiver_id = u2.id 
                            WHERE m.car_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) 
                            ORDER BY m.created_at ASC");
    $stmt->bind_param("iiiii", $car_id, $user_id, $receiver_id, $receiver_id, $user_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT brand, model FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $car_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE car_id = ? AND receiver_id = ? AND sender_id = ?");
    $stmt->bind_param("iii", $car_id, $user_id, $receiver_id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    if ($message && $car_id && $receiver_id) {
        $stmt = $conn->prepare("INSERT INTO messages (car_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $car_id, $user_id, $receiver_id, $message);
        if ($stmt->execute()) {
            header("Location: messages.php?car_id=$car_id&receiver_id=$receiver_id");
            exit();
        } else {
            $_SESSION['error'] = "Error sending message: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid message or recipient.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - CarBazaar</title>
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
            <h2 class="section-title">Messages</h2>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <div class="car-details-container">
            <div class="car-details-image" style="flex: 1;">
                <h3>Conversations</h3>
                <div class="cars-grid">
                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="car-card">
                                <div class="car-details">
                                    <h3 class="car-title"><?php echo htmlspecialchars($conv['brand'] . ' ' . $conv['model']); ?></h3>
                                    <p><strong>User:</strong> <?php echo htmlspecialchars($conv['username']); ?></p>
                                    <div class="car-actions">
                                        <a href="messages.php?car_id=<?php echo $conv['car_id']; ?>&receiver_id=<?php echo $conv['user_id']; ?>" class="btn btn-outline"><i class="fas fa-envelope"></i> View Chat</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No conversations found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="car-details-info" style="flex: 2;">
                <?php if ($car_id && $receiver_id && $car_details): ?>
                    <h3>Chat about <?php echo htmlspecialchars($car_details['brand'] . ' ' . $car_details['model']); ?></h3>
                    <div class="chat-section">
                        <div class="chat-box" id="chat-box">
                            <?php foreach ($messages as $msg): ?>
                                <div class="chat-message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                    <p><strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?></p>
                                    <small><?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" id="chat-form">
                            <div class="form-group">
                                <textarea id="message" name="message" class="form-control" rows="2" placeholder="Type your message..." required></textarea>
                            </div>
                            <div class="form-group form-actions">
                                <button type="submit" name="send_message" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <p>Select a conversation to start chatting.</p>
                <?php endif; ?>
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

    <?php if ($car_id && $receiver_id): ?>
        <script src="chat.js"></script>
        <script>
            startChat(<?php echo $car_id; ?>, <?php echo $receiver_id; ?>, <?php echo $user_id; ?>);
        </script>
    <?php endif; ?>
</body>
</html>
