<?php
// ---------------------------------------------------------------
// 1. CONFIG & INPUT HANDLING
// ---------------------------------------------------------------

// Include the database connection file
require_once('../includes/config.php'); // Use require_once for essential dependency

// Calculate default dates for the previous month
$defaultEndDate = date('Y-m-t', strtotime('last month'));     // Last day of previous month
$defaultStartDate = date('Y-m-01', strtotime('last month')); // First day of previous month

// Get selected dates from form submission or use defaults
// Use filter_input for safer input retrieval
$startDate = filter_input(INPUT_GET, 'start_date') ?? $defaultStartDate;
$endDate = filter_input(INPUT_GET, 'end_date') ?? $defaultEndDate;

// Validate and format dates (though prepared statements bind these as strings)
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

$average_dose_methadone = 0.00; // Initialize as a float for consistency

// ---------------------------------------------------------------
// 2. DATABASE QUERY (Using Prepared Statements)
// ---------------------------------------------------------------

// Define the SQL query to calculate the average patient dose for buprenorphine
// We use ? placeholders for security.
$query = "SELECT AVG(dosage) AS average_dose_buprenorphine
                    FROM pharmacy
                    WHERE drugname LIKE '%buprenorphine%'
                    AND dispDate BETWEEN ? AND ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
        // Handle prepare error
        error_log("Failed to prepare statement: " . $conn->error);
        echo "0.00";
        exit;
}

// Bind the date parameters as strings
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

// ---------------------------------------------------------------
// 3. RESULT PROCESSING AND FIX FOR DEPRECATED WARNING
// ---------------------------------------------------------------

if ($result && $row = $result->fetch_assoc()) {
        // The AVG function will always return one row, even if the value is NULL.
        // Use the null coalescing operator (?? 0.0) to safely handle NULL results
        // and cast to float to prevent the Deprecated warning in number_format().
        $average_dose_buprenorphine = (float)($row['average_dose_buprenorphine'] ?? 0.0);

        // Output the average (round to 2 decimal places)
        echo number_format($average_dose_buprenorphine, 2);
} else {
        // Should generally not be reached for an aggregate function, but good for safety
        echo "0.00";
}

$stmt->close();
// Assuming $conn closure happens elsewhere or at script end, as defined in config.php
?>