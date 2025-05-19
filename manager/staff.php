<?php
session_start();
include '../db.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch all staff members
$staff_query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role, u.phone_number, u.created_at,
                (SELECT COUNT(*) FROM orders WHERE assigned_to = u.user_id) AS total_orders,
                (SELECT COUNT(*) FROM orders WHERE assigned_to = u.user_id AND order_status = 'completed') AS completed_orders,
                (SELECT COUNT(*) FROM orders WHERE assigned_to = u.user_id AND order_status = 'in_process') AS ongoing_orders
                FROM users u
                WHERE u.role IN ('Staff', 'Manager')
                ORDER BY u.role, u.first_name";

$staff_result = $conn->query($staff_query);

// Fetch order statistics summary
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN order_status = 'in_process' THEN 1 ELSE 0 END) as ongoing_orders,
                COUNT(DISTINCT assigned_to) as active_staff
                FROM orders";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get top performing staff based on completed orders
$top_staff_query = "SELECT u.user_id, u.first_name, u.last_name, 
                    COUNT(*) AS completed_count
                    FROM orders o
                    JOIN users u ON o.assigned_to = u.user_id
                    WHERE o.order_status = 'completed'
                    GROUP BY u.user_id
                    ORDER BY completed_count DESC
                    LIMIT 5";

$top_staff_result = $conn->query($top_staff_query);
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
    <title>JXT Sublimator - Staff Management</title>

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

        :root {
            --primary: #D98324;
            --primary-dark: #b46c1e;
            --secondary: #443627;
            --light: #EFDCAB;
            --light-gray: #f8f9fc;
            --gray: #858796;
            --white: #fff;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
        }

        /* Card styling */
        .card {
            border: none !important;
            border-radius: 10px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
            transition: transform 0.2s ease;
            background-color: #EFDCAB !important;
            color: #443627 !important;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .card-header {
            background-color: var(--white) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
            padding: 1.25rem 1.5rem !important;
        }
        
        .card-body {
            padding: 1.5rem !important;
        }
        
        /* Table styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin-bottom: 0;
        }

        .table thead th {
            border-top: none !important;
            border-bottom: 2px solid #eaecf0 !important;
            font-weight: 600;
            color: var(--secondary);
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 1rem !important;
            vertical-align: middle;
            border-top: 1px solid #eaecf0;
            color: #4a5568;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(239, 220, 171, 0.1);
        }
        
        /* Status badges */
        .staff-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 6px;
            text-transform: uppercase;
            display: inline-block;
            min-width: 65px;
            text-align: center;
        }

        .staff-admin {
            background-color: #fecdd3;
            color: #9d174d;
        }

        .staff-manager {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .staff-staff {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        /* Stats cards */
        .stat-card {
            border-left: 5px solid var(--primary) !important;
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            border-radius: 8px;
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .stat-card .icon {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .stat-card .stat-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--gray);
            text-transform: uppercase;
        }
        
        /* Progress indicators */
        .progress {
            height: 0.6rem;
            border-radius: 1rem;
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            background-color: var(--primary);
        }
        
        /* Button styling */
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: 6px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(217, 131, 36, 0.3);
        }
        
        .btn-info {
            background-color: var(--info) !important;
            border-color: var(--info) !important;
            color: white !important;
        }

        .btn-sm {
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .card {
                margin-bottom: 1.5rem;
            }
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
                        <h1 class="h3 mb-0 text-gray-800">Staff Management</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                            <i class="fas fa-user-plus fa-sm text-white-50 mr-1"></i> Add New Staff
                        </a>
                    </div>

                    <!-- Content Row - Stats Cards -->
                    <div class="row">
                        <!-- Total Staff Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-value"><?php echo $staff_result->num_rows; ?></div>
                                        <div class="stat-label">Staff Members</div>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Staff Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-value"><?php echo $stats['active_staff']; ?></div>
                                        <div class="stat-label">Active Staff</div>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Orders Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                                        <div class="stat-label">Total Orders</div>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Completed Orders Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                                        <div class="stat-label">Completed Orders</div>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <?php if($stats['total_orders'] > 0): ?>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo ($stats['completed_orders'] / $stats['total_orders']) * 100; ?>%" 
                                         aria-valuenow="<?php echo ($stats['completed_orders'] / $stats['total_orders']) * 100; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted mt-1 d-block">
                                    <?php echo round(($stats['completed_orders'] / $stats['total_orders']) * 100); ?>% completion rate
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Staff List Table -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-users mr-2"></i>Staff Members
                                    </h6>
                                </div>
                                <div class="card-body px-0 py-3">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Role</th>
                                                    <th>Contact</th>
                                                    <th>Total Orders</th>
                                                    <th>Completed</th>
                                                    <th>Performance</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($staff_result->num_rows > 0): ?>
                                                    <?php while ($staff = $staff_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar mr-2">
                                                                        <div class="avatar-initial rounded-circle bg-light">
                                                                            <?php echo substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                $role_class = '';
                                                                switch ($staff['role']) {
                                                                    case 'Admin':
                                                                        $role_class = 'staff-admin';
                                                                        break;
                                                                    case 'Manager':
                                                                        $role_class = 'staff-manager';
                                                                        break;
                                                                    case 'Staff':
                                                                        $role_class = 'staff-staff';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="staff-badge <?php echo $role_class; ?>">
                                                                    <?php echo $staff['role']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div><?php echo htmlspecialchars($staff['email']); ?></div>
                                                                <div class="small text-muted"><?php echo htmlspecialchars($staff['phone']); ?></div>
                                                            </td>
                                                            <td><?php echo $staff['total_orders']; ?></td>
                                                            <td><?php echo $staff['completed_orders']; ?></td>
                                                            <td>
                                                                <?php if($staff['total_orders'] > 0): ?>
                                                                <div class="progress">
                                                                    <div class="progress-bar" role="progressbar" 
                                                                         style="width: <?php echo ($staff['completed_orders'] / $staff['total_orders']) * 100; ?>%" 
                                                                         aria-valuenow="<?php echo ($staff['completed_orders'] / $staff['total_orders']) * 100; ?>" 
                                                                         aria-valuemin="0" 
                                                                         aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted">
                                                                    <?php echo round(($staff['completed_orders'] / $staff['total_orders']) * 100); ?>%
                                                                </small>
                                                                <?php else: ?>
                                                                <small class="text-muted">No orders yet</small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <a href="staff_details.php?id=<?php echo $staff['user_id']; ?>" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-4">No staff members found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Performers Card -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-award mr-2"></i>Top Performers
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($top_staff_result->num_rows > 0): ?>
                                        <div class="staff-rankings">
                                            <?php 
                                            $rank = 1;
                                            while ($top = $top_staff_result->fetch_assoc()): 
                                            ?>
                                                <div class="staff-rank-item d-flex align-items-center mb-3 p-2 <?php echo ($rank == 1) ? 'bg-light rounded' : ''; ?>">
                                                    <div class="rank-badge mr-3 <?php echo ($rank <= 3) ? 'text-primary' : 'text-muted'; ?>">
                                                        <?php if ($rank == 1): ?>
                                                            <i class="fas fa-trophy fa-2x"></i>
                                                        <?php else: ?>
                                                            <span class="font-weight-bold">#<?php echo $rank; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($top['first_name'] . ' ' . $top['last_name']); ?></h6>
                                                        <small class="text-muted"><?php echo $top['completed_count']; ?> orders completed</small>
                                                    </div>
                                                </div>
                                            <?php 
                                                $rank++;
                                                endwhile; 
                                            ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="mb-3">
                                                <i class="fas fa-chart-bar fa-3x text-muted"></i>
                                            </div>
                                            <h6 class="text-muted">No performance data available yet</h6>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Orders per Staff Chart -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-pie mr-2"></i>Order Distribution
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:250px;">
                                        <canvas id="orderDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
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
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            // Chart.js implementation
            const ctx = document.getElementById('orderDistributionChart').getContext('2d');
            
            // Prepare data for chart
            const staffData = [
                <?php 
                mysqli_data_seek($staff_result, 0);
                while ($staff = $staff_result->fetch_assoc()) {
                    echo "{";
                    echo "name: '" . $staff['first_name'] . "',";
                    echo "total: " . $staff['total_orders'] . ",";
                    echo "completed: " . $staff['completed_orders'] . ",";
                    echo "ongoing: " . $staff['ongoing_orders'];
                    echo "},";
                }
                ?>
            ];
            
            const labels = staffData.map(staff => staff.name);
            const completedData = staffData.map(staff => staff.completed);
            const ongoingData = staffData.map(staff => staff.ongoing);
            
            const orderChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Completed Orders',
                            data: completedData,
                            backgroundColor: '#1cc88a',
                            borderColor: '#1cc88a',
                            borderWidth: 1
                        },
                        {
                            label: 'Ongoing Orders',
                            data: ongoingData,
                            backgroundColor: '#36b9cc',
                            borderColor: '#36b9cc',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>