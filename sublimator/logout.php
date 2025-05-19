<?php
// Only process logout if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    session_start();
    session_destroy(); // Destroy the session
    header("Location: ../index.php"); // Redirect to login page
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <!-- Include SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <style>
        /* Hide scrollbar for all browsers */
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f8f9fc;
        }
        
        /* Additional custom animation for the logout icon */
        @keyframes logout-pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
        .logout-icon {
            animation: logout-pulse 1.5s infinite;
            color: #ff8c00; /* Changed to orange */
            font-size: 5em;
            margin-bottom: 20px;
        }
        /* Custom SweetAlert styles with orange and black theme */
        .swal2-popup {
            border-radius: 10px;
        }
        .swal2-title {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            color: #000000 !important; /* Black text */
        }
        .swal2-html-container {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            color: #333333 !important; /* Dark gray for better readability */
        }
        .swal2-confirm {
            background-color: #ff8c00 !important; /* Orange button */
            border-radius: 5px !important;
            box-shadow: 0 0.25rem 0.55rem rgba(255, 140, 0, 0.25) !important;
        }
        .swal2-cancel {
            background-color: #333333 !important; /* Dark gray/black button */
            border-radius: 5px !important;
        }
        .swal2-icon.swal2-warning {
            border-color: #ff8c00 !important; /* Orange icon border */
            color: #ff8c00 !important; /* Orange icon color */
        }
        /* Spinner color for loading screen */
        .fa-spinner {
            color: #ff8c00 !important;
        }
        /* Small text styling */
        .small.text-muted {
            color: #666666 !important;
        }
    </style>
</head>
<body>
    <script>
        // Execute the logout confirmation as soon as page loads
        document.addEventListener('DOMContentLoaded', function() {
            showLogoutConfirmation();
        });
        
        function showLogoutConfirmation() {
            Swal.fire({
                title: 'Logout Confirmation',
                html: '<div class="logout-icon"><i class="fas fa-sign-out-alt"></i></div>' + 
                      '<p style="color: #000000; font-weight: 500;">Are you sure you want to log out?</p>' +
                      '<p class="small text-muted">You will be redirected to the login page.</p>',
                showCancelButton: true,
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'No, keep me signed in',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-warning',
                    cancelButton: 'btn btn-dark'
                },
                buttonsStyling: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: `rgba(0,0,0,0.4)`,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                },
                didOpen: () => {
                    // Load Font Awesome if not already loaded
                    if (!document.querySelector('link[href*="font-awesome"]')) {
                        const link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css';
                        document.head.appendChild(link);
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state with animation
                    Swal.fire({
                        title: 'Signing Out...',
                        html: '<i class="fas fa-spinner fa-spin fa-3x"></i>',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            // Submit the form to process logout
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'logout.php';
                            
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'confirm_logout';
                            input.value = '1';
                            
                            form.appendChild(input);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                } else {
                    // Redirect back to previous page if canceled
                    window.history.back();
                }
            });
        }
    </script>
    <!-- This is just a fallback in case JavaScript is disabled -->
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif; color: #000000;">
        <h2 style="color: #ff8c00;">Logout Page</h2>
        <p>Please enable JavaScript to continue with the logout process.</p>
        <a href="javascript:history.back()" style="color: #ff8c00; text-decoration: none; font-weight: bold;">Go Back</a>
    </div>
</body>
</html>
