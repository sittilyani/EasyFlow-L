<?php
include '../includes/config.php'; // Include the database configuration file
include '../includes/footer.php';
include '../includes/header.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $drugName = $_POST['drugName'];
    $drugCategory = $_POST['drugCategory'];
    $description = $_POST['description'];

    // Prepare SQL statement
    $sql = "INSERT INTO drug (drugName, drugCategory, description) VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("sss", $drugName, $drugCategory, $description);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">New Drug Added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "adddrug.php";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Drug</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <!-- Add your CSS stylesheets or use a CDN for styling -->
    <style>
    body{
        font-family: "Times New Roman", Times, serif;
    }
           .row{
               margin-left: 40px;
               margin-top: 10px;
               max-width: 40%;
           }
           label{
              margin-left: 40px;
           }
            h2{
              color: #000099;
              font-size: 22px;
              margin-left: 40px;

           }

           label, input, select, textarea{

             width: 100%;
           }
            .submit{
                background-color: #000080;
                color: white;
                cursor: pointer;
            }
           form{
            width: 300px;
            background-color: #FFFFE0;
            padding: 20px;
            border: solid thin;
        }
    </style>

</head>
<body>

<h2>Add New Drug</h2>

<!-- Your HTML form for adding drugs -->
<div class="row">
    <form action="adddrug.php" method="post">
        <label for="drugName">Drug Name:</label> <br>
        <input type="text" name="drugName" required>
        <br>

        <label for="drugCategory">Drug Category:</label> <br>
        <select name="drugCategory" required>
            <?php
            // Fetch only the catName column from drugCategory
            $sqlCategories = "SELECT catName FROM drugCategory";
            $resultCategories = $conn->query($sqlCategories);

            // Loop through the results to populate the dropdown
            while ($row = $resultCategories->fetch_assoc()) {
                echo "<option value='{$row['catName']}'>{$row['catName']}</option>";
            }
            ?>
        </select>
        <br>

        <label for="description">Drug Description:</label>  <br>
        <textarea id="description" name="description" rows="4" cols="30"></textarea>



        <br>
        <input type="submit" class="submit" value="Add Drug">
    </form>
</div>

<!-- Add your JavaScript scripts or use a CDN for scripts -->
<script src="../assets/js/bootstrap.min.js"></script>
<script src="../assets/js/jquery-3.7.1.min.js"></script>
</body>
</html>
