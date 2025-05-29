<?php
// Include database connection
session_start();
include '../db.php';

// Function to log user activity
function logActivity($conn, $user_id, $action_type, $description) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $user_type = mysqli_real_escape_string($conn, $_SESSION['role'] ?? 'Unknown');
    $action_type = mysqli_real_escape_string($conn, $action_type);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
              VALUES ('$user_id', '$user_type', '$action_type', '$description', NOW())";
    
    mysqli_query($conn, $query);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    
    // Role is fixed to "Sublimator"
    $role = 'Sublimator';

    // Use the provided password if it exists
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
    } else {
        // Generate a random password if none is provided
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    }
    
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
        // Log the sublimator creation activity
        if (isset($_SESSION['user_id'])) {
            logActivity($conn, $_SESSION['user_id'], 'CREATE', "Created new sublimator: $first_name $last_name");
        }
        header("Location: sublimator.php?success=Sublimator added successfully. Password: $password");
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
