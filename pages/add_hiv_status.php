<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the form
    $hiv_status_name = $_POST['hiv_status_name'];
    $description = $_POST['description'];
    // Validate data (you can add more validation as needed)

    // Insert data into tblcso table
    $sql = "INSERT INTO tbl_hiv_status (hiv_status_name, description) VALUES (?, ?)";

    if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("ss", $hiv_status_name, $description);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">HIV Status added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "add_gender.php";
                                        }, 3000);
                                    </script>';
                        exit();
                } else {
                        echo "Something went wrong. Please try again later.";
                }

                // Close statement
                $stmt->close();
        }

        // Close connection
        $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add HIV Status</title>
     <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../includes/style.css" type="text/css">
    
</head>
<body>
    <h2>Add HIV Status</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="hiv_status_name">HIV Status:</label>  <br>
        <input type="text" id="hiv_status_name" name="hiv_status_name" required>
        <br> <br>

        <label for="description">Description:</label>   <br>

        <textarea id="decsription" name="description" rows="4" cols="50"></textarea>
        <br>

        <input type="submit" value="Submit">
    </form>
</body>
</html>
