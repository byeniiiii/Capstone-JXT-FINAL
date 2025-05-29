<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // Get selected role

    // Check if username or email already exists
    $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Username or Email already exists!";
    } else {
        // Insert new user with selected role
        $query = "INSERT INTO users (username, password, email, first_name, last_name, phone_number, role) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $username, $password, $email, $first_name, $last_name, $phone_number, $role);

        if ($stmt->execute()) {
            $success = "Account created successfully! You can now log in.";
        } else {
            $error = "Error creating account: " . $stmt->error;
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JXT Tailoring - Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page-container">
        <div class="login-container">
            <!-- Left: Sign Up Form -->
            <div class="login-section">
                <div class="logo-container">
                    <h1>JXT Tailoring</h1>
                </div>
                
                <div class="form-container signup-form">
                    <h2>Create Account</h2>
                    <p class="subtitle">Fill in your details to get started</p>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="input-group half">
                                <label for="first_name"><i class="fas fa-user"></i></label>
                                <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                            </div>
                            
                            <div class="input-group half">
                                <label for="last_name"><i class="fas fa-user"></i></label>
                                <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label for="username"><i class="fas fa-id-card"></i></label>
                            <input type="text" id="username" name="username" placeholder="Username" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="email"><i class="fas fa-envelope"></i></label>
                            <input type="email" id="email" name="email" placeholder="Email" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="phone_number"><i class="fas fa-phone"></i></label>
                            <input type="text" id="phone_number" name="phone_number" placeholder="Phone Number" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="password"><i class="fas fa-lock"></i></label>
                            <input type="password" id="password" name="password" placeholder="Password" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="role"><i class="fas fa-user-tag"></i></label>
                            <select id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Staff">Staff</option>
                                <option value="Manager">Manager</option>
                                <option value="Admin">Admin</option>
                                <option value="Sublimator">Sublimator</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <span>Sign Up</span>
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </form>
                    
                    <div class="form-footer">
                        <p>Already have an account? <a href="index.php">Login here</a></p>
                    </div>
                </div>
            </div>

            <!-- Right: Image Section -->
            <div class="image-section">
                <div class="overlay"></div>
                <img src="image/tailor.jpg" alt="Tailoring Image">
                <div class="image-content">
                    <h2>Join Our Community</h2>
                    <p>Create an account to access premium tailoring services.</p>
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
    --success-color: #28a745;
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
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

/* Left: Form Section */
.login-section {
    width: 50%;
    padding: 30px;
    display: flex;
    flex-direction: column;
    max-height: 800px;
    overflow-y: auto;
}

.logo-container {
    margin-bottom: 15px;
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
}

.signup-form {
    justify-content: flex-start;
    padding-right: 10px;
}

.form-container h2 {
    font-size: 24px;
    color: var(--secondary-color);
    margin-bottom: 8px;
    font-weight: 600;
}

.subtitle {
    color: var(--light-text);
    margin-bottom: 20px;
    font-size: 14px;
}

.row {
    display: flex;
    gap: 10px;
    margin-bottom: 0;
}

.input-group {
    position: relative;
    margin-bottom: 15px;
}

.input-group.half {
    width: 50%;
}

.input-group label {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--light-text);
}

.input-group input,
.input-group select {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.input-group input:focus,
.input-group select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(255, 140, 0, 0.1);
    outline: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 12px;
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
    margin-top: 20px;
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
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
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

.alert-success {
    background-color: #f0fff4;
    color: var(--success-color);
    border-left: 3px solid var(--success-color);
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
        padding: 25px 20px;
    }
    
    .row {
        flex-direction: column;
    }
    
    .input-group.half {
        width: 100%;
    }
}
</style>

</body>
</html>
