<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $template_id = $_POST['id'];
    
    $query = "DELETE FROM templates WHERE template_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $template_id); // Changed from "s" to "i"
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error deleting template.";
    }
}
?>