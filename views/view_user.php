<?php
ob_start();
include '../includes/config.php';
include '../includes/header.php';

// Initialize $user to an empty array to avoid warnings if no user is found.
$user = []; // Important: Initialize $user

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $sql = "SELECT * FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { // Check if any rows were returned
        $user = $result->fetch_assoc(); // Now $user is populated
    } else {
        // Handle the case where the user is not found.  You can redirect or display a message.
        header("Location: userslist.php?error=user_not_found"); // Redirect with an error message
        exit(); // Important: Stop execution after redirecting
    }
    $stmt->close(); // Close the statement after use
} else {
    // Handle the case where user_id is not set.  You can redirect or display a message.
    header("Location: userslist.php?error=user_id_missing"); // Redirect with an error message
    exit(); // Important: Stop execution after redirecting
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $mobile = $_POST['mobile'];
    $userrole = $_POST['userrole'];

    $sql = "UPDATE tblusers SET first_name = ?, last_name = ?, email = ?, gender = ?, mobile = ?, userrole = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssi', $first_name, $last_name, $email, $gender, $mobile, $userrole, $user_id);

    $stmt->execute();
    $stmt->close(); // Close the statement

    header("Location: userslist.php?success=user_updated"); // Redirect with a success message
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    </head>
<body>
    <div class="views">
        <h2>View User</h2>

        <?php if (!empty($user)): ?>  <p>User ID: <?php echo $user['user_id']; ?></p>
            <p>First Name: <?php echo $user['first_name']; ?></p>
            <p>Last Name: <?php echo $user['last_name']; ?></p>
            <p>Email: <?php echo $user['email']; ?></p>
            <p>Gender: <?php echo $user['gender']; ?></p>
            <p>Mobile: <?php echo $user['mobile']; ?></p>
            <p>User Role: <?php echo $user['userrole']; ?></p>
            <p>Date Created: <?php echo $user['date_created']; ?></p>
        <?php else: ?>
            <p>User not found.</p>  <?php endif; ?>

        <a href="userslist.php">Back to User List</a>
    </div>
</body>
</html>