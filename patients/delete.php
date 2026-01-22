<?php
// Start the session
session_start();

// Include the database configuration file
include('../includes/config.php');

// Check if the p_id parameter is set in the URL
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    // Delete related records in the laboratory table
    $sql_delete_lab = "DELETE FROM laboratory WHERE mat_id = ?";
    $stmt_delete_lab = $conn->prepare($sql_delete_lab);
    $stmt_delete_lab->bind_param("s", $p_id);
    $stmt_delete_lab->execute();
    $stmt_delete_lab->close();

    // Delete related records in the medical history table
    $sql_delete_medhist = "DELETE FROM medical_history WHERE mat_id = ?";
    $stmt_delete_medhist = $conn->prepare($sql_delete_medhist);
    $stmt_delete_medhist->bind_param("s", $p_id);
    $stmt_delete_medhist->execute();
    $stmt_delete_medhist->close();
}

// Prepare the delete statement for the patients table
$sql = "DELETE FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $p_id);

// Execute the delete statement
if ($stmt->execute()) {
    // Deletion successful
    $message = "Record deleted successfully.";
} else {
    // Deletion failed
    $message = "Error deleting record: " . $conn->error;
}

// Close the statement
$stmt->close();

// Redirect with the success message
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Delete Status</title>
    <style>
        .message {
            padding: 20px;
            text-align: center;
            font-size: 18px;
            color: green;
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 5px;
        }
    </style>
    <script>
        setTimeout(function() {
            window.location.href = '../patients/view_all_patients.php'; // Redirect after 3 seconds
        }, 3000);
    </script>
</head>
<body>
    <div class='message'>
        <p>{$message}</p>
    </div>
</body>
</html>
";
exit();
?>
