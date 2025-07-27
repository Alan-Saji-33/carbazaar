-- car_rental_db.sql
-- Database creation and table setup for CarBazaar application

-- Create the database
CREATE DATABASE IF NOT EXISTS car_rental_db;
USE car_rental_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NULL,
    user_type ENUM('admin', 'seller', 'buyer') NOT NULL,
    location VARCHAR(100) NULL,
    profile_pic VARCHAR(255) NULL,
    aadhaar_status ENUM('not_submitted', 'pending', 'approved', 'rejected') DEFAULT 'not_submitted',
    aadhaar_path VARCHAR(255) NULL,
    aadhaar_rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cars table
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    model VARCHAR(100) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    price INT NOT NULL,
    km_driven INT NOT NULL,
    fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG') NOT NULL,
    transmission ENUM('Automatic', 'Manual') NOT NULL,
    main_image VARCHAR(255) NOT NULL,
    sub_image1 VARCHAR(255) NULL,
    sub_image2 VARCHAR(255) NULL,
    sub_image3 VARCHAR(255) NULL,
    location VARCHAR(100) NOT NULL,
    ownership ENUM('First', 'Second', 'Third', 'Other') NOT NULL,
    insurance_status ENUM('Valid', 'Expired', 'None') NOT NULL,
    description TEXT,
    is_sold BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, car_id)
);

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    car_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);
