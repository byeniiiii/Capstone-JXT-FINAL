<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

// Fetch only Sublimators from the database
$query = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS fullname, username, phone_number, role 
        FROM users 
        WHERE role = 'Sublimator'";

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

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">
                            Manage Templates
                        </h1>
                        <button class="btn btn-primary mb-3" id="addTemplateButton">Add New Template</button>
                    </div>

                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Template ID</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $templateResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['template_id']; ?></td>
                                    <td><?= $row['name']; ?></td>
                                    <td><img src="<?= $row['image_path']; ?>" width="80" height="80"></td>
                                    <td><?= $row['price']; ?></td>
                                    <td><?= $row['category']; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?= $row['template_id']; ?>" onclick="editTemplate(<?= $row['template_id']; ?>)">Edit</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add Template Modal -->
                <div class="modal fade" id="addTemplateModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Template</h5>
                                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form action="add_template.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Template Name</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Image</label>
                                        <input type="file" name="image" class="form-control-file" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Price</label>
                                        <input type="number" name="price" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" name="category" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Template</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bootstrap 5 JavaScript -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

                <!-- jQuery -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                <script>
                    $(document).ready(function() {
                        $("#addTemplateButton").click(function() {
                            var addModal = new bootstrap.Modal(document.getElementById("addTemplateModal"));
                            addModal.show();
                        });
                    });
                </script>

                <script>
                    function editTemplate(templateId) {
                        window.location.href = "edit_template.php?id=" + templateId;
                    }

                    function deleteTemplate(templateId) {
                        if (confirm("Are you sure you want to delete this template?")) {
                            $.ajax({
                                url: 'delete_template.php',
                                type: 'POST',
                                data: { id: templateId },
                                success: function(response) {
                                    if (response === "success") {
                                        alert("Template deleted successfully!");
                                        location.reload();
                                    } else {
                                        alert("Error deleting template: " + response);
                                    }
                                }
                            });
                        }
                    }
                </script>

            </div>
        </div>
    </div>
</body>

</html>