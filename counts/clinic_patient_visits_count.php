<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of workload users from the database
$sql = "SELECT COUNT(*) as workloadCount FROM medical_history";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$workloadCount = $result['workloadCount'];

// Output the count as plain text
echo $workloadCount;
?>
