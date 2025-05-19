<?php
session_start();
include '../db.php';

// Check if the customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

// Check if logout is confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] == 1) {
    // Validate if the user is in the customers table
    $customer_id = $_SESSION['customer_id'];
    $query = "SELECT * FROM customers WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // If user is not in the customers table, force logout
        session_destroy();
        header("Location: index.php");
        exit();
    }

    // Destroy session and log out customer
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation | JX Tailoring</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .logout-card {
            max-width: 360px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        .logout-card h4 {
            font-weight: 500;
            margin-bottom: 15px;
            color: #333;
        }
        .logout-card p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #ff7d00;
            border-color: #ff7d00;
        }
        .btn-primary:hover {
            background-color: #e06c00;
            border-color: #e06c00;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <h4>Logout Confirmation</h4>
        <p>Are you sure you want to log out from your account?</p>
        <div class="d-grid gap-2">
            <a href="logout.php?confirm=1" class="btn btn-primary">
                Yes, Log Me Out
            </a>
            <a href="home.php" class="btn btn-outline-secondary">
                No, Take Me Back
            </a>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
