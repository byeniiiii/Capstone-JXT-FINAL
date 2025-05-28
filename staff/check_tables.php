<?php
// Check if the sublimation_orders table exists and its structure
include '../db.php';

// Check for sublimation_orders table
$query = "DESCRIBE sublimation_orders";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Error: The sublimation_orders table does not exist or cannot be accessed.<br>";
    echo "MySQL Error: " . mysqli_error($conn) . "<br>";
    
    // Check if it's just the table name that's wrong
    $tables_query = "SHOW TABLES LIKE '%sublim%'";
    $tables_result = mysqli_query($conn, $tables_query);
    
    if (mysqli_num_rows($tables_result) > 0) {
        echo "Found tables with 'sublim' in the name:<br>";
        while ($row = mysqli_fetch_array($tables_result)) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "No tables found with 'sublim' in the name.<br>";
    }
} else {
    echo "The sublimation_orders table exists. Here's its structure:<br>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table><br>";
    
    // Check for order_id column in sublimation_orders
    $check_order_id = "SELECT * FROM sublimation_orders LIMIT 1";
    $order_id_result = mysqli_query($conn, $check_order_id);
    
    if (!$order_id_result) {
        echo "Error querying sublimation_orders: " . mysqli_error($conn) . "<br>";
    } else {
        $row = mysqli_fetch_assoc($order_id_result);
        echo "Sample data from sublimation_orders:<br>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
}

// Check orders table structure
echo "<br>Checking orders table for order_type and order_status columns:<br>";
$orders_query = "DESCRIBE orders";
$orders_result = mysqli_query($conn, $orders_query);

if (!$orders_result) {
    echo "Error accessing orders table: " . mysqli_error($conn) . "<br>";
} else {
    $has_order_type = false;
    $has_order_status = false;
    
    while ($row = mysqli_fetch_assoc($orders_result)) {
        if ($row['Field'] == 'order_type') {
            $has_order_type = true;
            echo "orders.order_type exists with type: " . $row['Type'] . "<br>";
        }
        if ($row['Field'] == 'order_status') {
            $has_order_status = true;
            echo "orders.order_status exists with type: " . $row['Type'] . "<br>";
        }
    }
    
    if (!$has_order_type) {
        echo "ERROR: orders.order_type column is missing!<br>";
    }
    
    if (!$has_order_status) {
        echo "ERROR: orders.order_status column is missing!<br>";
    }
    
    // Check orders with sublimation type
    $sublimation_orders = "SELECT COUNT(*) as count FROM orders WHERE order_type = 'sublimation'";
    $sublimation_result = mysqli_query($conn, $sublimation_orders);
    
    if (!$sublimation_result) {
        echo "Error counting sublimation orders: " . mysqli_error($conn) . "<br>";
    } else {
        $count = mysqli_fetch_assoc($sublimation_result)['count'];
        echo "Number of orders with order_type = 'sublimation': $count<br>";
    }
}
?>
