<?php
include '../db.php';
session_start();

// Fetch sublimation orders from the database
$query = "SELECT 
            o.order_id, 
            CONCAT(c.first_name, ' ', c.last_name) AS customer_name, 
            o.created_at AS order_date,  
            s.printing_type,   
            IF(s.custom_design = 1, 'Custom Design', 'Template') AS design_option,
            o.payment_status, 
            o.order_status AS status
          FROM orders o
          JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN sublimation_orders s ON o.order_id = s.order_id
          WHERE o.order_type = 'sublimation'";

$result = mysqli_query($conn, $query);

// Check if query execution was successful
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
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body, html {
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevents horizontal scrolling */
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
            width: 100%;
            background-color: #443627 !important;
            color: #EFDCAB !important;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
        }

        .container-fluid {
            max-width: 100%;
            padding-right: 0 !important;
            padding-left: 0 !important;
        }

        * {
            box-sizing: border-box;
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

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <?php include 'notifications.php'; ?>

                        <!-- User Info -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                    <i class='fas fa-user-circle' style="font-size:20px; margin-left: 10px;"></i>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">
                            Manage Sublimation Orders
                        </h1>
                    </div>

                    <!-- Orders Table -->
                    <div class="container">
                        <table class="table table-bordered" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Order Date</th>
                                    <th>Printing Type</th>
                                    <th>Design Option</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($result) && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['printing_type']); ?></td>
                                            <td><?php echo htmlspecialchars($row['design_option']); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <td>
                                                <a href="view_order.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-info btn-sm">View</a>
                                                <a href="edit_order.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['order_id']; ?>)">Delete</button>
                                            </td>
                                        </tr>
                                <?php }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No sublimation orders found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <?php include 'footer.php';?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Delete Confirmation -->
    <script>
        function confirmDelete(orderId) {
            if (confirm("Are you sure you want to delete this order?")) {
                window.location.href = "delete_order.php?order_id=" + orderId;
            }
        }
    </script>

</body>

</html>
