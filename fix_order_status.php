<?php
// Script to fix any empty or NULL order statuses
include 'db.php';

echo "Fixing empty or NULL order statuses...<br>";

// Update any empty or NULL order_status values to 'pending_approval'
$update_query = "UPDATE orders SET order_status = 'pending_approval' WHERE order_status IS NULL OR order_status = ''";
$result = mysqli_query($conn, $update_query);

if ($result) {
    echo "Successfully updated " . mysqli_affected_rows($conn) . " records with empty order_status values.<br>";
} else {
    echo "ERROR: Could not update empty order_status values: " . mysqli_error($conn) . "<br>";
}

echo "<br>Done!";
?>
