<?php
session_start();
include '../includes/config.php'; // Adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_calibration'])) {
    $pump_id = $_POST['pump_id'];
    $calibration_factor = $_POST['calibration_factor'];
    $concentration = $_POST['concentration'] ?? 5.00;
    $tubing_type = $_POST['tubing_type'] ?? '';
    $notes = $_POST['notes'] ?? 'Manual calibration adjustment';

    // Get username from session or use default
    $calibrated_by = $_SESSION['username'] ?? 'Unknown User';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Deactivate old calibrations for this pump
        $deactivateStmt = $conn->prepare("UPDATE pump_calibration SET is_active = FALSE WHERE pump_id = ?");
        $deactivateStmt->bind_param('i', $pump_id);
        $deactivateStmt->execute();
        $deactivateStmt->close();

        // Insert new calibration
        $insertStmt = $conn->prepare("
            INSERT INTO pump_calibration (pump_id, calibration_factor, concentration_mg_per_ml, tubing_type, calibrated_by, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param('iddsss', $pump_id, $calibration_factor, $concentration, $tubing_type, $calibrated_by, $notes);

        if ($insertStmt->execute()) {
            $conn->commit();
            echo 'Calibration updated successfully!';

            // Also update session if this pump is being used
            if (isset($_SESSION['factor'])) {
                $_SESSION['factor'] = $calibration_factor;
            }
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
        $insertStmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo 'Error: ' . $e->getMessage();
    }
} else {
    http_response_code(400);
    echo 'Invalid request';
}
?>