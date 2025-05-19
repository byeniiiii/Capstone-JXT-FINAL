<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Ensure ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid request.'); window.location.href='templates.php';</script>";
    exit();
}
 
$template_id = $_GET['id'];
error_log("Editing template ID: " . $template_id); // Debug log

// Fetch the template data
$query = "SELECT * FROM templates WHERE template_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $template_id); // Changed from "s" to "i"
$stmt->execute();
$result = $stmt->get_result();
$template = $result->fetch_assoc();

// If no matching template is found
if (!$template) {
    echo "<script>alert('Template not found!'); window.location.href='templates.php';</script>";
    exit();
}

// Handle form submission for updating the template
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];

    $updateQuery = "UPDATE templates SET name = ?, price = ?, category = ? WHERE template_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sdsi", $name, $price, $category, $template_id); // Changed from "sdss" to "sdsi"

    if ($stmt->execute()) {
        echo "<script>alert('Template updated successfully!'); window.location.href='templates.php';</script>";
    } else {
        echo "<script>alert('Error updating template.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Template</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container">
    <h2 class="mt-4">Edit Template</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($template['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label>Price</label>
            <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($template['price'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($template['category'] ?? ''); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="templates.php" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>