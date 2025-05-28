<?php
// Simple script to test database connection
include 'db.php';

// Check if tables exist
$tables = ['orders', 'tailoring_orders', 'sublimation_orders', 'customers', 'payments'];
$missing_tables = [];

foreach ($tables as $table) {
    $query = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $query);
    if (!$result || mysqli_num_rows($result) == 0) {
        $missing_tables[] = $table;
    }
}

// Check connection and output status
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Connection successful! Server version: " . mysqli_get_server_info($conn) . "<br>";
    echo "Character set: " . mysqli_character_set_name($conn) . "<br>";
    
    if (empty($missing_tables)) {
        echo "All required tables exist.<br>";
    } else {
        echo "Missing tables: " . implode(", ", $missing_tables) . "<br>";
    }
    
    // Check the CASE statement issue in tailoring_orders.php
    $test_query = "SELECT order_id, order_status, 
                 CASE 
                    WHEN order_status = 'pending_approval' THEN 1
                    WHEN order_status = 'approved' THEN 2
                    WHEN order_status = 'in_process' THEN 3
                    WHEN order_status = 'ready_for_pickup' THEN 4
                    WHEN order_status = 'completed' THEN 5
                    ELSE 6
                 END as status_order
                 FROM orders 
                 WHERE order_type = 'tailoring'
                 ORDER BY status_order, created_at DESC 
                 LIMIT 1";
    
    $test_result = mysqli_query($conn, $test_query);
    if (!$test_result) {
        echo "CASE statement test failed: " . mysqli_error($conn) . "<br>";
    } else {
        echo "CASE statement test passed.<br>";
    }
}
?>
