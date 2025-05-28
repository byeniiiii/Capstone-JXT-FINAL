<?php
// Debug script to check for errors in sublimation_orders.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../db.php';

// Log function
function debug_log($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

debug_log('Debug script started');

// Check database connection
if ($conn->connect_error) {
    debug_log('Database connection error: ' . $conn->connect_error);
    echo "Database connection error: " . $conn->connect_error;
    exit;
}

debug_log('Database connection successful');

// Get GET parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
debug_log('Status filter: ' . $status);

// Try to run the main query from sublimation_orders.php
try {
    $where_clauses = ["o.order_type = 'sublimation'"]; 
    $params = [];
    $types = "";
    
    if ($status != 'all') {
        $where_clauses[] = "o.order_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "SELECT COUNT(*) as total FROM orders o 
                   LEFT JOIN customers c ON o.customer_id = c.customer_id 
                   $where_clause";
                   
    debug_log('Count query: ' . $count_query);
    
    $stmt = mysqli_prepare($conn, $count_query);
    
    if (!empty($params)) {
        debug_log('Binding parameters for count query: ' . print_r($params, true));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $total_records = mysqli_fetch_assoc($count_result)['total'];
    
    debug_log('Total records: ' . $total_records);
    
    // Test the main query
    $main_query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number, 
             CASE 
                WHEN o.order_type = 'sublimation' THEN t.completion_date
                ELSE NULL
             END AS completion_date
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.customer_id
             LEFT JOIN sublimation_orders t ON o.order_id = t.order_id AND o.order_type = 'sublimation'
             $where_clause
             ORDER BY 
                CASE 
                    WHEN o.order_status = 'pending_approval' THEN 1
                    WHEN o.order_status = 'approved' THEN 2
                    WHEN o.order_status = 'in_process' THEN 3
                    WHEN o.order_status = 'printing_done' THEN 4
                    WHEN o.order_status = 'ready_for_pickup' THEN 5
                    WHEN o.order_status = 'completed' THEN 6
                    ELSE 7
                END,
                o.created_at DESC
             LIMIT 10";
             
    debug_log('Main query: ' . $main_query);
    
    $stmt = mysqli_prepare($conn, $main_query);
    
    if (!empty($params)) {
        debug_log('Binding parameters for main query: ' . print_r($params, true));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        debug_log('Error executing main query: ' . mysqli_stmt_error($stmt));
        echo "Error executing main query: " . mysqli_stmt_error($stmt);
    } else {
        $result = mysqli_stmt_get_result($stmt);
        debug_log('Main query successful, retrieved ' . mysqli_num_rows($result) . ' rows');
        
        // Display first row
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            debug_log('First order ID: ' . $row['order_id'] . ', Status: ' . $row['order_status']);
        }
    }
    
    // Check status counts query
    $count_query = "SELECT order_status, COUNT(*) as count FROM orders WHERE order_type = 'sublimation' GROUP BY order_status";
    debug_log('Status count query: ' . $count_query);
    
    $count_result = mysqli_query($conn, $count_query);
    
    if (!$count_result) {
        debug_log('Error in status count query: ' . mysqli_error($conn));
        echo "Error in status count query: " . mysqli_error($conn);
    } else {
        debug_log('Status count query successful, retrieved ' . mysqli_num_rows($count_result) . ' rows');
        while ($row = mysqli_fetch_assoc($count_result)) {
            debug_log('Status: ' . $row['order_status'] . ', Count: ' . $row['count']);
        }
    }
    
} catch (Exception $e) {
    debug_log('Exception: ' . $e->getMessage());
    echo "Exception: " . $e->getMessage();
}

echo "Debug completed, check debug.log for results";
