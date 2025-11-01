<?php
include '../includes/config.php'; // Include your database connection file

// Initialize the variable with 0
$opening_bal = 0;

// Calculate default dates for the previous month
$defaultEndDate = date('Y-m-t', strtotime('last month')); // Last day of previous month
$defaultStartDate = date('Y-m-01', strtotime('last month')); // First day of previous month

// Get selected dates from form submission or use defaults
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Validate dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// First, try to get opening_bal from the first day of the selected month
$sql1 = "SELECT opening_bal
    FROM stock_movements
    WHERE trans_date = '$startDate' AND drugname = 'Methadone'
    LIMIT 1";

$result1 = $conn->query($sql1);

// Check if the query was successful and returned a row
if ($result1 && $result1->num_rows > 0) {
    // Fetch the row
    $row1 = $result1->fetch_assoc();
    // Assign the value to the variable
    $opening_bal = $row1['opening_bal'];
} else {
    // If no opening_bal found on the first day, get total_qty from the last transaction of the previous month
    $previousMonthEnd = date('Y-m-t', strtotime($startDate . ' -1 month'));

    $sql2 = "SELECT total_qty
        FROM stock_movements
        WHERE trans_date <= '$previousMonthEnd' AND drugname = 'Naltrexone 50mg'
        ORDER BY trans_date DESC
        LIMIT 1";

    $result2 = $conn->query($sql2);

    if ($result2 && $result2->num_rows > 0) {
        // Fetch the row
        $row2 = $result2->fetch_assoc();
        // Assign the total_qty as the opening balance
        $opening_bal = $row2['total_qty'];
    }
}

// Output the opening balance
echo $opening_bal;

// Close the database connection
$conn->close();
?>