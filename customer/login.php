<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize error variable
$error = '';

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    include '../db.php';
    
    // Get form data and sanitize inputs
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Query to get user with the provided username
    $sql = "SELECT * FROM customers WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['name'];
            $_SESSION['customer_username'] = $user['username'];
            
            // Remember me functionality
            if (isset($_POST['remember'])) {
                // Set cookies for 30 days
                setcookie("customer_username", $username, time() + (86400 * 30), "/");
            }
            
            // Redirect to home page
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
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
    <title>Customer Login | JXT Tailoring</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ff7d00',
                        'primary-dark': '#e06c00',
                        dark: '#343a40',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary mb-4">
                    <i class="fas fa-tshirt text-xl"></i>
                </div>
                <h1 class="text-2xl font-semibold text-gray-800">JXT Tailoring</h1>
            </div>
            
            <!-- Login Form Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all">
                <div class="p-8">
                    <!-- Form Header -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Sign In</h2>
                        <p class="text-gray-600 mt-1">Welcome back! Please enter your details</p>
                    </div>
                    
                    <!-- Form -->
                    <form method="POST" action="" class="space-y-5">
                        <!-- Username Input -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <i class="fas fa-user"></i>
                            </div>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                placeholder="Username" 
                                required
                            >
                        </div>
                        
                        <!-- Password Input -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                placeholder="Password" 
                                required
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                            >
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Error Alert -->
                        <?php if (!empty($error)): ?>
                        <div class="bg-red-50 text-red-800 px-4 py-3 rounded-lg flex items-center text-sm">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full py-3 px-4 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg transition duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        >
                            Sign In
                        </button>
                    </form>
                    
                    <!-- Divider -->
                    <div class="relative flex items-center my-6">
                        <div class="flex-grow border-t border-gray-200"></div>
                        <span class="flex-shrink mx-3 text-gray-400 text-sm">or</span>
                        <div class="flex-grow border-t border-gray-200"></div>
                    </div>
                    
                    <!-- Sign Up Link -->
                    <a href="signup.php" class="block">
                        <button 
                            type="button" 
                            class="w-full py-3 px-4 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border border-gray-200 transition duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                        >
                            <i class="fas fa-user-plus mr-2"></i> Create New Account
                        </button>
                    </a>
                </div>
                
                <!-- Footer -->
                <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center text-sm text-gray-500">
                    Premium tailoring services for your unique style
                </div>
            </div>
            
            <!-- Features (Simplified) -->
            <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                <div class="px-3 py-4">
                    <div class="text-primary mb-2">
                        <i class="fas fa-ruler text-xl"></i>
                    </div>
                    <p class="text-sm text-gray-600">Custom Measurements</p>
                </div>
                <div class="px-3 py-4">
                    <div class="text-primary mb-2">
                        <i class="fas fa-scissors text-xl"></i>
                    </div>
                    <p class="text-sm text-gray-600">Expert Alterations</p>
                </div>
                <div class="px-3 py-4">
                    <div class="text-primary mb-2">
                        <i class="fas fa-award text-xl"></i>
                    </div>
                    <p class="text-sm text-gray-600">Premium Quality</p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('button[onclick="togglePassword()"] i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>