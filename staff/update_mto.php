<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = array('mto_id', 'category', 'description', 'labor', 'full_order');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }

        // Sanitize and get form data
        $mto_id = mysqli_real_escape_string($conn, $_POST['mto_id']);
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
        mysqli_begin_transaction($conn);

        // Handle image upload if a new image is provided
        $image_update_sql = "";
        $image_params = array();
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = "../uploads/mto/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES['image']['name'];
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
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $relative_path = "uploads/mto/" . $new_file_name;
                $image_update_sql = ", reference_image = ?";
                $image_params[] = $relative_path;

                // Delete old image if it exists
                $sql = "SELECT reference_image FROM made_to_order WHERE mto_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $mto_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $old_image = $row['reference_image'];
                    if ($old_image && file_exists("../" . $old_image)) {
                        unlink("../" . $old_image);
                    }
                }
            } else {
                throw new Exception("Error uploading image");
            }
        }

        // Update the MTO record
        $sql = "UPDATE made_to_order 
                SET category = ?, subcategory = ?, description = ?, 
                    labor = ?, full_order = ?" . $image_update_sql . "
                WHERE mto_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        
        $params = array($category, $subcategory, $description, $labor, $full_order);
        if (!empty($image_params)) {
            $params = array_merge($params, $image_params);
        }
        $params[] = $mto_id;
        
        $types = str_repeat('s', 3) . str_repeat('d', 2);
        if (!empty($image_params)) {
            $types .= 's';
        }
        $types .= 'i';
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating MTO project: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect back with success message
        header("Location: edit_mto.php?id=" . $mto_id . "&success=Project updated successfully");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        // Redirect back with error
        header("Location: edit_mto.php?id=" . $mto_id . "&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If not POST request, redirect to the projects page
    header("Location: custom_orders.php");
    exit();
}
?>
