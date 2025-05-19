<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f8f9fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
        }
        .container {
            text-align: center; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .btn-home {
            background-color: orange; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            font-size: 16px; 
            cursor: pointer; 
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            background-color: darkorange;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-success">✅ Order Submitted Successfully!</h2>
    <p>Your order is now <strong>Awaiting Approval</strong>.</p>

    <a href="index.php" class="btn-home">🏠 Go Back Home</a>
</div>

</body>
</html>
