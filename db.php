<?php
// Error reporting settings
error_reporting(0); // Turn off all error reporting
ini_set('display_errors', 0); // Don't display errors
ini_set('log_errors', 1); // Log errors instead
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
