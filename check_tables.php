<?php
// Script to check the structure of the orders table
include 'db.php';

// Check order_type column in orders table
$query = "SHOW COLUMNS FROM orders LIKE 'order_type'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "ERROR: 'order_type' column doesn't exist in orders table.<br>";
} else {
    $column = mysqli_fetch_assoc($result);
    echo "orders.order_type exists with type: " . $column['Type'] . "<br>";
}

// Check order_status column
$query = "SHOW COLUMNS FROM orders LIKE 'order_status'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "ERROR: 'order_status' column doesn't exist in orders table.<br>";
} else {
    $column = mysqli_fetch_assoc($result);
    echo "orders.order_status exists with type: " . $column['Type'] . "<br>";
}

// Check if tailoring_orders and orders tables can be joined
$query = "SELECT o.order_id, t.completion_date 
          FROM orders o 
          LEFT JOIN tailoring_orders t ON o.order_id = t.order_id AND o.order_type = 'tailoring'
          LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "ERROR: Cannot join orders and tailoring_orders tables: " . mysqli_error($conn) . "<br>";
} else {
    echo "orders and tailoring_orders tables can be joined successfully.<br>";
}

// Check order_type values in orders table
$query = "SELECT DISTINCT order_type FROM orders";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "ERROR: Cannot query distinct order_type values: " . mysqli_error($conn) . "<br>";
} else {
    echo "Distinct order_type values: ";
    $types = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $types[] = $row['order_type'];
    }
    echo implode(", ", $types) . "<br>";
}

// Check count of tailoring orders
$query = "SELECT COUNT(*) as count FROM orders WHERE order_type = 'tailoring'";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "ERROR: Cannot count tailoring orders: " . mysqli_error($conn) . "<br>";
} else {
    $row = mysqli_fetch_assoc($result);
    echo "Number of tailoring orders: " . $row['count'] . "<br>";
}

?>
