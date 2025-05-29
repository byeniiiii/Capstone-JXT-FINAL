<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

<<<<<<< HEAD
// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $staff_id = $_SESSION['user_id']; // Use the logged-in user's ID
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $subcategory = mysqli_real_escape_string($conn, $_POST['subcategory']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $labor = floatval($_POST['labor']);
    $full_order = floatval($_POST['full_order']);
    
    // Handle image upload
    $reference_image = '';
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] === 0) {
        $upload_dir = '../uploads/mto/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['images']['name'][0];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['images']['tmp_name'][0], $upload_path)) {
            $reference_image = 'uploads/mto/' . $file_name;
        }
    }
    
    // Insert into database
    $sql = "INSERT INTO made_to_orders (category, subcategory, description, staff_id, labor, full_order, reference_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiids", $category, $subcategory, $description, $staff_id, $labor, $full_order, $reference_image);
    
    if ($stmt->execute()) {
        header("Location: custom_orders.php?success=Project added successfully");
        exit();
    } else {
        header("Location: custom_orders.php?error=Failed to add project: " . $conn->error);
        exit();
    }
} else {
    // If not a POST request, redirect back to the form
    header("Location: custom_orders.php");
=======
// Initialize response array
$response = array(
    'status' => 'error',
    'message' => ''
);

try {
    // Check if form was submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate required fields
        $required_fields = array('user_id', 'category', 'description', 'labor', 'full_order');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }

        // Sanitize and get form data
        $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $subcategory = isset($_POST['subcategory']) ? mysqli_real_escape_string($conn, $_POST['subcategory']) : '';
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $labor = floatval($_POST['labor']);
        $full_order = floatval($_POST['full_order']);

        // Validate costs
        if ($labor < 0 || $full_order < 0) {
            throw new Exception("Costs cannot be negative");
        }

        // Start transaction
        mysqli_begin_transaction($conn);        // Insert into made_to_order table
        $sql = "INSERT INTO made_to_order (user_id, category, subcategory, description, labor, full_order, reference_image) 
                VALUES (?, ?, ?, ?, ?, ?, NULL)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssdd", $user_id, $category, $subcategory, $description, $labor, $full_order);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error creating MTO project: " . mysqli_error($conn));
        }

        // Get the inserted MTO ID
        $mto_id = mysqli_insert_id($conn);        // Handle single image upload
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = "../uploads/mto/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Process the first uploaded image only
            if ($_FILES['images']['error'][0] === 0) {
                $file_name = $_FILES['images']['name'][0];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validate file extension
                $allowed_exts = array('jpg', 'jpeg', 'png');
                if (!in_array($file_ext, $allowed_exts)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, and PNG files are allowed.");
                }

                // Generate unique filename
                $new_file_name = "mto_" . $mto_id . "_" . uniqid() . "." . $file_ext;
                $file_path = $upload_dir . $new_file_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['images']['tmp_name'][0], $file_path)) {
                    // Update the reference_image in the made_to_order table
                    $relative_path = "uploads/mto/" . $new_file_name;
                    $sql = "UPDATE made_to_order SET reference_image = ? WHERE mto_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $relative_path, $mto_id);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error saving image reference: " . mysqli_error($conn));
                    }
                } else {
                    throw new Exception("Error uploading image: " . $file_name);
                }
            }
        }

        // Commit transaction
        mysqli_commit($conn);
        
        // Set success response
        $response['status'] = 'success';
        $response['message'] = 'MTO project created successfully';
        
        // Redirect back to the MTO management page
        header("Location: custom_orders.php");
        exit();

    } else {
        throw new Exception("Invalid request method");
    }

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    // Set error response
    $response['message'] = $e->getMessage();
    
    // Redirect back with error
    header("Location: custom_orders.php?error=" . urlencode($response['message']));
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
    exit();
}
?>
