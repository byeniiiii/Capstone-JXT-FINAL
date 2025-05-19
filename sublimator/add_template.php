<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to add a template.'); window.location.href='../index.php';</script>";
    exit();
}

$added_by = $_SESSION['user_id']; // Ensure the user ID is taken from the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];

    // Generate a unique 5-digit template ID
    do {
        $template_id = rand(10000, 99999); // Ensures 5-digit number
        error_log("Generated template ID: " . $template_id); // Debug log
        $checkQuery = "SELECT template_id FROM templates WHERE template_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $template_id);
        $checkStmt->execute();
        $checkStmt->store_result();
    } while ($checkStmt->num_rows > 0); // Repeat if ID already exists
    $checkStmt->close();

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        
        // Create upload directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $fileName = basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        // Allow only JPG, PNG, and JPEG
        $allowedTypes = array("jpg", "png", "jpeg");
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                // Insert into database (including added_by)
                $query = "INSERT INTO templates (template_id, name, image_path, price, category, added_by) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("issdsi", $template_id, $name, $targetFilePath, $price, $category, $added_by);

                if ($stmt->execute()) {
                    echo "<script>alert('Template added successfully!'); window.location.href='templates.php';</script>";
                } else {
                    echo "<script>alert('Error saving template: " . $stmt->error . "');</script>";
                }
            } else {
                echo "<script>alert('Error uploading image.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file format. Only JPG, PNG, JPEG allowed.');</script>";
        }
    } else {
        echo "<script>alert('Please upload an image.');</script>";
    }
}
?>