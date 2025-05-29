<?php
// Include database connection
session_start();
include '../db.php';

// Function to log user activity
function logActivity($conn, $user_id, $action_type, $description) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $user_type = mysqli_real_escape_string($conn, $_SESSION['role'] ?? 'Unknown');
    $action_type = mysqli_real_escape_string($conn, $action_type);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
              VALUES ('$user_id', '$user_type', '$action_type', '$description', NOW())";
    
    mysqli_query($conn, $query);
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php?error=Invalid user ID");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch user details
$query = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: users.php?error=User not found");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Check if username or email already exists for another user
    $check_query = "SELECT * FROM users WHERE (username = '$username' OR email = '$email') AND user_id != '$user_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        header("Location: edit_user.php?id=$user_id&error=Username or Email already exists");
        exit();
    }

    // Update user information
    $update_query = "UPDATE users SET 
                        first_name = '$first_name',
                        last_name = '$last_name',
                        email = '$email',
                        username = '$username',
                        phone_number = '$phone_number',
                        role = '$role'
                     WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $update_query)) {
        // Log the activity
        if (isset($_SESSION['user_id'])) {
            logActivity($conn, $_SESSION['user_id'], 'UPDATE', "Updated user: $first_name $last_name, Role: $role (ID: $user_id)");
        }
        header("Location: users.php?success=User updated successfully");
        exit();
    } else {
        header("Location: edit_user.php?id=$user_id&error=Failed to update user: " . mysqli_error($conn));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .btn-orange {
            background-color: #ff7f00;
            color: white;
            border: none;
        }
        .btn-orange:hover {
            background-color: #e67000;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit User</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
        <?php endif; ?>
        <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
            <div class="mb-2">
                <label>First Name</label>
                <input type="text" class="form-control" name="first_name" value="<?php echo $user['first_name']; ?>" required>
            </div>
            <div class="mb-2">
                <label>Last Name</label>
                <input type="text" class="form-control" name="last_name" value="<?php echo $user['last_name']; ?>" required>
            </div>
            <div class="mb-2">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
            <div class="mb-2">
                <label>Username</label>
                <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="mb-2">
                <label>Phone Number</label>
                <input type="text" class="form-control" name="phone_number" value="<?php echo $user['phone_number']; ?>" required>
            </div>
            <div class="mb-2">
                <label>Role</label>
                <select class="form-control" name="role" required>
                    <option value="Admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>Admin</option>
                    <option value="Manager" <?php if ($user['role'] == 'Manager') echo 'selected'; ?>>Manager</option>
                    <option value="Staff" <?php if ($user['role'] == 'Staff') echo 'selected'; ?>>Staff</option>
                </select>
            </div>
            <!-- Button to trigger modal -->
            <button type="button" class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#confirmModal">
                Update User
            </button>
            <a href="users.php" class="btn btn-secondary">Cancel</a>

            <!-- Confirmation Modal -->
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">Confirm Update</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to update this user?</p>
                            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-orange">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
