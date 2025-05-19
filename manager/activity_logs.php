<?php
session_start();
include '../db.php';

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch activity logs with user information
$query = "SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
          FROM activity_logs l 
          LEFT JOIN users u ON l.user_id = u.user_id 
          ORDER BY l.created_at DESC";
$result = mysqli_query($conn, $query);
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
    <title>JXT Sublimator</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #443627;
        }

        .sidebar .nav-item .nav-link {
            color: #EFDCAB !important;
        }

        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: #443627 !important;
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

        .text-primary {
            color: #D98324 !important;
        }

        .text-gray-800 {
            color: #443627 !important;
        }

        .footer {
            background-color: #443627 !important;
            color: #EFDCAB !important;
        }

        /* Add these to your existing styles */
        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #443627;
            color: #EFDCAB;
            border-bottom: 2px solid #D98324;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 6px;
        }

        .table tbody tr:hover {
            background-color: rgba(217, 131, 36, 0.05);
            transition: all 0.2s ease;
        }

        /* Minimal Table Styles */
        .table-responsive {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .table {
            margin: 0;
            border: none;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #443627;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            border: none;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border: none;
            font-size: 0.85rem;
            color: #555;
        }

        .table tbody tr {
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Minimal Badge Design */
        .badge {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
        }

        /* Minimal DataTables Design */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin: 15px 0;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: none !important;
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 4px;
            background: transparent !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f8f9fa !important;
            border: none !important;
            color: #443627 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #f8f9fa !important;
            border: none !important;
            color: #443627 !important;
            font-weight: 600;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <?php include 'notifications.php'; ?>

                        <!-- User Info -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                    <i class='fas fa-user-circle' style="font-size:20px; margin-left: 10px;"></i>

                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Activity Logs</h1>
                    </div>

                    <!-- Activity Logs Table -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="activityTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>IP Address</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($result) && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($row['log_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['user_type']); ?></span></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo match($row['action_type']) {
                                                                'LOGIN' => 'bg-success',
                                                                'LOGOUT' => 'bg-secondary',
                                                                'CREATE' => 'bg-primary',
                                                                'UPDATE' => 'bg-warning',
                                                                'DELETE' => 'bg-danger',
                                                                default => 'bg-info'
                                                            };
                                                        ?>">
                                                            <?php echo htmlspecialchars($row['action_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                    <td><small><?php echo htmlspecialchars($row['ip_address']); ?></small></td>
                                                    <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr><td colspan="7" class="text-center">No activity logs found</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->

                <!-- Footer -->
                <footer class="footer text-center py-3">
                    <span>Copyright &copy; JXT Tailoring and Printing Services</span>
                </footer>
                <!-- End of Footer -->
            </div>
            <!-- End of Content Wrapper -->
        </div>
    </div>
    <!-- End of Page Wrapper -->

    <!-- DataTables Scripts -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

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
        });
    </script>
</body>
</html>
