<?php
session_start();
session_destroy(); // Destroy the session
header("Location: ../index.php"); // Redirect to login page
exit();
?>
<html>
<a href="logout.php" onclick="confirmLogout(event)" class="nav-link">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>

</html>
<script>
    function confirmLogout(event) {
        event.preventDefault(); // Prevent the default link action
        var userConfirm = confirm("Are you sure you want to log out?");
        if (userConfirm) {
            window.location.href = "logout.php";
        }
    }
</script>
