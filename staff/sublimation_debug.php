<?php
// Debug version of sublimation_orders.php with fixes
// Start session with secure settings for local development
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Disable secure flag for localhost (non-HTTPS)
ini_set('session.cookie_secure', 0);
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'sublimation_debug.log');

// Check session
if (!isset($_SESSION['user_id'])) {
    echo "Error: No user_id in session";
    exit;
}

// Include database connection
include '../db.php';

echo "<h2>Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit;
} else {
    echo "Database connection successful!<br>";
}

// Status filter 
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
echo "Status filter: " . htmlspecialchars($status_filter) . "<br>";

// Pagination - fixed with proper error handling
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

echo "Page: $page, Offset: $offset<br>";

// Build the query with filters
$where_clauses = ["o.order_type = 'sublimation'"];
$params = [];
$types = "";

if ($status_filter != 'all') {
    $where_clauses[] = "o.order_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

echo "SQL WHERE clause: " . htmlspecialchars($where_clause) . "<br>";

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o 
               LEFT JOIN customers c ON o.customer_id = c.customer_id 
               $where_clause";

echo "Count query: " . htmlspecialchars($count_query) . "<br>";

$stmt = mysqli_prepare($conn, $count_query);

if (!$stmt) {
    echo "Error preparing count query: " . mysqli_error($conn) . "<br>";
    exit;
}

if (!empty($params)) {
    echo "Binding parameters for count query: " . implode(", ", $params) . "<br>";
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

$execute_result = mysqli_stmt_execute($stmt);

if (!$execute_result) {
    echo "Error executing count query: " . mysqli_stmt_error($stmt) . "<br>";
    exit;
}

$count_result = mysqli_stmt_get_result($stmt);

if (!$count_result) {
    echo "Error getting count result: " . mysqli_stmt_error($stmt) . "<br>";
    exit;
}

$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

echo "Total records: $total_records, Total pages: $total_pages<br>";

// Main query with fixed ordering
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number, 
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
         LIMIT ?, ?";

echo "Main query (without LIMIT params): <pre>" . 
     htmlspecialchars(str_replace("LIMIT ?, ?", "LIMIT $offset, $records_per_page", $query)) . 
     "</pre>";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo "Error preparing main query: " . mysqli_error($conn) . "<br>";
    exit;
}

// Add pagination parameters to the existing parameters array
if (!empty($params)) {
    $types .= "ii";
    $params[] = $offset;
    $params[] = $records_per_page;
    echo "Binding parameters for main query: " . implode(", ", $params) . "<br>";
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    echo "Binding only pagination parameters: $offset, $records_per_page<br>";
    mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
}

$execute_result = mysqli_stmt_execute($stmt);

if (!$execute_result) {
    echo "Error executing main query: " . mysqli_stmt_error($stmt) . "<br>";
    exit;
}

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    echo "Error getting result set: " . mysqli_stmt_error($stmt) . "<br>";
    exit;
}

echo "Query executed successfully! Retrieved " . mysqli_num_rows($result) . " rows.<br>";

// Status counts
echo "<h3>Status Counts</h3>";
$status_counts = [
    'pending_approval' => 0,
    'approved' => 0,
    'forward_to_sublimator' => 0,
    'in_process' => 0,
    'printing_done' => 0,
    'ready_for_pickup' => 0,
    'completed' => 0,
    'declined' => 0
];

$count_query = "SELECT order_status, COUNT(*) as count FROM orders WHERE order_type = 'sublimation' GROUP BY order_status";
$count_result = mysqli_query($conn, $count_query);

if (!$count_result) {
    echo "Error in status count query: " . mysqli_error($conn) . "<br>";
} else {
    while ($row = mysqli_fetch_assoc($count_result)) {
        if (!empty($row['order_status'])) {
            $status = $row['order_status'];
            $count = $row['count'];
            $status_counts[$status] = $count;
            echo "$status: $count<br>";
        } else {
            echo "WARNING: Found orders with empty status<br>";
        }
    }
}

echo "<h3>Debug Information</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "</pre>";

// Display query results
echo "<h3>Query Results</h3>";
echo "<table border='1'>";
echo "<tr><th>Order ID</th><th>Customer</th><th>Status</th><th>Payment</th><th>Created</th></tr>";

$result->data_seek(0); // Reset result pointer
while ($order = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . "</td>";
    echo "<td>" . htmlspecialchars($order['order_status']) . "</td>";
    echo "<td>" . htmlspecialchars($order['payment_status']) . "</td>";
    echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
