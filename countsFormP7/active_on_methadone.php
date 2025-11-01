<?php
    // Include the database connection file
    include_once('../includes/config.php');


    // Calculate default dates for the previous month
    $defaultEndDate = date('Y-m-t', strtotime('last month')); // Last day of previous month
    $defaultStartDate = date('Y-m-01', strtotime('last month')); // First day of previous month

    // Get selected dates from form submission or use defaults
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

    // Validate dates
    $startDate = date('Y-m-d', strtotime($startDate));
    $endDate = date('Y-m-d', strtotime($endDate));

    $active_on_methadone = 0;
    $active_on_naltrexone = 0;
    $active_on_buprenorphine = 0;
    $average_doses_methadone =0;
    $average_doses_buprenorphine = 0;

    // Define the SQL query to count active patients on Buprenorphine within the last 6 days
    $query = "SELECT
        COUNT(DISTINCT mat_id) AS total_met_count
    FROM pharmacy
    WHERE
        drugname = 'Methadone'
        AND dispDate BETWEEN '$startDate' AND '$endDate'";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
            // Fetch the count
            $row = $result->fetch_assoc();
            $total_met_count = $row['total_met_count'];

            // Output the count
            echo $total_met_count;
    } else {
            echo "0"; // If no active patientss on Buprenorphine found within the last 6 days, display 0
    }
?>