<?php
/**
 * Authentication check for staff pages
 * This file verifies that the user is logged in and has appropriate staff permissions
 */

// Debug session
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure session is started (although it should be started in the including file)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session variables
var_dump($_SESSION);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Debug redirect
    echo "Redirecting to index.php because: ";
    if (!isset($_SESSION['user_id'])) echo "No user_id in session. ";
    if (!isset($_SESSION['role'])) echo "No role in session.";
    header("Location: ../index.php");
    exit();
}

// Check if user has staff permissions (all roles except Customer)
$allowed_roles = ['Admin', 'Manager', 'Tailor', 'Staff', 'Sublimator'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Debug redirect
    echo "Redirecting to index.php because role " . $_SESSION['role'] . " is not allowed.";
    header("Location: ../index.php?error=insufficient_permissions");
    exit();
}

// Include database connection
include '../db.php';

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$order_status = isset($_GET['order_status']) ? $_GET['order_status'] : 'all';
$order_type = isset($_GET['order_type']) ? $_GET['order_type'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build the base query
$query = "SELECT o.*, 
          c.first_name AS customer_fname, c.last_name AS customer_lname,
          s.first_name AS staff_fname, s.last_name AS staff_lname
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users s ON o.staff_id = s.user_id
          WHERE 1=1";

$params = array();
$types = "";

// Add date range filter
if ($date_from && $date_to) {
    $query .= " AND DATE(o.created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

// Add status filter
if ($order_status != 'all') {
    $query .= " AND o.order_status = ?";
    $params[] = $order_status;
    $types .= "s";
}

// Add order type filter
if ($order_type != 'all') {
    $query .= " AND o.order_type = ?";
    $params[] = $order_type;
    $types .= "s";
}

// Add search filter
if ($search_query) {
    $query .= " AND (o.order_id LIKE ? OR 
                     CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR
                     CONCAT(s.first_name, ' ', s.last_name) LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Add sorting
$query .= " ORDER BY o.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all possible order statuses for filter dropdown
$all_statuses_query = "SELECT DISTINCT order_status FROM orders";
$all_statuses_result = mysqli_query($conn, $all_statuses_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>JXT Staff - Order Reports</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <ul class="navbar-nav ml-auto">
                        <?php include 'notification.php'; ?>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Order Reports</h1>
                    </div>

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">Order Status</label>
                                    <select class="form-select" name="order_status">
                                        <option value="all">All</option>
                                        <?php
                                        if ($all_statuses_result && mysqli_num_rows($all_statuses_result) > 0) {
                                            while ($status = mysqli_fetch_assoc($all_statuses_result)) {
                                                $selected = $order_status == $status['order_status'] ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($status['order_status']) . "' $selected>" . 
                                                     ucfirst(str_replace('_', ' ', $status['order_status'])) . 
                                                     "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">Order Type</label>
                                    <select class="form-select" name="order_type">
                                        <option value="all" <?php echo $order_type == 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="sublimation" <?php echo $order_type == 'sublimation' ? 'selected' : ''; ?>>Sublimation</option>
                                        <option value="tailoring" <?php echo $order_type == 'tailoring' ? 'selected' : ''; ?>>Tailoring</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">Search</label>
                                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search orders...">
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Order Type</th>
                                            <th>Order Date</th>
                                            <th>Status</th>
                                            <th>Total Amount</th>
                                            <th>Staff</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_fname'] . ' ' . $order['customer_lname']); ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($order['order_type'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($order['order_status']) {
                                                            'pending' => 'warning',
                                                            'approved' => 'primary',
                                                            'in_process' => 'info',
                                                            'ready_for_pickup' => 'success',
                                                            'completed' => 'secondary',
                                                            'declined' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['staff_fname'] . ' ' . $order['staff_lname']); ?></td>
                                                <td>
                                                    <a href="view_order.php?id=<?php echo $order['order_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    
    <!-- DataTables -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#ordersTable').DataTable({
                "order": [[3, "desc"]], // Sort by order date by default
                "pageLength": 25
            });
        });
    </script>
</body>
</html>