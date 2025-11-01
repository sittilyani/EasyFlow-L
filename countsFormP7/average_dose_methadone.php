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

    $average_dose_methadone = 0;

    // Define the SQL query to count average patient doses on methadone
    $query = "SELECT AVG(dosage) AS average_dose_methadone
            FROM pharmacy
            WHERE drugname LIKE '%methadone%'
            AND dispDate BETWEEN '$startDate' AND '$endDate'";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        // Fetch the average
        $row = $result->fetch_assoc();
        $average_dose_methadone = $row['average_dose_methadone'];
        // Output the average (round to 2 decimal places)
        echo number_format($average_dose_methadone, 2);
    } else {
        echo "0"; // If no records found, display 0
    }
?>