<?php
include '../includes/config.php';
include ("../includes/header.php");

// Check if ID parameter is passed through the URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    // Get ID from URL
    $id = trim($_GET['id']);

    // Prepare SELECT statement
    $sql = "SELECT * FROM pharmacy WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a record was found
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $drugName = $row['drugName'];
        $stockqty = $row['stockqty'];
        $stockedDate = $row['stockedDate'];
        $expiryDate = $row['expiryDate'];
    } else {
        // Redirect to error page if no record found
        header("location: error.php");
        exit();
    }

    // Close the statement
    $stmt->close();
} else {
    // Redirect to error page if ID parameter is not present
    header("location: error.php");
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $drugName = $_POST['drugName'];
    $stockqty = $_POST['stockqty'];
    $stockedDate = $_POST['stockedDate'];
    $expiryDate = $_POST['expiryDate'];

    // Prepare UPDATE statement
    $sql = "UPDATE pharmacy SET drugName = ?, stockqty = ?, stockedDate = ?, expiryDate = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $drugName, $stockqty, $stockedDate, $expiryDate, $id);

    // Execute the statement
    if ($stmt->execute()) {
        header("location: viewstocks.php");
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

include '../includes/footer.php';
?>

<!-- HTML form for editing stock details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Drug Stocks</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Edit Drug Stocks</h2>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label for="drugName">Drug Name:</label>
            <input type="text" name="drugName" id="drugName" value="<?= $drugName; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="stockqty">Stock Quantity:</label>
            <input type="number" name="stockqty" id="stockqty" value="<?= $stockqty; ?>" required>
        </div>

        <div class="form-group">
            <label for="stockedDate">Stocked Date:</label>
            <input type="date" name="stockedDate" id="stockedDate" value="<?= $stockedDate; ?>" required>
        </div>

        <div class="form-group">
            <label for="expiryDate">Expiry Date:</label>
            <input type="date" name="expiryDate" id="expiryDate" value="<?= $expiryDate; ?>" required>
        </div>

        <input type="submit" value="Update Stock">
    </form>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
