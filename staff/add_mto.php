<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

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
    exit();
}
?>
