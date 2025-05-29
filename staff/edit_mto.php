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

// Fetch the MTO project
$sql = "SELECT * FROM made_to_orders WHERE mto_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: custom_orders.php?error=Project not found");
    exit();
}

$mto = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $subcategory = mysqli_real_escape_string($conn, $_POST['subcategory']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $labor = floatval($_POST['labor']);
    $full_order = floatval($_POST['full_order']);
    
    // Handle image upload if a new one is provided
    $reference_image = $mto['reference_image']; // Keep existing image by default
    
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] === 0) {
        $upload_dir = '../uploads/mto/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['images']['name'][0];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['images']['tmp_name'][0], $upload_path)) {
            // Delete old image if exists
            if (!empty($mto['reference_image']) && file_exists('../' . $mto['reference_image'])) {
                unlink('../' . $mto['reference_image']);
            }
            $reference_image = 'uploads/mto/' . $file_name;
        }
    }
    
    // Update database
    $sql = "UPDATE made_to_orders SET 
            category = ?, 
            subcategory = ?, 
            description = ?, 
            labor = ?, 
            full_order = ?, 
            reference_image = ? 
            WHERE mto_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdssi", $category, $subcategory, $description, $labor, $full_order, $reference_image, $mto_id);
    
    if ($stmt->execute()) {
        header("Location: custom_orders.php?success=Project updated successfully");
        exit();
    } else {
        header("Location: edit_mto.php?id=$mto_id&error=Failed to update project: " . $conn->error);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>Edit Made-to-Order Project</title>

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

                <div class="container-fluid mt-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Made-to-Order Project</h1>
                        <a href="custom_orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Projects
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="edit_mto.php?id=<?= $mto_id; ?>" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                            <select name="category" class="form-select" required>
                                                <option value="">Select Category</option>
                                                <option value="band_uniform" <?= ($mto['category'] == 'band_uniform') ? 'selected' : ''; ?>>Band Uniform</option>
                                                <option value="school_uniform" <?= ($mto['category'] == 'school_uniform') ? 'selected' : ''; ?>>School Uniform</option>
                                                <option value="security_uniform" <?= ($mto['category'] == 'security_uniform') ? 'selected' : ''; ?>>Security Uniform</option>
                                                <option value="other" <?= ($mto['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Subcategory</label>
                                            <input type="text" name="subcategory" class="form-control" 
                                                   value="<?= htmlspecialchars($mto['subcategory']); ?>"
                                                   placeholder="e.g., PE Uniform, Class Uniform">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" required 
                                              placeholder="Enter detailed project specifications, measurements, and requirements"><?= htmlspecialchars($mto['description']); ?></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Labor Cost <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" name="labor" class="form-control" required min="0" step="0.01"
                                                       value="<?= $mto['labor']; ?>" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Materials Cost <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" name="full_order" class="form-control" required min="0" step="0.01"
                                                       value="<?= $mto['full_order']; ?>" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Reference Image</label>
                                    <div class="input-group">
                                        <input type="file" name="images[]" class="form-control" accept="image/*"
                                               id="imageInput">
                                    </div>
                                    <?php if (!empty($mto['reference_image'])): ?>
                                    <div class="mt-2">
                                        <p>Current Image:</p>
                                        <img src="../<?= $mto['reference_image']; ?>" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                    <?php endif; ?>
                                    <div id="imagePreview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                    <small class="text-muted">Leave empty to keep current image. Supported formats: JPG, PNG</small>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <a href="custom_orders.php" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Project
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
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
    </script>
</body>
</html>
