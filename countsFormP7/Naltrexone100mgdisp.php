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

    $total_dispensed_naltrexone = 0;

    // Define the SQL query to sum total naltrexone dispensed
    $query = "SELECT SUM(dosage) AS total_dispensed_naltrexone
            FROM pharmacy
            WHERE drugname LIKE 'Naltrexone 100mg'
            AND dispDate BETWEEN '$startDate' AND '$endDate'";

    $result = $conn->query($query);

    if ($result) {
        // Fetch the row
        $row = $result->fetch_assoc();
        // Handle NULL from SUM (when no records found)
        $total_dispensed_naltrexone = $row['total_dispensed_naltrexone'] !== null ? $row['total_dispensed_naltrexone'] : 0;
        // Output the total (round to 2 decimal places)
        echo number_format($total_dispensed_naltrexone, 2);
    } else {
        echo "0.00"; // If query failed, display 0
    }
?>