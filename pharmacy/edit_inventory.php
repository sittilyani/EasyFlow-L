<?php
include '../includes/config.php';

// Check if ID parameter is passed through the URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    // Get ID from URL
    $id = trim($_GET['drugID']);

    // Prepare SELECT statement
    $sql = "SELECT * FROM stock_movements WHERE drugID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a record was found
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $drugname = $row['drugname'];
        $total_qty = $row['total_qty'];
        $trans_date = $row['trans_date'];
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
    $drugname = $_POST['drugname'];
    $total_qty = $_POST['total_qty'];
    $trans_date = $_POST['trans_date'];
    $expiryDate = $_POST['expiryDate'];

    // Prepare UPDATE statement
    $sql = "UPDATE pharmacy SET drugname = ?, total_qty = ?, trans_date = ?, expiryDate = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $drugname, $total_qty, $trans_date, $expiryDate, $id);

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
            <label for="drugname">Drug Name:</label>
            <input type="text" name="drugname" id="drugname" value="<?= $drugname; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="total_qty">Stock Quantity:</label>
            <input type="number" name="total_qty" id="total_qty" value="<?= $total_qty; ?>" required>
        </div>

        <div class="form-group">
            <label for="trans_date">Stocked Date:</label>
            <input type="date" name="trans_date" id="trans_date" value="<?= $trans_date; ?>" required>
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
