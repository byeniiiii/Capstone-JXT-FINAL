<?php
// Error reporting settings
error_reporting(E_ALL); // Show all errors
ini_set('display_errors', 1); // Display errors
ini_set('log_errors', 1); // Also log errors
ini_set('error_log', 'error.log'); // Set error log file

$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "tailor_db"; 

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
