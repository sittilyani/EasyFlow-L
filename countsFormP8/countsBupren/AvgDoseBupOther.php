<?php
// Include the database connection file
require_once '../includes/config.php';

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// SQL query to calculate the average dosage
$sql = "SELECT AVG(dosage) AS average_dosage
        FROM pharmacy

        WHERE drugname = 'methadone'
        AND sex NOT IN ('male', 'female')
        AND MONTH(visitDate) = ?
        AND YEAR(visitDate) = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind parameters and execute the statement
$stmt->bind_param('ss', $currentMonth, $currentYear);
$stmt->execute();

// Bind the result variable
$stmt->bind_result($average_dosage);

// Fetch the result
$stmt->fetch();

// Close the statement
$stmt->close();

// Check if the result is NULL and set to 0 if so
$average_dosage = $average_dosage ?? 0;

// Display the result
echo $average_dosage;
?>
