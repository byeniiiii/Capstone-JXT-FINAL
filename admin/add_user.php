<?php
// Include database connection
include '../db.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']); // Fix: Added email
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Generate a random password (default: 8 characters)
    $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

    // Check if username or email already exists
    $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Username or email already exists, redirect with error
        header("Location: users.php?error=Username or Email already exists");
        exit();
    }

    // Insert new user into database
    $query = "INSERT INTO users (first_name, last_name, email, username, phone_number, role, password) 
              VALUES ('$first_name', '$last_name', '$email', '$username', '$phone_number', '$role', '$hashed_password')";

    if (mysqli_query($conn, $query)) {
        // Redirect with success message
        header("Location: users.php?success=User added successfully. Default password: $password");
        exit();
    } else {
        // Redirect with error message
        header("Location: users.php?error=Failed to add user: " . mysqli_error($conn));
        exit();
    }
} else {
    // Redirect if accessed directly
    header("Location: users.php");
    exit();
}
?>
    