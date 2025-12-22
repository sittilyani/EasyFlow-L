<?php
    session_start();

    // Check if the user is not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("location: ../index.php");
        exit;
    }
    // include '../includes/restrict_access.php';
    include '../includes/footer.php';
    include '../includes/header.php';

    // Check if the user is logged in and has the necessary information
    if (isset($_SESSION['email'], $_SESSION['first_name'], $_SESSION['last_name'])) {
        $email = $_SESSION['email'];
        $first_name = $_SESSION['first_name'];
        $last_name = $_SESSION['last_name'];

        // You can concatenate first_name and last_name with a space
        $full_name = $first_name . ' ' . $last_name;
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/favicons/Lamu_County-removebg.ico" type="image/x-icon">
    <title>Admin</title>

    <style>
            body {
                margin: 0;
                padding: 0;
                font-family: "Times New Roman", Times, serif;
                font-size: 16px;
            }

            .container-display {
                display: grid;
                /*grid-template-columns: repeat(2, 1fr); /* Create two columns /
                grid-template-rows: repeat(2, 1fr); * Create two rows */
                grid-template-columns: repeat(4, 1fr); /* Create four columns */
                grid-template-rows: 1fr; /* Create one row */
                grid-gap: 20px; /* Adjust the gap between grid elements */
                margin-left: auto; /* Add margin for spacing */
                margin-right: auto; /* Add margin for spacing */
                margin-top: 40px;
                width: 600px;
                height: 400px;
                justify-content: center;


            .grid-item {
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 10px;
                text-align: right;
                width: 250px;
                height: 350px;
                background-color: #E5E8E8;
            }

            #grid-item-2{
                background-color: #FBD875;
                width: 250px;
            }
            #grid-item-3{
                background-color: #75C0FB;
                width: 250px;
            }

            h3{
                font-weight:bold;
                color: #2C3162;
            }


    </style>
</head>
<body>


    <div class="container-display">
        <!--<div class="grid-item">

             <h3>USERS </h3>
             Admins:&nbsp;&nbsp;<?php include '../counts/admin_count.php'; ?><br>
             Clinicians:&nbsp;&nbsp;<?php include '../counts/clinician_count.php'; ?><br>
             Pharmacists:&nbsp;&nbsp;<?php include '../counts/pharmacist_count.php'; ?> <br>
             Receptionists:&nbsp;&nbsp;<?php include '../counts/receptionist_count.php'; ?> <br>
             Psychologists:&nbsp;&nbsp;<?php include '../counts/psychologist_count.php'; ?>  <br>
             HRIOs:&nbsp;&nbsp;<?php include '../counts/hrio_count.php'; ?> <br>
             Peer Educator:&nbsp;&nbsp;<?php include '../counts/peer_count.php'; ?>


            </div>-->
        <div class="grid-item" id='grid-item-2'>
            <h3>DRUGS</h3>
            <!--<p>Clients Served Today:</p>-->
            <?php
                // Include the configuration file
                include '../includes/config.php';

                // Get the current date
                $currentDate = date("Y-m-d");

                // SQL query
                $sql = "SELECT COUNT(DISTINCT mat_id) AS unique_mat_ids FROM pharmacy WHERE DATE(visitDate) = ? AND dosage IS NOT NULL";
                $stmt = $conn->prepare($sql);

                // Check if the query was prepared successfully
                if ($stmt) {
                    // Bind the parameter
                    $stmt->bind_param("s", $currentDate);

                    // Execute the query
                    $stmt->execute();

                    // Get the result
                    $result = $stmt->get_result();

                    // Fetch the row
                    $row = $result->fetch_assoc();

                    // Output the count of unique mat_ids
                    echo '<p>Number of Clients served today: <span style="font-weight: bold; color: green;" >' . $row['unique_mat_ids'] . '</span></p>';

                    // Close the statement
                    $stmt->close();
                } else {
                    // Output error message if query preparation fails
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }

                // Close the connection
                $conn->close();
                ?>


            <?php
                // Include the configuration file
                include '../includes/config.php';

                // Get the current date
                $currentDate = date("Y-m-d");

                // SQL query
                /*$sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) = ? AND dosage IS NOT NULL"; */
                $sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) = ? AND dosage IS NOT NULL AND drugName = 'Methadone'";

                $stmt = $conn->prepare($sql);

                // Check if the query was prepared successfully
                if ($stmt) {
                    // Bind the parameter
                    $stmt->bind_param("s", $currentDate);

                    // Execute the query
                    $stmt->execute();

                    // Get the result
                    $result = $stmt->get_result();

                    // Fetch the row
                    $row = $result->fetch_assoc();

                    // Output the total dosage dispensed today
                    echo '<p>Methadone Disp Today: <span style="font-weight: bold; color: #2C3162;" >' . $row['total_dosage'] . '&nbsp;mg</span></p>';

                    // Close the statement
                    $stmt->close();
                } else {
                    // Output error message if query preparation fails
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }

                // Close the connection
                $conn->close();
                ?>


            <?php
                    // Include the configuration file
                    include '../includes/config.php';

                    // Get the current month
                    $currentMonth = date("m");
                    $currentYear = date("Y");

                    // Calculate the start and end date of the current month
                    $startDate = date("Y-m-01");
                    $endDate = date("Y-m-t");

                    // SQL query
                    /*$sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ?";  */
                    $sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Methadone'";

                    $stmt = $conn->prepare($sql);

                    // Check if the query was prepared successfully
                    if ($stmt) {
                        // Bind the parameters
                        $stmt->bind_param("ss", $startDate, $endDate);

                        // Execute the query
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Fetch the row
                        $row = $result->fetch_assoc();

                        // Output the total dosage dispensed for the month
                        echo '<p>Methadone Disp in the Month: <span style="font-weight: bold; color: #2C3162;" >' . $row['total_dosage'] . '&nbsp;mg</p>';

                        // Close the statement
                        $stmt->close();
                    } else {
                        // Output error message if query preparation fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

            <?php
                    // Include the configuration file
                    include '../includes/config.php';

                    // Get the current month
                    $currentMonth = date("m");
                    $currentYear = date("Y");

                    // Calculate the start and end date of the current month
                    $startDate = date("Y-m-01");
                    $endDate = date("Y-m-t");

                    // SQL query
                    /*$sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ?";  */
                    $sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Buprenorphine 2mg'";

                    $stmt = $conn->prepare($sql);

                    // Check if the query was prepared successfully
                    if ($stmt) {
                        // Bind the parameters
                        $stmt->bind_param("ss", $startDate, $endDate);

                        // Execute the query
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Fetch the row
                        $row = $result->fetch_assoc();

                        // Output the total dosage dispensed for the month
                        echo '<p>Buprenor 2mg Disp in the Month: <span style="font-weight: bold; color: #2C3162;" >' . $row['total_dosage'] . '&nbsp;mg</p>';

                        // Close the statement
                        $stmt->close();
                    } else {
                        // Output error message if query preparation fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

            <?php
                    // Include the configuration file
                    include '../includes/config.php';

                    // Get the current month
                    $currentMonth = date("m");
                    $currentYear = date("Y");

                    // Calculate the start and end date of the current month
                    $startDate = date("Y-m-01");
                    $endDate = date("Y-m-t");

                    // SQL query
                    /*$sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ?";  */
                    $sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Buprenorphine 8mg'";

                    $stmt = $conn->prepare($sql);

                    // Check if the query was prepared successfully
                    if ($stmt) {
                        // Bind the parameters
                        $stmt->bind_param("ss", $startDate, $endDate);

                        // Execute the query
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Fetch the row
                        $row = $result->fetch_assoc();

                        // Output the total dosage dispensed for the month
                        echo '<p>Buprenor 8mg Disp in the Month: <span style="font-weight: bold; color: #2C3162;" >' . $row['total_dosage'] . '&nbsp;mg</p>';

                        // Close the statement
                        $stmt->close();
                    } else {
                        // Output error message if query preparation fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

            </div>
        <div class="grid-item" id='grid-item-3'>
            <h3>STOCKS</h3>
            <!--Drugs Stocked:&nbsp;&nbsp; --><?php include '../counts/drugStockedCount.php'; ?>

            <p>
                <?php
                    include '../includes/config.php';

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS total_qty
                            FROM stock_movements
                            JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                            WHERE drug.drugID = 2
                            AND drug.drugName = 'Methadone'
                            ORDER BY stock_movements.trans_date DESC
                            LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Methadone Balance: <span style="font-weight: bold; color: #2C3162;">' . $row['total_qty'] . '&nbsp;mg</strong></p>';
                        } else {
                            echo '<p>No Methadone stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>


            </p>

            <!--Buprenorphine 2mg Stock Balance-->
            <p>
                <?php
                    include '../includes/config.php';

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS total_qty
                            FROM stock_movements
                            JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                            WHERE drug.drugID = 6
                            AND drug.drugName = 'Buprenorphine 2mg'
                            ORDER BY stock_movements.trans_date DESC
                            LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Buprenor 2mg Bal: <span style="font-weight: bold; color: #2C3162;">' . $row['total_qty'] . '&nbsp;mg</strong></p>';
                        } else {
                            echo '<p>No Buprenor 2mg stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>
            </p>

            <!--Buprenorphine 4mg Stock Balance-->
            <p>
                <?php
                    include '../includes/config.php';

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS total_qty
                            FROM stock_movements
                            JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                            WHERE drug.drugID = 7
                            AND drug.drugName = 'Buprenorphine 4mg'
                            ORDER BY stock_movements.trans_date DESC
                            LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Buprenor 4mg Bal: <span style="font-weight: bold; color: #2C3162;">' . $row['total_qty'] . '&nbsp;mg</strong></p>';
                        } else {
                            echo '<p>No Buprenor 4mg stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>
            </p>

            <!--Buprenorphine 8mg Stock Balance-->
            <p>
                <?php
                    include '../includes/config.php';

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS total_qty
                            FROM stock_movements
                            JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                            WHERE drug.drugID = 8
                            AND drug.drugName = 'Buprenorphine 8mg'
                            ORDER BY stock_movements.trans_date DESC
                            LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Buprenor 8mg Bal: <span style="font-weight: bold; color: #2C3162;">' . $row['total_qty'] . '&nbsp;mg</strong></p>';
                        } else {
                            echo '<p>No Buprenor 8mg stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>
            </p>

            <!--<a href="../views/buprenorphine2_balance.php">Buprenorphine 2mg Balance:</a>-->
            <p>No. of drugs out of Stock:</p>


            </div>

        <div class="grid-item">
            <h3>PATIENTS</h3>
            <!--Active:&nbsp;&nbsp;<span style="font-weight: bold; color: #2C3162;" ><?php include '../counts/active_patient_count.php'; ?></span><br>-->

            Active:&nbsp;&nbsp;<a href="../views/view_active_patients.php" style="font-weight: bold; color: #green; text-decoration: none;" target="_blank"><?php include '../counts/active_patient_count.php'; ?></a>  <br>
            Defaulters:&nbsp;&nbsp;<a href="../views/view_defaulted_patients.php" style="font-weight: bold; color: red; text-decoration: none;" target="_blank"><?php include '../counts/defaulted_patient_count.php'; ?></a>  <br>
            Died:&nbsp;&nbsp;<a href="../views/view_dead_patients.php" style="font-weight: bold; color: red; text-decoration: none;" target="_blank"><?php include '../counts/dead_patient_count.php'; ?></a>  <br>
            Weaned Off:&nbsp;&nbsp;<a href="../views/view_weaned_patients.php" style="font-weight: bold; color: green; text-decoration: none;" target="_blank"><?php include '../counts/weaned_patient_count.php'; ?></a>  <br>
            LTFU:&nbsp;&nbsp;<a href="../views/view_ltfu_patients.php" style="font-weight: bold; color: red; text-decoration: none;" target="_blank"><?php include '../counts/lost_patient_count.php'; ?></a>  <br>
            Discontinued:&nbsp;&nbsp;<a href="../views/view_stopped_patients.php" style="font-weight: red; color: #2C3162; text-decoration: none;" target="_blank"><?php include '../counts/stopped_patient_count.php'; ?></a>  <br>
            Transit:&nbsp;&nbsp;<a href="../views/view_transit_patients.php" style="font-weight: bold; color: blue; text-decoration: none;" target="_blank"><?php include '../counts/transit_patient_count.php'; ?></a>  <br>
            Transfer Out:&nbsp;&nbsp;<a href="../views/view_transout_patients.php" style="font-weight: bold; color: blue; text-decoration: none;" target="_blank"><?php include '../counts/transout_patient_count.php'; ?></a>  <br>
            Inmates:&nbsp;&nbsp;<a href="../views/view_inmates_patients.php" style="font-weight: bold; color: green; text-decoration: none;" target="_blank"><?php include '../counts/inmates_patient_count.php'; ?></a>  <br>
            Ever Enrolled:&nbsp;&nbsp;<a href="../views/view_all_patients.php" style="font-weight: bold; color: blue; text-decoration: none;" target="_blank"><?php include '../counts/new_patient_count.php'; ?></a>  <br>

            <span style ="color: red">Clients Missed Doses Today:</span>&nbsp;&nbsp;<a href="../views/view_appointment_patients.php" style="font-weight: bold; color: red; text-decoration: none;" target="_blank"><?php include '../counts/today_missed_count.php'; ?></a>  <br>

            <h4 style="color: red;">Click the numbers to view <span style='font-size:50px;'>&#9757;</span></h4>

            </div>

    </div>

        <!-- Your content goes here -->
        <!-- Include jQuery -->
        <script src="../assets/js/jquery-3.7.1.min.js"></script>

        <!-- Your existing code -->

        <script>
            $(document).ready(function () {
                // Handle click event to toggle active class
                $(".dropdown").click(function () {
                    $(this).toggleClass("active");
                });
            });
        </script>

</body>
</html>

