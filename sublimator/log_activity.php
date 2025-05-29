<?php
session_start();
include_once '../db.php';

// Check if user is logged in and is a sublimator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Sublimator') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['role'];

// Get action type and description from POST data
$action_type = isset($_POST['action_type']) ? $_POST['action_type'] : '';
$description = isset($_POST['description']) ? $_POST['description'] : '';

// Validate input
if (empty($action_type) || empty($description)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Sanitize input
$user_id = mysqli_real_escape_string($conn, $user_id);
$user_type = mysqli_real_escape_string($conn, $user_type);
$action_type = mysqli_real_escape_string($conn, $action_type);
$description = mysqli_real_escape_string($conn, $description);

// Insert into activity logs
$query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
          VALUES ('$user_id', '$user_type', '$action_type', '$description', NOW())";

$result = mysqli_query($conn, $query);

// Return JSON response
header('Content-Type: application/json');
if ($result) {
    echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to log activity: ' . mysqli_error($conn)]);
}
?> 