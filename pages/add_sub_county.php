<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the form
    $sub_county_name = $_POST['sub_county_name']; // Assuming the form field name is sub_county_name
    $county_name = $_POST['county_name'];
    // Validate data (you can add more validation as needed)

    // Insert data into tblsub_counties table
    $sql = "INSERT INTO sub_counties (sub_county_name, county_name) VALUES ('$sub_county_name', 'county_name')";

    if ($conn->query($sql) === TRUE) {
        echo "New sub county added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Sub County</title>
     <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="../includes/style.css" type="text/css">
    <style>

    </style>
</head>
<body>
    <h2>Add Sub County</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="sub_county_name">Sub County Name:</label> <br>
        <input type="text" id="sub_county_name" name="sub_county_name" required>
        <br>
        <label for="county_name">County Name</label><br>
        <select id="county_name" name="county_name" required>
            <option value="">Select County</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM counties";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['county_name'] . "'>" . $row['county_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        <br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>
