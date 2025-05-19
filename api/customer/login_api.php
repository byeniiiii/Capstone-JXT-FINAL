<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Missing required fields"
    ));
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check if account is locked
    $lock_query = "SELECT COUNT(*) as attempt_count, MAX(attempt_time) as last_attempt 
                   FROM login_attempts 
                   WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    $lock_stmt = $db->prepare($lock_query);
    $lock_stmt->execute([$data->username]);
    $lock_result = $lock_stmt->fetch(PDO::FETCH_ASSOC);

    if ($lock_result['attempt_count'] >= 5) {
        $minutes_remaining = 15 - floor((time() - strtotime($lock_result['last_attempt'])) / 60);
        http_response_code(429);
        echo json_encode(array(
            "success" => false,
            "message" => "Account temporarily locked. Please try again later.",
            "wait_time" => $minutes_remaining . " minutes"
        ));
        exit();
    }

    // Get user
    $query = "SELECT customer_id, username, password, email, first_name, last_name, phone_number, address 
              FROM customers WHERE username = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data->password, $user['password'])) {
        // Success - Clear any existing login attempts
        $clear_attempts = "DELETE FROM login_attempts WHERE username = ?";
        $clear_stmt = $db->prepare($clear_attempts);
        $clear_stmt->execute([$data->username]);

        // Format user data to match mobile app expectations
        $formatted_user = array(
            "customer_id" => $user['customer_id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "fname" => $user['first_name'],
            "lname" => $user['last_name'],
            "contact_number" => $user['phone_number'],
            "address" => $user['address']
        );

        // Remove password from response
        unset($user['password']);

        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Login successful",
            "user" => $formatted_user
        ));
    } else {
        // Record failed attempt
        $record_attempt = "INSERT INTO login_attempts (username, attempt_time) VALUES (?, NOW())";
        $attempt_stmt = $db->prepare($record_attempt);
        $attempt_stmt->execute([$data->username]);

        // Get remaining attempts
        $attempts_left = 5 - ($lock_result['attempt_count'] + 1);
        
        http_response_code(401);
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid username or password",
            "attempts_left" => $attempts_left
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ));
}
?>
