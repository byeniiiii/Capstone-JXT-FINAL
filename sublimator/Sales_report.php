<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Update the query to match your actual database structure
$query = "SELECT so.*, 
          o.order_status, o.created_at, o.total_amount,
          CONCAT(u.first_name, ' ', u.last_name) as sublimator_name,
          t.name as template_name
          FROM sublimation_orders so
          LEFT JOIN orders o ON so.order_id = o.order_id
          LEFT JOIN users u ON so.sublimator_id = u.user_id
          LEFT JOIN templates t ON so.template_id = t.template_id
          ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

// Fetch templates
$sql = "SELECT * FROM templates";
$templateResult = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT Sublimator</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F2F6D0;
        }
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .sidebar {
            background-color: #443627 !important;
        }
        .sidebar .nav-item .nav-link {
            color: #EFDCAB !important;
        }
        .card {
            border-left: 5px solid #D98324 !important;
            background-color: #EFDCAB !important;
            color: #443627 !important;
        }
        .btn-primary {
            background-color: #D98324 !important;
            border-color: #D98324 !important;
        }
        .btn-primary:hover {
            background-color: #443627 !important;
            border-color: #443627 !important;
        }
        .footer {
            background-color: #443627 !important;
            color: #EFDCAB !important;
        }
        .table {
            background-color: white;
        }
        .table th {
            background-color: #fff !important;
            color: #000 !important;
            font-weight: bold;
        }

        /* Activity Logs Table Styles */
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 1rem;
        }

        .table thead th {
            background-color: #443627 !important;
            color: #EFDCAB !important;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
        }

        .table td {
            vertical-align: middle;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .badge {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 6px;
        }

        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #443627;
            border-radius: 6px;
            padding: 4px 8px;
        }

        /* Sales Report Table Styles */
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 1rem;
        }

        .table thead th {
            background-color: #443627 !important;
            color: #EFDCAB !important;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
        }

        .table td {
            vertical-align: middle;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.85rem;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .btn-outline-primary {
            color: #D98324;
            border-color: #D98324;
        }

        .btn-outline-primary:hover {
            background-color: #D98324;
            color: white;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                    <ul class="navbar-nav ml-auto">
                        <?php include 'notifications.php'; ?>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                    <i class='fas fa-user-circle' style="font-size:20px; margin-left: 10px;"></i>
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Sales Report</h1>
                    </div>

                    <!-- Sales Report Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Sublimation Orders Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="salesTable">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Template</th>
                                            <th>Design</th>
                                            <th>Details</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Sublimator</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($result) && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($row['order_id']); ?></td>
                                                    <td>
                                                        <?php if ($row['template_id']): ?>
                                                            <?php echo htmlspecialchars($row['template_name']); ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-info">Custom Design</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['design_path']): ?>
                                                            <button class="btn btn-sm btn-outline-primary view-design" 
                                                                    data-path="<?php echo htmlspecialchars($row['design_path']); ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            Size: <?php echo htmlspecialchars($row['size']); ?><br>
                                                            Color: <?php echo htmlspecialchars($row['color']); ?><br>
                                                            Qty: <?php echo htmlspecialchars($row['quantity']); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo match($row['order_status']) {
                                                                'Pending' => 'bg-warning',
                                                                'In Progress' => 'bg-info',
                                                                'Completed' => 'bg-success',
                                                                'Cancelled' => 'bg-danger',
                                                                default => 'bg-secondary'
                                                            };
                                                        ?>">
                                                            <?php echo htmlspecialchars($row['order_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($row['sublimator_name']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info view-details" 
                                                                data-id="<?php echo $row['sublimation_id']; ?>">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr><td colspan="9" class="text-center">No sublimation orders found</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bootstrap 5 JavaScript -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

                <!-- jQuery -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                <!-- DataTables -->
                <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

                <script>
                    $(document).ready(function() {
                        $('#activityTable').DataTable({
                            order: [[6, 'desc']],
                            pageLength: 7,
                            responsive: true,
                            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                            language: {
                                search: "",
                                searchPlaceholder: "Search logs...",
                                lengthMenu: "Show _MENU_",
                                info: "_START_ - _END_ of _TOTAL_",
                                paginate: {
                                    first: '«',
                                    previous: '‹',
                                    next: '›',
                                    last: '»'
                                }
                            }
                        });

                        $('#salesTable').DataTable({
                            order: [[7, 'desc']], // Sort by date by default
                            pageLength: 10,
                            responsive: true,
                            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                            language: {
                                search: "",
                                searchPlaceholder: "Search orders...",
                                lengthMenu: "Show _MENU_ entries",
                                info: "_START_ - _END_ of _TOTAL_ orders",
                            }
                        });

                        // View design image
                        $('.view-design').click(function() {
                            const designPath = $(this).data('path');
                            // Add your image preview logic here
                        });

                        // View order details
                        $('.view-details').click(function() {
                            const sublimationId = $(this).data('id');
                            // Add your details view logic here
                        });
                    });
                </script>

            </div>
        </div>
    </div>
</body>

</html>