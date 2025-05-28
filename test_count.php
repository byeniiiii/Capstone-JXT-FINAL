<?php
// Script to check the count query in tailoring_orders.php
include 'db.php';

// Test the count query
$count_query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$count_result = mysqli_query($conn, $count_query);

if (!$count_result) {
    echo "ERROR: Count query failed: " . mysqli_error($conn) . "<br>";
} else {
    echo "Count query executed successfully.<br>";
    echo "Status counts:<br>";
    while ($row = mysqli_fetch_assoc($count_result)) {
        echo "- " . $row['order_status'] . ": " . $row['count'] . "<br>";
    }
}

// Now test a filtered count query
$filtered_count_query = "SELECT order_status, COUNT(*) as count FROM orders WHERE order_type = 'tailoring' GROUP BY order_status";
$filtered_count_result = mysqli_query($conn, $filtered_count_query);

if (!$filtered_count_result) {
    echo "ERROR: Filtered count query failed: " . mysqli_error($conn) . "<br>";
} else {
    echo "<br>Filtered count query (only tailoring) executed successfully.<br>";
    echo "Tailoring status counts:<br>";
    while ($row = mysqli_fetch_assoc($filtered_count_result)) {
        echo "- " . $row['order_status'] . ": " . $row['count'] . "<br>";
    }
}

?>
