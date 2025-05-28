<?php
// filepath: c:\xampp\htdocs\capstone_jxt\db_check_new.php

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h1>Database Connection Check</h1>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>Database connection successful!</p>";
}

// Check tables needed for sublimator assignment
$tables_to_check = ['orders', 'users', 'activity_logs', 'sublimation_orders'];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $query = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($query);
    
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (count($missing_tables) > 0) {
    echo "<p style='color: red;'>Missing tables: " . implode(', ', $missing_tables) . "</p>";
} else {
    echo "<p style='color: green;'>All required tables exist!</p>";
}

// Check if sublimators exist in the database
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'sublimator'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo "<p style='color: green;'>" . $row['count'] . " sublimators found in the database.</p>";
} else {
    echo "<p style='color: orange;'>No sublimators found in the database. You need to add sublimators before you can assign orders.</p>";
}

// Check activity_logs structure
echo "<h2>Activity Logs Table Structure</h2>";
$query = "DESCRIBE activity_logs";
$result = $conn->query($query);

if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error fetching activity_logs structure: " . $conn->error . "</p>";
}

echo "<h2>Test Activity Log Insert</h2>";
// Test inserting into activity_logs
try {
    $query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, ip_address) 
              VALUES (1, 'staff', 'test', 'This is a test from db_check_new.php', '127.0.0.1')";
    
    if ($conn->query($query)) {
        echo "<p style='color: green;'>Successfully inserted test record into activity_logs table.</p>";
    } else {
        echo "<p style='color: red;'>Failed to insert test record into activity_logs: " . $conn->error . "</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

// Close connection
$conn->close();
?>
