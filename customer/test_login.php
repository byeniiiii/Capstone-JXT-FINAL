<?php
// Test script for login API
function testLoginAPI($username, $password) {
    $url = 'http://localhost/capstone_jxt/customer/login_api.php';
    $data = array(
        'username' => $username,
        'password' => $password
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "\nTest Case: username='$username', password='$password'\n";
    echo "Status Code: $httpCode\n";
    echo "Response: $response\n";
    echo "----------------------------------------\n";
}

echo "Starting Login API Tests...\n";

// Test 1: Missing fields
echo "\nTest 1: Missing Fields";
testLoginAPI("", "");

// Test 2: Invalid credentials
echo "\nTest 2: Invalid Credentials";
testLoginAPI("wronguser", "wrongpass");

// Test 3: Valid credentials (using hashed password)
echo "\nTest 3: Valid Credentials";
$test_password = 'test123'; // This should match a password in your database
$test_username = 'testcustomer';
testLoginAPI($test_username, $test_password);

// Test 4: Multiple failed attempts (to test lockout)
echo "\nTest 4: Multiple Failed Attempts (same username)";
for ($i = 0; $i < 6; $i++) {
    testLoginAPI($test_username, 'wrongpass' . $i);
    sleep(1); // Add a small delay between attempts
}

?>
