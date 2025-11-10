<?php
include '../includes/config.php';

// Check if drugID is set in the URL
if (isset($_GET['drugID'])) {
        $drugID = $_GET['drugID'];

        // Fetch drug details from the database based on the ID
        $sql = "SELECT * FROM drug WHERE drugID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $drugID);
        $stmt->execute();
        $result = $stmt->get_result();
        $drug = $result->fetch_assoc();

        if (!$drug) {
                die("Drug not found");
        }
} else {
        die("Invalid request. Please provide a drug ID.");
}

// Handle form submission for updating drug details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data
        $drugName = $_POST['drugName'];
        $drugcategory = $_POST['drugCategory'];
        $description = $_POST['description'];
        $price = $_POST['price'];

        // Prepare SQL statement for updating drug details
        $sqlUpdate = "UPDATE drug SET drugName = ?, drugCategory = ?, description = ?, price = ? WHERE drugID = ?";
        if ($stmt = $conn->prepare($sqlUpdate)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("ssssi", $drugName, $drugCategory, $description, $price, $drugID);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">Drug updated successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "../pharmacy/view_other_drugs.php";
                                        }, 3000);
                                    </script>';
                        exit();
                } else {
                        echo '<div style="color: red; margin-left: 40px; margin-top: 30px; font-size: 18px;">Something went wrong. Please try again later.</div>';
                }

                // Close the statement
                $stmt->close();
        }
}

// Close the database connection
$conn->close();
?>


<!-- HTML form for updating drug details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Drug/Item</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
        .content-main{
            width: 40%;
            margin-left: auto;
            margin-right: auto;
        }

        .form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            background-color: none;

        }

        h2{

            color: #000099;
            margin-left: 70px;
        }

    </style>
</head>
<body>

    <div class="content-main">
        <h2>Update Drug/Item Details</h2>
        <form action="update_drug.php?drugID=<?php echo $drugID; ?>" method="post">
            <!-- Display existing drug details in the form -->
            <div class="form-group">
                <label for="drugName">Drug/Item Name:</label>
                <input type="text" id="drugName" name="drugName" value="<?php echo $drug['drugName']; ?>" required>
            </div>
            <div class="form-group">
                <label for="drugCategory">Drug/Item Category:</label>
                <input type="text" id="drugCategory" name="drugCategory" value="<?php echo $drug['drugCategory']; ?>">
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <input type="text" id="description" name="description" value="<?php echo $drug['description']; ?>">
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="text" id="price" name="price" value="<?php echo $drug['price']; ?>">
            </div>

            <!-- Add or modify fields as needed -->
            <button type="submit" class="custom-submit-btn">Update Drug or Item</button>
        </form>
    </div>
</body>
</html>
