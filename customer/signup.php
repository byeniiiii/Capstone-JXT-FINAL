<?php
session_start();
require '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = rand(1000, 9999); // Generate 4-digit customer ID
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username or email already exists
    $checkQuery = "SELECT * FROM customers WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Username or Email already exists!";
    } else {
        // Insert new customer
        $query = "INSERT INTO customers (customer_id, username, password, email, first_name, last_name, phone_number, address) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssss", $customer_id, $username, $password, $email, $first_name, $last_name, $phone_number, $address);

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
    <title>Sign Up | JX Tailoring</title>
    
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
                    animation: {
                        'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-6xl">
            <!-- Container -->
            <div class="flex flex-col md:flex-row bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Left: Form Section -->
                <div class="w-full md:w-3/5 p-6 md:p-8 lg:p-10">
                    <!-- Logo -->
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary mb-3">
                            <i class="fas fa-tshirt text-xl"></i>
                        </div>
                        <h1 class="text-xl font-semibold text-gray-800">JX Tailoring</h1>
                    </div>
                    
                    <!-- Form Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-800">Create Account</h2>
                        <p class="text-gray-600 mt-1">Fill in your details to join JX Tailoring</p>
                    </div>
                    
                    <!-- Alert Messages -->
                    <?php if (isset($error)): ?>
                    <div class="bg-red-50 text-red-800 px-4 py-3 rounded-lg flex items-center text-sm mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                    <div class="bg-green-50 text-green-800 px-4 py-3 rounded-lg flex items-center text-sm mb-6">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Form -->
                    <form method="POST" action="" class="space-y-5">
                        <!-- Name Fields Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- First Name -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="first_name" 
                                    name="first_name" 
                                    class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="First Name" 
                                    required
                                >
                            </div>
                            
                            <!-- Last Name -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="last_name" 
                                    name="last_name" 
                                    class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="Last Name" 
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Username/Email Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Username -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-user-tag"></i>
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
                            
                            <!-- Email -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="Email Address" 
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Phone Number -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <i class="fas fa-phone"></i>
                            </div>
                            <input 
                                type="text" 
                                id="phone_number" 
                                name="phone_number" 
                                class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                placeholder="Phone Number" 
                                required
                            >
                        </div>
                        
                        <!-- Address -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <input 
                                type="text" 
                                id="address" 
                                name="address" 
                                class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                placeholder="Address" 
                                required
                            >
                        </div>
                        
                        <!-- Password -->
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
                        
                        <!-- Terms & Conditions -->
                        <div class="flex items-start">
                            <input 
                                type="checkbox" 
                                id="terms" 
                                name="terms" 
                                class="w-4 h-4 mt-1 text-primary border-gray-300 rounded focus:ring-primary"
                                required
                            >
                            <label for="terms" class="ml-2 text-sm text-gray-600">
                                I agree to the <a href="#" class="text-primary hover:text-primary-dark">Terms of Service</a> and <a href="#" class="text-primary hover:text-primary-dark">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full py-3 px-4 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg transition duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        >
                            <i class="fas fa-user-plus mr-2"></i> Create Account
                        </button>
                        
                        <!-- Login Link -->
                        <div class="text-center mt-4">
                            <p class="text-gray-600">
                                Already have an account? 
                                <a href="index.php" class="text-primary hover:text-primary-dark font-medium">
                                    Sign In
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
                
                <!-- Right: Image Section -->
                <div class="w-full md:w-2/5 bg-gradient-to-br from-dark to-primary relative hidden md:block">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-8 lg:p-12">
                        <h2 class="text-2xl font-bold mb-4">Welcome to JX Tailoring</h2>
                        <p class="text-white/90 mb-8 text-center">
                            By creating an account, you'll be able to access our custom tailoring services, 
                            track your orders, and enjoy a personalized experience.
                        </p>
                        
                        <div class="space-y-4 w-full max-w-xs">
                            <div class="flex items-center">
                                <div class="bg-white/20 p-2 rounded-full mr-3">
                                    <i class="fas fa-check text-sm"></i>
                                </div>
                                <span>Personalized services</span>
                            </div>
                            <div class="flex items-center">
                                <div class="bg-white/20 p-2 rounded-full mr-3">
                                    <i class="fas fa-check text-sm"></i>
                                </div>
                                <span>Order tracking</span>
                            </div>
                            <div class="flex items-center">
                                <div class="bg-white/20 p-2 rounded-full mr-3">
                                    <i class="fas fa-check text-sm"></i>
                                </div>
                                <span>Exclusive offers</span>
                            </div>
                        </div>
                    </div>
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
