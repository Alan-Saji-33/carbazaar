<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "car_rental_db";

session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

try {
    $temp_conn = new mysqli($db_host, $db_user, $db_pass);
    if ($temp_conn->connect_error) {
        throw new Exception("Initial connection failed: " . $temp_conn->connect_error);
    }

    $create_db = "CREATE DATABASE IF NOT EXISTS $db_name";
    if (!$temp_conn->query($create_db)) {
        throw new Exception("Error creating database: " . $temp_conn->error);
    }
    $temp_conn->close();

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(15) NULL,
        user_type ENUM('admin', 'seller', 'buyer') NOT NULL,
        seller_level ENUM('first_time', 'verified', 'pending') NULL,
        profile_image VARCHAR(255) NULL,
        location VARCHAR(100) NULL,
        aadhar_number VARCHAR(12) NULL,
        aadhar_image VARCHAR(255) NULL,
        is_approved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "cars" => "CREATE TABLE IF NOT EXISTS cars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        model VARCHAR(100) NOT NULL,
        brand VARCHAR(50) NOT NULL,
        year INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        km_driven INT NOT NULL,
        fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG') NOT NULL,
        transmission ENUM('Automatic', 'Manual') NOT NULL,
        description TEXT,
        is_sold BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "car_images" => "CREATE TABLE IF NOT EXISTS car_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
    )",

    "favorites" => "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorite (user_id, car_id)
    )",

    "messages" => "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $table_name => $sql) {
    if (!$conn->query($sql)) {
        die("Error creating table $table_name: " . $conn->error);
    }
}

$admin_check = $conn->query("SELECT * FROM users WHERE username = 'admin' AND user_type = 'admin'");
if ($admin_check->num_rows == 0) {
    $admin_password = password_hash('admin', PASSWORD_DEFAULT);
    $admin_email = 'admin@carbazaar.com';
    $admin_sql = "INSERT INTO users (username, password, email, user_type, is_approved) VALUES ('admin', '$admin_password', '$admin_email', 'admin', TRUE)";
    if (!$conn->query($admin_sql)) {
        die("Error creating admin user: " . $conn->error);
    }
}
?>
