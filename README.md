# CarBazaar 🚗

**CarBazaar** is a web platform for buying and selling used cars, built with PHP, MySQL, HTML, CSS, and JavaScript. It provides a user-friendly interface for users to browse, list, and manage car listings, with features for messaging, favorites, and admin management.

## Features

### **User Management**
- **Registration & Login**: Users can register as buyers or sellers and log in securely with session-based authentication.
- **Profile Management**: Users can view and update their profile details, including username, email, and profile picture.
- **Admin Privileges**: Admins can manage users (view or delete non-admin accounts) and oversee car listings.

### **Car Listings**
- **Browse Cars**: Users can view a list of available cars with details like brand, model, year, price, and images.
- **Search & Filter**: Filter cars by brand, model, or location for quick discovery.
- **Add/Edit Cars**: Sellers and admins can add or edit car listings, including details like fuel type, transmission, kilometers driven, and up to four images.
- **Favorites**: Users can mark cars as favorites for quick access later.

### **Messaging System**
- **Direct Messaging**: Users can message sellers about specific cars, with conversations organized by car and user.
- **Unread Notifications**: Displays unread message counts in the navigation bar.
- **Delete Chats**: Users can delete their chat history for a specific car.

### **Admin Dashboard**
- **User Management**: Admins can view all users, search by username/email, and delete non-admin accounts.
- **Car Oversight**: Admins can edit or delete any car listing, regardless of ownership.

### **Security & Validation**
- **Input Sanitization**: All inputs are sanitized to prevent SQL injection and XSS attacks.
- **File Uploads**: Image uploads are restricted to JPEG/PNG formats and a 5MB size limit.
- **Access Control**: Only sellers/admins can add/edit cars, and only admins can manage users.


## **Technologies Used**
- **Backend**: PHP, MySQL
- **Frontend**: HTML, CSS (with `style.css`), JavaScript
- **Libraries**: Font Awesome for icons, Google Fonts (Poppins)

## **Key Files**
- `index.php`: Homepage with car listings and navigation.
- `register.php` & `login.php`: User registration and login forms.
- `profile.php`: User profile and car listing management.
- `edit_car.php`: Form to edit car details.
- `messages.php`: Messaging interface for user-seller communication.
- `admin_dashboard.php` & `admin_users.php`: Admin panel for user and car management.

## **Notes**
- Ensure the `Uploads/cars/` and `Uploads/profiles/` directories are writable for image uploads.
- The site uses session-based authentication with a 24-hour cookie lifetime.
- AJAX polling in `messages.php` refreshes chats every 5 seconds.

