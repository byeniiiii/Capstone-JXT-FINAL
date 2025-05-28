<?php
// Script to validate the specific query in tailoring_orders.php
include 'db.php';

// Test the exact query
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number, 
         CASE 
            WHEN o.order_type = 'tailoring' THEN t.completion_date
            ELSE NULL
         END AS completion_date
         FROM orders o
         LEFT JOIN customers c ON o.customer_id = c.customer_id
         LEFT JOIN tailoring_orders t ON o.order_id = t.order_id AND o.order_type = 'tailoring'
         WHERE o.order_type = 'tailoring'
         ORDER BY 
            CASE 
                WHEN o.order_status = 'pending_approval' THEN 1
                WHEN o.order_status = 'approved' THEN 2
                WHEN o.order_status = 'in_process' THEN 3
                WHEN o.order_status = 'ready_for_pickup' THEN 4
                WHEN o.order_status = 'completed' THEN 5
                ELSE 6
            END,
            o.created_at DESC
         LIMIT 10";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "ERROR: Query failed: " . mysqli_error($conn) . "<br>";
} else {
    echo "Query executed successfully. Found " . mysqli_num_rows($result) . " rows.<br>";
    
    // Display first result
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "First row order_id: " . $row['order_id'] . ", status: " . $row['order_status'] . "<br>";
    }
}

?>
