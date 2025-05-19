<?php
// Test configuration
$API_BASE_URL = 'http://localhost/capstone_jxt';
$TEST_USERNAME = 'lando';
$TEST_PASSWORD = 'password123'; // Note: This needs to match the hashed password in the database

// Function to make HTTP requests
function makeRequest($endpoint, $method = 'POST', $data = null) {
    global $API_BASE_URL;
    
    $ch = curl_init($API_BASE_URL . $endpoint);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Function to run tests
function runTest($name, $callback) {
    echo "\nRunning test: $name\n";
    echo "----------------------------------------\n";
    $callback();
    echo "----------------------------------------\n";
}

// Test Cases

// Test 1: Missing Fields
runTest('Login with missing fields', function() {
    $result = makeRequest('/customer/login_api.php', 'POST', [
        'username' => 'testuser'
        // Missing password
    ]);
    echo "Status: {$result['status']}\n";
    echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    assert($result['status'] === 400, "Expected 400 status code");
});

// Test 2: Invalid Credentials
runTest('Login with invalid credentials', function() {
    $result = makeRequest('/customer/login_api.php', 'POST', [
        'username' => 'wronguser',
        'password' => 'wrongpass'
    ]);
    echo "Status: {$result['status']}\n";
    echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    assert($result['status'] === 401, "Expected 401 status code");
});

// Test 3: Valid Login
runTest('Login with valid credentials', function() {
    global $TEST_USERNAME, $TEST_PASSWORD;
    $result = makeRequest('/customer/login_api.php', 'POST', [
        'username' => $TEST_USERNAME,
        'password' => $TEST_PASSWORD
    ]);
    echo "Status: {$result['status']}\n";
    echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    assert($result['status'] === 200, "Expected 200 status code");
});

// Test 4: Account Lockout
runTest('Test account lockout after multiple failures', function() {
    $username = 'lockouttest';
    $password = 'wrongpass';
    
    // Attempt login 6 times
    for ($i = 1; $i <= 6; $i++) {
        $result = makeRequest('/customer/login_api.php', 'POST', [
            'username' => $username,
            'password' => $password
        ]);
        echo "Attempt $i - Status: {$result['status']}\n";
        
        if ($i >= 5) {
            assert($result['status'] === 429, "Expected 429 status code after 5 failures");
            echo "Lockout Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
        }
    }
});
