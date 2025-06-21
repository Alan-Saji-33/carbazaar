<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($_POST['password'], $user['password'])) {
                if ($user['user_type'] == 'seller' && !$user['is_approved'] && $user['seller_level'] != 'first_time') {
                    $_SESSION['error'] = "Your seller account is pending approval.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['phone'] = $user['phone'];
                    $_SESSION['seller_level'] = $user['seller_level'];
                    $_SESSION['message'] = "Login successful!";
                    header("Location: index.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid password.";
            }
        } else {
            $_SESSION['error'] = "User not found.";
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
    <title>Login - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;Expected Behavior:
When a user visits the website, they should see a list of cars with a search bar to filter them. They can click on a car to view its details, including multiple images and seller information with a badge indicating whether the seller is verified. Sellers can add up to three cars without verification, after which they must submit Aadhar details for admin approval to become verified and sell more. The admin can approve or deny seller verification requests via the admin dashboard. Users can favorite cars, message sellers in real-time, and manage their profiles. All pages should maintain consistent styling and be properly linked within the CarBazaar directory.

The provided files implement all these features, ensuring a single directory structure, proper relative paths, and a cohesive user experience. If you need further assistance or modifications, please let me know!
