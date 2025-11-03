<?php
// Include the configuration file
include '../includes/config.php';

// Get the current date
$currentDate = date("Y-m-d");
$current_display_date = date("F j, Y"); // Format: January 15, 2025

// SQL query for methadone
$sql = "SELECT COUNT(DISTINCT mat_id) AS total_clients FROM pharmacy WHERE DATE(dispDate) = ? AND dosage IS NOT NULL";

$stmt = $conn->prepare($sql);

// Check if the query was prepared successfully
if ($stmt) {
    // Bind the parameter
    $stmt->bind_param("s", $currentDate);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result) {
        // Fetch the row
        $row = $result->fetch_assoc();
        $total_clients = $row['total_clients'];

        if ($total_clients === null || $total_clients == 0) {
            echo '<p>No clients served on ' . htmlspecialchars($current_display_date) . '.</p>';
        } else {
            echo '<p>Clients on ' . htmlspecialchars($current_display_date) . ': <span style="font-weight: bold; color: red;">' . htmlspecialchars($total_clients) . '&nbsp;client(s)</span></p>';
        }
    } else {
        error_log("Error executing query for clients: " . $stmt->error);
        echo '<p style="color: red;">Error retrieving clients data.</p>';
    }

    // Close the statement
    $stmt->close();
} else {
    error_log("Database error preparing query for clients: " . $conn->error);
    echo '<p style="color: red;">Database error occurred.</p>';
}

// Close the connection if it exists and is active
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}

ob_end_flush();
?>