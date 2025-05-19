<?php
// Include database connection
include '../db.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php?error=Invalid user ID");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['id']);

// Prevent admin from deleting themselves (optional, adjust as needed)
session_start();
if ($_SESSION['user_id'] == $user_id) {
    header("Location: users.php?error=You cannot delete your own account");
    exit();
}

// Delete user from database
$delete_query = "DELETE FROM users WHERE user_id = '$user_id'";

if (mysqli_query($conn, $delete_query)) {
    header("Location: users.php?success=User deleted successfully");
    exit();
} else {
    header("Location: users.php?error=Failed to delete user: " . mysqli_error($conn));
    exit();
}
?>
