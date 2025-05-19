<?php
header('Content-Type: application/json');
require_once '../db.php';

$template_id = isset($_GET['template_id']) ? $_GET['template_id'] : null;

if (!$template_id) {
    echo json_encode(['success' => false, 'message' => 'Template ID is required']);
    exit();
}

$query = "SELECT t.added_by as user_id, CONCAT(u.first_name, ' ', u.last_name) as name 
          FROM templates t 
          JOIN users u ON t.added_by = u.user_id 
          WHERE t.template_id = ?";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query']);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $template_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        echo json_encode([
            'success' => true, 
            'user_id' => $row['user_id'],
            'name' => $row['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Template creator not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch template creator']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
