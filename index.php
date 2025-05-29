<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];

            header("Location: {$row['role']}/dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JXT Tailoring - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page-container">
        <div class="login-container">
            <!-- Left: Login Form -->
            <div class="login-section">
                <div class="logo-container">
                    <h1>JXT Tailoring</h1>
                </div>
                
                <div class="form-container">
                    <h2>Welcome Back</h2>
                    <p class="subtitle">Sign in to continue to your account</p>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="input-group">
                            <label for="username"><i class="fas fa-user"></i></label>
                            <input type="text" id="username" name="username" placeholder="Username" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="password"><i class="fas fa-lock"></i></label>
                            <input type="password" id="password" name="password" placeholder="Password" required>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <span>Login</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                    
                </div>
            </div>

            <!-- Right: Image Section -->
            <div class="image-section">
                <div class="overlay"></div>
                <img src="image/tailor.jpg" alt="Tailoring Image">
                <div class="image-content">
                    <h2>Custom Tailoring Services</h2>
                    <p>Your perfect fit is our commitment.</p>
                </div>
            </div>
        </div>
    </div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

:root {
    --primary-color: #ff8c00;
    --primary-dark: #e67e00;
    --secondary-color: #333;
    --text-color: #555;
    --light-text: #777;
    --border-color: #e1e1e1;
    --background-light: #f9f9f9;
    --shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

body {
    background-color: var(--background-light);
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.page-container {
    width: 100%;
    padding: 20px;
}

.login-container {
    display: flex;
    max-width: 1000px;
    height: 600px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

/* Left: Login Section */
.login-section {
    width: 50%;
    padding: 40px;
    display: flex;
    flex-direction: column;
}

.logo-container {
    margin-bottom: 20px;
}

.logo-container h1 {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 24px;
}

.form-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-container h2 {
    font-size: 28px;
    color: var(--secondary-color);
    margin-bottom: 10px;
    font-weight: 600;
}

.subtitle {
    color: var(--light-text);
    margin-bottom: 30px;
    font-size: 15px;
}

.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group label {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--light-text);
}

.input-group input {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
}

.input-group input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(255, 140, 0, 0.1);
    outline: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.btn-primary:active {
    transform: translateY(0);
}

.form-footer {
    margin-top: 30px;
    text-align: center;
    color: var(--light-text);
    font-size: 14px;
}

.form-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.form-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-danger {
    background-color: #fff5f5;
    color: #e53e3e;
    border-left: 3px solid #e53e3e;
}

/* Right: Image Section */
.image-section {
    width: 50%;
    position: relative;
    overflow: hidden;
}

.image-section img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 10s ease;
}

.image-section:hover img {
    transform: scale(1.1);
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1;
}

.image-content {
    position: absolute;
    bottom: 60px;
    left: 30px;
    z-index: 2;
    color: white;
}

.image-content h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.image-content p {
    font-size: 15px;
    opacity: 0.9;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .login-container {
        flex-direction: column;
        height: auto;
    }

    .login-section, .image-section {
        width: 100%;
    }

    .image-section {
        height: 200px;
        order: -1;
    }
    
    .login-section {
        padding: 30px 20px;
    }
}
</style>

</body>
</html>
