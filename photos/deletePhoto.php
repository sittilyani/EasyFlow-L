<?php
session_start();
include('../includes/config.php');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    // First, get the mat_id from patients table
    $sql = "SELECT mat_id FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $p_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $mat_id = $row['mat_id'];

        // Delete all photos associated with this mat_id (BLOB data will be deleted automatically)
        $delete_sql = "DELETE FROM photos WHERE mat_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("s", $mat_id);

        if ($delete_stmt->execute()) {
            $affected_rows = $delete_stmt->affected_rows;

            if ($affected_rows > 0) {
                $message = "Photo(s) deleted successfully! ($affected_rows photo(s) removed)";
                $status = "success";
            } else {
                $message = "No photos found to delete for this patient.";
                $status = "warning";
            }
        } else {
            $message = "Error: Unable to delete photo(s). " . $delete_stmt->error;
            $status = "error";
        }

        $delete_stmt->close();
    } else {
        $message = "Error: Patient record not found.";
        $status = "error";
    }

    $stmt->close();
} else {
    $message = "Error: Invalid request. Patient ID missing.";
    $status = "error";
}

$conn->close();

// Redirect back to read.php with message
header("Location: client_search.php?message=" . urlencode($message) . "&status=" . $status);
exit();
?>