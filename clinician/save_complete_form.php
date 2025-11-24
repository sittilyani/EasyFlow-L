<?php
// save_complete_form.php - Save complete form data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complete'])) {
    $patient_id = $_POST['patient_id'];
    $clinician_id = $_POST['clinician_id'];
    $triage_id = $_POST['triage_id'];

    // Prepare clinical encounter data
    // ... (your existing clinical data processing code)

    // Insert into clinical_encounters
    $sql = "INSERT INTO clinical_encounters (triage_id, patient_id, clinician_id, ...) VALUES (?, ?, ?, ...)";
    $stmt = $conn->prepare($sql);
    // ... bind parameters

    if ($stmt->execute()) {
        $encounter_id = $stmt->insert_id;
        $stmt->close();

        // Update triage status to complete
        $update_sql = "UPDATE triage_services SET status = 'complete' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('i', $triage_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Save drug histories
        // ... (your existing drug history code)

        // Clear session data
        unset($_SESSION['triage_data']);
        unset($_SESSION['clinical_data']);

        // Redirect to success page
        header("Location: ../clinician/clinical_encounter_search.php?success=1");
        exit();
    } else {
        die("Error saving clinical encounter: " . $stmt->error);
    }
}
?>