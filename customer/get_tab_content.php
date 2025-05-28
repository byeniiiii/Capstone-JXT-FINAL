<?php
session_start();
include '../db.php';

$tab = $_GET['tab'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 8;

// Get customer_id from session
$customer_id = $_SESSION['customer_id'];

// Fetch orders based on tab
$query = "SELECT o.*, ... FROM orders o WHERE o.customer_id = ? ";

switch($tab) {
    case 'pending':
        $query .= "AND o.order_status = 'pending_approval'";
        break;
    case 'approved':
        $query .= "AND o.order_status = 'approved'";
        break;
    // Add other cases for different tabs
}

$query .= " ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

$paginated_data = getPaginatedOrders($orders, $page);

// Include the table content template
include 'templates/' . $tab . '_table.php';

// Add pagination
echo generatePagination($paginated_data['current_page'], $paginated_data['total_pages'], $tab);
?>