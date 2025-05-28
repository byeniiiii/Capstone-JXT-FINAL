<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // Adjust path as needed
    exit();
}
?>

<?php
// Include database connection
include '../db.php';

// Fetch only Sublimators from the database
$query = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS fullname, username, phone_number, role 
          FROM users 
          WHERE role = 'Sublimator'";

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

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

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
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
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
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">
                            Manage Sublimators
                        </h1>
                        <!-- Add Sublimator Button -->
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            Add Sublimator
                        </button>
                    </div>

                    <!-- Sublimators Table -->
                    <div class="container">
                        <table class="table table-bordered" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Phone Number</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($result) && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                                            <td>
                                                <a href="edit_sublimator.php?id=<?php echo $row['user_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                            </td>
                                        </tr>
                                <?php }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No sublimators found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Sublimator Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addUserModalLabel">Add Sublimator</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="add_sublimator.php" method="POST">
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
                                            <option value="Sublimator">Sublimator</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Add Sublimator</button>
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

    <!-- Delete Confirmation -->
    <script>
        function confirmDelete(userId) {
            if (confirm("Are you sure you want to delete this sublimator?")) {
                window.location.href = "delete_sublimator.php?id=" + userId;
            }
        }
    </script>

</body>

</html>
