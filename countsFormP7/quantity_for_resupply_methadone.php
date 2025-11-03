<?php
    // This file calculates: (methadonedisp * 3) - physical_count
    // Note: physical_count should be passed from the form

    // Include the database connection file
    include_once('../includes/config.php');

    // Calculate default dates for the previous month
    $defaultEndDate = date('Y-m-t', strtotime('last month'));
    $defaultStartDate = date('Y-m-01', strtotime('last month'));

    // Get selected dates from form submission or use defaults
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

    // Validate dates
    $startDate = date('Y-m-d', strtotime($startDate));
    $endDate = date('Y-m-d', strtotime($endDate));

    $total_dispensed_methadone = 0;

    // Get total methadone dispensed
    $query = "SELECT SUM(dosage) AS total_dispensed_methadone
            FROM pharmacy
            WHERE drugname LIKE '%methadone%'
            AND dispDate BETWEEN '$startDate' AND '$endDate'";

    $result = $conn->query($query);

    if ($result) {
        $row = $result->fetch_assoc();
        $total_dispensed_methadone = $row['total_dispensed_methadone'] !== null ? $row['total_dispensed_methadone'] : 0;
    }

    // Get physical count from GET parameter (if submitted)
    $physical_count = isset($_GET['physical_count_methadone']) ? floatval($_GET['physical_count_methadone']) : 0;

    // Calculate quantity for resupply: (dispensed * 3) - physical_count
    $quantity_resupply = ($total_dispensed_methadone * 3) - $physical_count;

    // Output the result
    echo number_format($quantity_resupply, 2);
?>