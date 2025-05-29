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
<<<<<<< HEAD
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
=======
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: custom_orders.php?error=Invalid request");
    exit();
}

$mto_id = $_GET['id'];

// Fetch the MTO record with user information
$sql = "SELECT m.*, u.first_name, u.last_name 
        FROM made_to_order m 
        LEFT JOIN users u ON m.user_id = u.user_id 
        WHERE m.mto_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $mto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $mto = $row;
} else {
    header("Location: custom_orders.php?error=Project not found");
    exit();
}
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
<<<<<<< HEAD
    <title>Edit Made-to-Order Project</title>
=======
    <title>Edit MTO Project - JXT Tailoring</title>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
<<<<<<< HEAD
    <!-- FontAwesome -->
=======
    <!-- FontAwesome & Custom Fonts -->
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
<<<<<<< HEAD
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
=======
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
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 10px 15px;
        }
        .img-thumbnail {
            border-radius: 8px;
            transition: transform 0.2s ease;
            max-width: 200px;
        }
        .img-thumbnail:hover {
            transform: scale(1.05);
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
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

<<<<<<< HEAD
                <div class="container-fluid mt-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Made-to-Order Project</h1>
                        <a href="custom_orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Projects
=======
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mx-4 mt-4" role="alert">
                        <?= htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
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
                            <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;">Edit MTO Project</h1>
                            <p class="text-muted mb-0">Update project details and specifications</p>
                        </div>
                        <a href="custom_orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Projects
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
<<<<<<< HEAD
                            <form action="edit_mto.php?id=<?= $mto_id; ?>" method="POST" enctype="multipart/form-data">
                                <div class="row">
=======
                            <form action="update_mto.php" method="POST" enctype="multipart/form-data" id="editMtoForm">
                                <input type="hidden" name="mto_id" value="<?= $mto['mto_id']; ?>">
                                
                                <div class="row mb-4">
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                            <select name="category" class="form-select" required>
<<<<<<< HEAD
                                                <option value="">Select Category</option>
                                                <option value="band_uniform" <?= ($mto['category'] == 'band_uniform') ? 'selected' : ''; ?>>Band Uniform</option>
                                                <option value="school_uniform" <?= ($mto['category'] == 'school_uniform') ? 'selected' : ''; ?>>School Uniform</option>
                                                <option value="security_uniform" <?= ($mto['category'] == 'security_uniform') ? 'selected' : ''; ?>>Security Uniform</option>
                                                <option value="other" <?= ($mto['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
=======
                                                <option value="band_uniform" <?= $mto['category'] == 'band_uniform' ? 'selected' : ''; ?>>Band Uniform</option>
                                                <option value="school_uniform" <?= $mto['category'] == 'school_uniform' ? 'selected' : ''; ?>>School Uniform</option>
                                                <option value="security_uniform" <?= $mto['category'] == 'security_uniform' ? 'selected' : ''; ?>>Security Uniform</option>
                                                <option value="other" <?= $mto['category'] == 'other' ? 'selected' : ''; ?>>Other</option>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Subcategory</label>
                                            <input type="text" name="subcategory" class="form-control" 
<<<<<<< HEAD
                                                   value="<?= htmlspecialchars($mto['subcategory']); ?>"
                                                   placeholder="e.g., PE Uniform, Class Uniform">
=======
                                                value="<?= htmlspecialchars($mto['subcategory']); ?>"
                                                placeholder="e.g., PE Uniform, Class Uniform">
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                                        </div>
                                    </div>
                                </div>

<<<<<<< HEAD
                                <div class="form-group mb-3">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" required 
                                              placeholder="Enter detailed project specifications, measurements, and requirements"><?= htmlspecialchars($mto['description']); ?></textarea>
                                </div>

                                <div class="row mb-3">
=======
                                <div class="form-group mb-4">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" required 
                                            placeholder="Enter detailed project specifications, measurements, and requirements"><?= htmlspecialchars($mto['description']); ?></textarea>
                                </div>

                                <div class="row mb-4">
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Labor Cost <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" name="labor" class="form-control" required min="0" step="0.01"
<<<<<<< HEAD
                                                       value="<?= $mto['labor']; ?>" placeholder="0.00">
=======
                                                    value="<?= $mto['labor']; ?>">
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Materials Cost <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" name="full_order" class="form-control" required min="0" step="0.01"
<<<<<<< HEAD
                                                       value="<?= $mto['full_order']; ?>" placeholder="0.00">
=======
                                                    value="<?= $mto['full_order']; ?>">
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                                            </div>
                                        </div>
                                    </div>
                                </div>

<<<<<<< HEAD
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
=======
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label d-block">Current Reference Image</label>                                            <?php if ($mto['reference_image']): ?>
                                                <img src="../<?= htmlspecialchars($mto['reference_image']); ?>" 
                                                     class="img-thumbnail mb-2" alt="Current reference image">
                                            <?php else: ?>
                                                <p class="text-muted">No image uploaded</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Update Reference Image</label>
                                            <input type="file" name="image" class="form-control" accept="image/*">
                                            <small class="text-muted">Leave empty to keep the current image. Supported formats: JPG, PNG</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="custom_orders.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Changes
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
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
<<<<<<< HEAD
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
=======
            // Form validation
            $("#editMtoForm").on('submit', function(e) {
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

                // If there's a new image, validate it
                const imageInput = form.find('[name="image"]')[0];
                if (imageInput.files.length > 0) {
                    const file = imageInput.files[0];
                    const allowedTypes = ['image/jpeg', 'image/png'];
                    if (!allowedTypes.includes(file.type)) {
                        isValid = false;
                        form.find('[name="image"]')
                            .addClass('is-invalid')
                            .after('<div class="invalid-feedback">Only JPG and PNG files are allowed</div>');
                    }
                }

                if (!isValid) {
                    e.preventDefault();
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
                }
            });
        });
    </script>
</body>
</html>
