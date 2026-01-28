<?php
session_start();
include('../includes/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the new password and confirm password match
    if ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match. Please try again.";
    } else {
        // Fetch the user's current password hash from the database
        $query = "SELECT password FROM tblusers WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_password_hash);
        $stmt->fetch();
        $stmt->close();

        // Verify the old password
        if (password_verify($old_password, $current_password_hash)) {
            // Hash the new password
            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the password in the database
            $update_query = "UPDATE tblusers SET password = ? WHERE user_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("si", $hashed_new_password, $user_id);

            if ($stmt_update->execute()) {
                $success_message = "Password updated successfully. Redirecting to the dashboard...";
                // Close the connection
                $stmt_update->close();
                $conn->close();

                // Redirect to the dashboard after 3 seconds
                echo "<script>
                    alert('$success_message');
                    setTimeout(() => {
                        window.location.href = '../public/login.php';
                    }, 1000);
                </script>";
                exit;
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
        } else {
            $error_message = "Old password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">

    <style>
        form {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* Three equal columns */}

        .main-content {
            padding: 20px;
            max-width: 50%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

    </style>
</head>
<body>
   <div class="main-content">
        <div class="form-group"><h2>Reset Password</h2></div>

    <form method="post" action="reset_password.php">
        <div class="form-group">
            <label for="old_password">Old Password:</label>
            <input type="password" name="old_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" required>
        </div>
        <div class="form-group">
            <button class="custom-submit-btn" type="submit">Update Password</button>
        </div>
    </form>
   </div>

    <?php
    // Display success or error message
    if (isset($success_message)) {
        echo "<p style='color: green;'>$success_message</p>";
    }
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>
</body>
</html>
