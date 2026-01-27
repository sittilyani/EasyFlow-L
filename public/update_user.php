<?php
include '../includes/config.php';

// Retrieve user details for the specified user_id
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $sql = "SELECT * FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();


}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $mobile = $_POST['mobile'];
    $userrole = $_POST['userrole'];

    $sql = "UPDATE tblusers SET username = ?, first_name = ?, last_name = ?, email = ?, gender = ?, mobile = ?, userrole = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssi', $username, $first_name,  $last_name, $email, $gender, $mobile, $userrole, $user_id);

    $stmt->execute();

    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
         .content-main{
             width: 60%;
             margin-left: auto;
             margin-right: auto;
         }

    </style>
</head>
<body>

<div class="content-main">

<h2>Update User details</h2>

    <form action="update_user.php" method="post">

        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
        <div class="form-group">
            <label for="username">User Name</label>
            <input type="text" name="username" value="<?php echo $user['username']; ?>">
        </div>
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>">
        </div>
        <div class="form-group">
            <label for="last_name">Last Name</label>
        <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="text" name="email" value="<?php echo $user['email']; ?>">
        </div>
        <div class="form-group">
            <label for="sex">Gender</label>
            <input type="text" name="gender" value="<?php echo $user['gender']; ?>">
        </div>
        <div class="form-group">
            <label for="mobile">Mobile</label>
            <input type="text" name="mobile" value="<?php echo $user['mobile']; ?>" readonly class="readonly-input">
        </div>
        <div class="form-group">
            <label for="userrole">User Role</label>
                <select name="userrole" required>
                    <?php
                    // Fetch genders from tblgender and populate the dropdown
                    $result = $conn->query("SELECT id, role FROM userroles");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['role']}'>{$row['role']}</option>";
                    }
                    ?>
                </select>
        </div>
        <input type="submit" class="custom-submit-btn"name="submit" value="Update">
    </form>
</div>
</body>
</html>
