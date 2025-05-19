<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account | JX Tailoring</title>
    
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
        <div class="w-full max-w-2xl">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary mb-4">
                    <i class="fas fa-tshirt text-xl"></i>
                </div>
                <h1 class="text-2xl font-semibold text-gray-800">JX Tailoring</h1>
            </div>
            
            <!-- Registration Form Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all">
                <div class="p-8">
                    <!-- Form Header -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800">Create an Account</h2>
                        <p class="text-gray-600 mt-1">Join JX Tailoring for a personalized experience</p>
                    </div>
                    
                    <!-- Form -->
                    <form method="POST" action="" class="space-y-6">
                        <!-- Name Fields (Two Column) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- First Name Input -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="firstName" 
                                    name="firstName" 
                                    class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="First Name" 
                                    required
                                >
                            </div>
                            
                            <!-- Last Name Input -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="lastName" 
                                    name="lastName" 
                                    class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="Last Name" 
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Email Input -->
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
                        
                        <!-- Password Fields (Two Column) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    onclick="togglePassword('password')" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                >
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            
                            <!-- Confirm Password Input -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input 
                                    type="password" 
                                    id="confirmPassword" 
                                    name="confirmPassword" 
                                    class="block w-full pl-10 px-4 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="Confirm Password" 
                                    required
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('confirmPassword')" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                >
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
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
                            Create Account
                        </button>
                    </form>
                    
                    <!-- Login Link -->
                    <div class="text-center mt-6">
                        <p class="text-gray-600">
                            Already have an account? 
                            <a href="index.php" class="text-primary hover:text-primary-dark font-medium">
                                Sign In
                            </a>
                        </p>
                    </div>
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
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleBtn = document.querySelector(`button[onclick="togglePassword('${inputId}')"] i`);
            
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