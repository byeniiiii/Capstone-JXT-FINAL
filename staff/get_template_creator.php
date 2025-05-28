<?php
header('Content-Type: application/json');
include '../db.php';

try {
    $template_id = isset($_GET['template_id']) ? $_GET['template_id'] : null;

    if (!$template_id) {
        throw new Exception('Template ID is required');
    }

    // Fixed query to use added_by instead of created_by
    $query = "SELECT u.user_id, u.first_name, u.last_name 
              FROM templates t 
              JOIN users u ON t.added_by = u.user_id 
              WHERE t.template_id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "s", $template_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);    
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true, 
            'user_id' => $row['user_id'],
            'creator_name' => $row['first_name'] . ' ' . $row['last_name']
        ]);
    } else {
        throw new Exception('Template creator not found');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
