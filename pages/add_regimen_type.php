<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $regimen_type_name = trim($_POST["regimen_type_name"]);

        // Prepare an insert statement
        $sql = "INSERT INTO regimen_type (regimen_type_name) VALUES (?)";

        if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $regimen_type_name);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">Regimen Type added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "add_regimen_type.php";
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
    <title>Regimen Type</title>
     <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../includes/style.css" type="text/css">

</head>
<body>
    <h2>Add Regimen Type</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="regimen_type_name">Regimen Type:</label>  <br>
        <input type="text" id="regimen_type_name" name="regimen_type_name" required>
        <br> <br>

        <input type="submit" value="Submit">
    </form>
</body>
</html>
