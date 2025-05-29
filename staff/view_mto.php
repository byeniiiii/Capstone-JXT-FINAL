<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: custom_orders.php?error=No project ID provided");
    exit();
}

$mto_id = intval($_GET['id']);

// Fetch the MTO project with staff information
$sql = "SELECT m.*, u.first_name, u.last_name 
        FROM made_to_orders m 
        LEFT JOIN users u ON m.staff_id = u.user_id 
        WHERE m.mto_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: custom_orders.php?error=Project not found");
    exit();
}

$mto = $result->fetch_assoc();
$total_cost = $mto['labor'] + $mto['full_order'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>View Made-to-Order Project</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #D98324;
            border-color: #D98324;
        }
        .btn-primary:hover {
            background-color: #443627;
            border-color: #443627;
        }
        .project-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .detail-label {
            font-weight: 600;
            color: #443627;
        }
        .badge {
            padding: 0.4rem 0.6rem;
            font-weight: 500;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid mt-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Project Details</h1>
                        <div>
                            <a href="edit_mto.php?id=<?= $mto_id; ?>" class="btn btn-warning me-2">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="custom_orders.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Project Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="detail-label">Category:</p>
                                            <p>
                                                <span class="badge bg-info">
                                                    <?= ucfirst(str_replace('_', ' ', $mto['category'])); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="detail-label">Subcategory:</p>
                                            <p><?= htmlspecialchars($mto['subcategory'] ?: 'Not specified'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="detail-label">Description:</p>
                                        <div class="p-3 bg-light rounded">
                                            <?= nl2br(htmlspecialchars($mto['description'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <p class="detail-label">Labor Cost:</p>
                                            <p class="text-primary fw-bold">₱<?= number_format($mto['labor'], 2); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="detail-label">Materials Cost:</p>
                                            <p class="text-primary fw-bold">₱<?= number_format($mto['full_order'], 2); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="detail-label">Total Cost:</p>
                                            <p class="text-success fw-bold">₱<?= number_format($total_cost, 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="detail-label">Created By:</p>
                                            <p>
                                                <?= htmlspecialchars($mto['first_name'] . ' ' . $mto['last_name']); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="detail-label">Created On:</p>
                                            <p><?= date('F j, Y g:i A', strtotime($mto['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Reference Image</h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php if (!empty($mto['reference_image'])): ?>
                                        <img src="../<?= $mto['reference_image']; ?>" class="project-image img-fluid" 
                                             alt="Project Reference" onclick="viewFullImage('../<?= $mto['reference_image']; ?>')">
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewFullImage('../<?= $mto['reference_image']; ?>')">
                                                <i class="fas fa-search-plus"></i> View Full Size
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center p-5 bg-light rounded">
                                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                            <p class="mb-0">No reference image available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
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

    <script>
        function viewFullImage(imagePath) {
            document.getElementById('modalImage').src = imagePath;
            var imageModal = new bootstrap.Modal(document.getElementById('imageViewerModal'));
            imageModal.show();
        }
    </script>
</body>
</html> 