<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

// Create the made-to-orders table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS made_to_orders (
    mto_id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('band_uniform', 'security_uniform', 'school_uniform', 'other') NOT NULL,
    subcategory VARCHAR(100),
    description TEXT,
    staff_id INT NOT NULL,
    labor DECIMAL(10,2),
    full_order DECIMAL(10,2),
    reference_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_id FOREIGN KEY (staff_id) REFERENCES users(user_id)
)";

// Execute the query to create the table
mysqli_query($conn, $createTableSQL);

// Fetch made-to-order items without user information for now
$sql = "SELECT * FROM made_to_orders 
        ORDER BY created_at DESC";
$mtoResult = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT Made-to-Order Management</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .sidebar {
            background: linear-gradient(180deg, #443627 0%, #2c1810 100%) !important;
        }
        .sidebar .nav-item .nav-link {
            color: #EFDCAB !important;
            transition: all 0.3s ease;
        }
        .sidebar .nav-item .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .card {
            border: none !important;
            border-radius: 15px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            background-color: #fff !important;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background-color: #D98324 !important;
            border-color: #D98324 !important;
            border-radius: 8px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #443627 !important;
            border-color: #443627 !important;
            transform: translateY(-2px);
        }        .table {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            width: 100%;
        }
        .table th {
            background-color: #f8f9fc !important;
            color: #443627 !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            max-width: 200px;
        }
        .table td.description-cell {
            max-width: 250px;
        }
        .description-text {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            cursor: pointer;
            max-width: 300px;
            line-height: 1.5;
        }
        .cost-cell {
            white-space: nowrap;
        }
        .btn-group {
            white-space: nowrap;
        }
        .btn-group .btn {
            padding: 0.375rem 0.5rem;
        }
        .img-thumbnail {
            border-radius: 8px;
            transition: transform 0.2s ease;
        }
        .img-thumbnail:hover {
            transform: scale(1.1);
        }
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        .modal-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 10px 15px;
        }
        .badge {
            padding: 0.25rem 0.4rem;
            border-radius: 4px;
            font-weight: 500;
        }

        /* Compact table styles */
        #mtoTable {
            font-size: 12px;
            width: 100% !important;
        }

        #mtoTable th {
            padding: 6px 4px;
            font-size: 10px;
            white-space: nowrap;
            background-color: #f8f9fc !important;
        }

        #mtoTable td {
            padding: 6px 4px;
            vertical-align: middle;
        }

        .table-sm td, .table-sm th {
            padding: 0.25rem 0.25rem;
        }

        .badge {
            padding: 0.25rem 0.4rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .description-text {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            cursor: pointer;
            max-width: 100%;
        }

        /* Handle DataTables styling */
        div.dataTables_wrapper {
            width: 100%;
            margin: 0 auto;
        }

        div.dataTables_wrapper div.dataTables_length {
            text-align: left;
            margin-bottom: 0.5rem;
        }

        div.dataTables_wrapper div.dataTables_length select {
            width: 60px;
            padding: 2px;
            height: 28px;
            font-size: 12px;
        }

        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
            margin-bottom: 0.5rem;
        }

        div.dataTables_wrapper div.dataTables_filter input {
            width: 120px;
            padding: 4px;
            height: 28px;
            font-size: 12px;
            margin-left: 4px;
        }

        div.dataTables_wrapper div.dataTables_info {
            font-size: 11px;
            padding-top: 0.5rem;
        }

        div.dataTables_wrapper div.dataTables_paginate {
            font-size: 11px;
            padding-top: 0.5rem;
        }

        div.dataTables_wrapper div.dataTables_paginate ul.pagination {
            margin: 0;
        }

        div.dataTables_wrapper div.dataTables_paginate ul.pagination .page-link {
            padding: 0.25rem 0.5rem;
            font-size: 11px;
        }

        .table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Message Alerts -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mx-4 mt-4" role="alert">
                        <?= htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mx-4 mt-4" role="alert">
                        <?= htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Topbar -->                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                    <ul class="navbar-nav ml-auto">
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
                        <div>
                            <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;">Made-to-Order Projects</h1>
                            <p class="text-muted mb-0">Manage custom tailoring projects and orders</p>
                        </div>
                        <button class="btn btn-primary d-flex align-items-center gap-2" id="addMTOButton">
                            <i class="fas fa-plus"></i> New MTO Project
                        </button>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle compact" id="mtoTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 3%">#</th>
                                            <th style="width: 10%">Staff</th>
                                            <th style="width: 35%">Project Details</th>
                                            <th style="width: 12%">Costing</th>
                                            <th style="width: 8%">Ref</th>
                                            <th style="width: 6%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $mtoResult->fetch_assoc()): 
                                            $mto_id = $row['mto_id'];
                                            $reference_image = $row['reference_image'];
                                            $total_cost = $row['labor'] + $row['full_order'];
                                        ?>
                                        <tr>
                                            <td><?= $mto_id; ?></td>
                                            <td>
                                                <span class="badge text-bg-primary">
                                                    <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                                </span>
                                                <small><?= substr($row['first_name'], 0, 5); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-info text-white" style="font-size: 9px; padding: 2px 4px;">
                                                            <?= ucfirst(str_replace('_', ' ', $row['category'])); ?>
                                                        </span>
                                                        <span class="fw-bold ms-1" style="font-size: 11px;"><?= substr($row['subcategory'], 0, 15); ?></span>
                                                    </div>
                                                    <div class="description-text" 
                                                         data-bs-toggle="tooltip" 
                                                         title="<?= htmlspecialchars($row['description']); ?>"
                                                         style="font-size: 10px; line-height: 1.2;">
                                                        <?= substr($row['description'], 0, 40) . (strlen($row['description']) > 40 ? '...' : ''); ?>
                                                    </div>
                                                    <div style="font-size: 9px; color: #6c757d;">
                                                        <i class="fas fa-clock" style="font-size: 8px;"></i>
                                                        <?= date('m/d/y', strtotime($row['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 10px;">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Labor:</span>
                                                        <span>₱<?= number_format($row['labor'], 0); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Mat'l:</span>
                                                        <span>₱<?= number_format($row['full_order'], 0); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between fw-bold text-primary" style="font-size: 11px;">
                                                        <span>Tot:</span>
                                                        <span>₱<?= number_format($total_cost, 0); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($reference_image): ?>
                                                    <img src="../<?= $reference_image; ?>" 
                                                         class="img-thumbnail cursor-pointer" 
                                                         style="width: 30px; height: 30px; object-fit: cover;"
                                                         onclick="viewImage('../<?= $reference_image; ?>')" 
                                                         data-bs-toggle="tooltip" 
                                                         title="Click to view">
                                                <?php else: ?>
                                                    <span class="badge bg-secondary" style="font-size: 9px;">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex" style="gap: 2px">
                                                    <button class="btn btn-sm btn-outline-primary p-1" 
                                                            style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;"
                                                            onclick="viewMTO(<?= $mto_id; ?>)"
                                                            data-bs-toggle="tooltip"
                                                            title="View">
                                                        <i class="fas fa-eye" style="font-size: 10px;"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning p-1" 
                                                            style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;"
                                                            onclick="editMTO(<?= $mto_id; ?>)"
                                                            data-bs-toggle="tooltip"
                                                            title="Edit">
                                                        <i class="fas fa-edit" style="font-size: 10px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add MTO Modal -->
                <div class="modal fade" id="addMTOModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Made-to-Order Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="add_mto.php" method="POST" enctype="multipart/form-data" id="mtoForm">
                                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                                <select name="category" class="form-select" required>
                                                    <option value="">Select Category</option>
                                                    <option value="band_uniform">Band Uniform</option>
                                                    <option value="school_uniform">School Uniform</option>
                                                    <option value="security_uniform">Security Uniform</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label">Subcategory</label>
                                                <input type="text" name="subcategory" class="form-control" 
                                                       placeholder="e.g., PE Uniform, Class Uniform">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea name="description" class="form-control" rows="4" required 
                                                placeholder="Enter detailed project specifications, measurements, and requirements"></textarea>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Labor Cost <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" name="labor" class="form-control" required min="0" step="0.01"
                                                           placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Materials Cost <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" name="full_order" class="form-control" required min="0" step="0.01"
                                                           placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>                                    <div class="form-group mb-3">
                                        <label class="form-label">Reference Image <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="file" name="images[]" class="form-control" accept="image/*" required
                                                   id="imageInput">
                                        </div>
                                        <div id="imagePreview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                        <small class="text-muted">Supported formats: JPG, PNG</small>
                                    </div>

                                    <div class="modal-footer px-0 pb-0">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Project
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>                <!-- Image Viewer Modal -->
                <div class="modal fade" id="imageViewerModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reference Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-0">
                                <img id="modalImage" src="" class="img-fluid" style="max-height: 80vh; width: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Required Scripts -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

                <script>
                    $(document).ready(function() {                        // Initialize DataTable
                        $('#mtoTable').DataTable({
                            order: [[0, 'desc']],
                            pageLength: 15,
                            lengthMenu: [10, 15, 25, 50],
                            language: {
                                search: "",
                                searchPlaceholder: "Search...",
                                lengthMenu: "_MENU_",
                                info: "_START_-_END_ of _TOTAL_",
                                infoEmpty: "No records",
                                infoFiltered: "(filtered)"
                            },
                            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
                            scrollCollapse: true,
                            autoWidth: false,
                            responsive: true,
                            columnDefs: [
                                { orderable: false, targets: [4, 5] },
                                { width: '3%', targets: 0 },
                                { width: '10%', targets: 1 },
                                { width: '35%', targets: 2 },
                                { width: '12%', targets: 3 },
                                { width: '8%', targets: 4 },
                                { width: '6%', targets: 5 }
                            ],
                            fixedColumns: true
                        });

                        // Initialize tooltips
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl)
                        });

                        // Form validation
                        $("#mtoForm").on('submit', function(e) {
                            let isValid = true;
                            const form = $(this);

                            // Clear previous error styling
                            form.find('.is-invalid').removeClass('is-invalid');
                            form.find('.invalid-feedback').remove();

                            // Validate category
                            if (!form.find('[name="category"]').val()) {
                                isValid = false;
                                form.find('[name="category"]')
                                    .addClass('is-invalid')
                                    .after('<div class="invalid-feedback">Please select a category</div>');
                            }

                            // Validate description
                            if (!form.find('[name="description"]').val().trim()) {
                                isValid = false;
                                form.find('[name="description"]')
                                    .addClass('is-invalid')
                                    .after('<div class="invalid-feedback">Please enter a description</div>');
                            }

                            // Validate costs
                            const labor = parseFloat(form.find('[name="labor"]').val());
                            const materials = parseFloat(form.find('[name="full_order"]').val());

                            if (isNaN(labor) || labor < 0) {
                                isValid = false;
                                form.find('[name="labor"]')
                                    .addClass('is-invalid')
                                    .after('<div class="invalid-feedback">Please enter a valid labor cost</div>');
                            }

                            if (isNaN(materials) || materials < 0) {
                                isValid = false;
                                form.find('[name="full_order"]')
                                    .addClass('is-invalid')
                                    .after('<div class="invalid-feedback">Please enter a valid materials cost</div>');
                            }

                            // Validate images
                            const images = form.find('[name="images[]"]')[0].files;
                            if (images.length === 0) {
                                isValid = false;
                                form.find('[name="images[]"]')
                                    .addClass('is-invalid')
                                    .after('<div class="invalid-feedback">Please select at least one image</div>');
                            } else {
                                // Check file types
                                const allowedTypes = ['image/jpeg', 'image/png'];
                                for (let i = 0; i < images.length; i++) {
                                    if (!allowedTypes.includes(images[i].type)) {
                                        isValid = false;
                                        form.find('[name="images[]"]')
                                            .addClass('is-invalid')
                                            .after('<div class="invalid-feedback">Only JPG and PNG files are allowed</div>');
                                        break;
                                    }
                                }
                            }

                            if (!isValid) {
                                e.preventDefault();
                            }
                        });

                        $("#addMTOButton").click(function() {
                            var addModal = new bootstrap.Modal(document.getElementById("addMTOModal"));
                            addModal.show();
                        });

                        // Image preview functionality
                        $('#imageInput').on('change', function() {
                            const preview = $('#imagePreview');
                            preview.html('');
                            
                            if (this.files) {
                                [...this.files].forEach(file => {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        preview.append(`
                                            <div class="position-relative">
                                                <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                            </div>
                                        `);
                                    }
                                    reader.readAsDataURL(file);
                                });
                            }
                        });
                    });

                    function editMTO(mtoId) {
                        window.location.href = "edit_mto.php?id=" + mtoId;
                    }

                    function viewMTO(mtoId) {
                        window.location.href = "view_mto.php?id=" + mtoId;
                    }

                    function viewImage(imagePath) {
                        document.getElementById('modalImage').src = imagePath;
                        var imageModal = new bootstrap.Modal(document.getElementById('imageViewerModal'));
                        imageModal.show();
                    }
                </script>
            </div>
        </div>
    </div>
</body>
</html>