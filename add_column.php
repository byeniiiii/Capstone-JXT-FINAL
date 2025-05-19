<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tailor_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully. ";

// SQL to add column if it doesn't exist
$sql = "
SHOW COLUMNS FROM sublimation_orders 
LIKE 'player_details_file_reference'";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnSQL = "ALTER TABLE sublimation_orders 
                     ADD COLUMN player_details_file_reference VARCHAR(255) NULL 
                     AFTER design_path";
                     
    if ($conn->query($addColumnSQL) === TRUE) {
        echo "Column 'player_details_file_reference' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'player_details_file_reference' already exists.";
}

echo "\n\nCurrent columns in sublimation_orders table:\n";
$columnsSQL = "SHOW COLUMNS FROM sublimation_orders";
$columnsResult = $conn->query($columnsSQL);

if ($columnsResult->num_rows > 0) {
    while($row = $columnsResult->fetch_assoc()) {
        echo $row["Field"] . " - " . $row["Type"] . "\n";
    }
} else {
    echo "No columns found";
}

$conn->close();
?> 