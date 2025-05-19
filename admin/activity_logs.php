<?php
// Include database connection
include '../db.php';

// Replace the existing query with this:
$query = "SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name 
          FROM activity_logs l
          LEFT JOIN users u ON l.user_id = u.user_id
          ORDER BY l.created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Add DataTables -->
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
        .text-primary {
            color: #D98324 !important;
        }
        .footer {
            background-color: #443627 !important;
            color: #EFDCAB !important;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .table {
            margin-bottom: 0;
            border: none;
        }

        .table th {
            border: none;
            background-color: #f8f9fa;
            color: #443627;
            font-weight: 600;
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            border: none;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem;
            vertical-align: middle;
            color: #666;
            font-size: 0.9rem;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: rgba(217, 131, 36, 0.03);
            transition: all 0.2s ease;
        }

        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 8px;
        }

        /* DataTables Styling */
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 4px 24px 4px 12px;
            margin: 0 8px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 6px 12px;
            margin-left: 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: none !important;
            padding: 5px 12px;
            margin: 0 2px;
            border-radius: 6px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #D98324 !important;
            color: white !important;
            border: none;
            box-shadow: 0 2px 4px rgba(217, 131, 36, 0.2);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #443627 !important;
            color: white !important;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">
                            Activity Logs
                        </h1>
                    </div>

                    <!-- Activity Logs Table -->
                    <div class="table-container">
                        <table class="table" id="logsTable">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>User</th>
                                    <th>User Type</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($result) && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($row['log_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($row['user_type']); ?></small></td>
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
                                            <td><small class="text-muted"><?php echo htmlspecialchars($row['ip_address']); ?></small></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                        </tr>
                                <?php }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No activity logs found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

               <!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add_user.php" method="POST">
                    <div class="mb-2">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-2">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-2">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone_number" required>
                    </div>
                    <div class="mb-2">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" name="role" required>
                            <option value="Admin">Admin</option>
                            <option value="Manager">Manager</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>



                <!-- Footer -->
                <footer class="footer text-center py-3">
                    <span>Copyright &copy; JXT Tailoring and Printing Services</span>
                </footer>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#logsTable').DataTable({
            order: [[6, 'desc']],
            pageLength: 5,
            responsive: true,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            dom: '<"row align-items-center"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search logs...",
                lengthMenu: "Show _MENU_",
                info: "Showing _START_ to _END_ of _TOTAL_ logs",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            }
        });
    });
    </script>

    <!-- Delete Confirmation -->
    <script>
        function confirmDelete(userId) {
            if (confirm("Are you sure you want to delete this user?")) {
                window.location.href = "delete_user.php?id=" + userId;
            }
        }
    </script>

</body>

</html>
