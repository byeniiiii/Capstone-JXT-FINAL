<?php
// Include database connection
include '../db.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    
    // Role is fixed to "Sublimator"
    $role = 'Sublimator';

    // Generate a random password (8 characters)
    $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if username or email already exists
    $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        header("Location: sublimator.php?error=Username or Email already exists");
        exit();
    }

    // Insert new Sublimator into database
    $query = "INSERT INTO users (first_name, last_name, email, username, phone_number, role, password) 
              VALUES ('$first_name', '$last_name', '$email', '$username', '$phone_number', '$role', '$hashed_password')";

    if (mysqli_query($conn, $query)) {
        header("Location: sublimator.php?success=Sublimator added successfully. Default password: $password");
        exit();
    } else {
        header("Location: sublimator.php?error=Failed to add Sublimator: " . mysqli_error($conn));
        exit();
    }
} else {
    header("Location: sublimator.php");
    exit();
}
?>
