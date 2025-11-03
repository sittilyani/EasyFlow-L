<?php
include '../includes/config.php'; // Include your database connection file

// Initialize the variable with 0
$qty_in = 0;

// Calculate default dates for the previous month
$defaultEndDate = date('Y-m-t', strtotime('last month')); // Last day of previous month
$defaultStartDate = date('Y-m-01', strtotime('last month')); // First day of previous month

// Get selected dates from form submission or use defaults
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Validate dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// Query to retrieve sum of qty_in from stock_movements table for the selected period
$sql = "SELECT SUM(qty_in) AS total_qty_in
        FROM stock_movements
        WHERE trans_date BETWEEN '$startDate' AND '$endDate'
        AND drugname = 'Naltrexone 150mg'
        AND transactionType = 'Receiving'";

$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    // Fetch the row
    $row = $result->fetch_assoc();
    // Assign the value to the variable (handle NULL from SUM)
    $qty_in = $row['total_qty_in'] !== null ? $row['total_qty_in'] : 0;
    // Output the quantity
    echo $qty_in;
} else {
    // Query failed
    echo "0";
}

// Close the database connection
/*$conn->close();  */
?>