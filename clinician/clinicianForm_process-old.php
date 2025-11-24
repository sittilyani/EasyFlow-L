<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Retrieve form data with default values
$visitDate = $_POST['visitDate'] ?? null;
$mat_id = $_POST['mat_id'] ?? null;
$clientName = $_POST['clientName'] ?? null;
$nickName = $_POST['nickName'] ?? null;
$sname = $_POST['sname'] ?? null;
$dob = $_POST['dob'] ?? null;
$reg_date = $_POST['reg_date'] ?? null;
$sex = $_POST['sex'] ?? null;
$marital_status = $_POST['marital_status'] ?? null;
$hiv_status = $_POST['hiv_status'] ?? null;
$current_status = $_POST['current_status'] ?? null;

// Get clinician name from POST (which comes from session)
$clinician_name = $_POST['clinician_name'] ?? null;

// Validate current_status is 'Active'
if (strtolower(trim($current_status)) !== 'active') {
    die("<div style='background-color: #f2dede; color: #a94442; padding: 15px; margin: 20px; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> Please update patient status to 'Active' in patients tab before submitting clinical information.
        <br><br>
        <a href='../patients/view_all_patients.php' style='color: #a94442; text-decoration: underline;'>Go back to patient list</a>
    </div>");
}

// Handle ART fields - default to 'None' if not provided or empty
$art_regimen = !empty($_POST['art_regimen']) ? $_POST['art_regimen'] : 'None';
$regimen_type = !empty($_POST['regimen_type']) ? $_POST['regimen_type'] : 'None';

$tb_status = $_POST['tb_status'] ?? null;
$hepc_status = $_POST['hepc_status'] ?? null;
$other_status = $_POST['other_status'] ?? null;
$clinical_notes = $_POST['clinical_notes'] ?? null;
$next_appointment = $_POST['next_appointment'] ?? null;
$last_vlDate = $_POST['last_vlDate'] ?? null;
$results = $_POST['results'] ?? null;

// Validate required fields
if (empty($mat_id) || empty($clientName)) {
    die("<div style='background-color: #f2dede; color: #a94442; padding: 15px; margin: 20px; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> MAT ID and Client Name are required.
    </div>");
}

if (empty($clinician_name)) {
    die("<div style='background-color: #f2dede; color: #a94442; padding: 15px; margin: 20px; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> Clinician name is required. Please log in again.
    </div>");
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert into medical_history table
    $query = "INSERT INTO medical_history (visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex, marital_status, hiv_status, art_regimen, regimen_type, tb_status, hepc_status, other_status, clinical_notes, clinician_name, next_appointment, current_status, last_vlDate, results) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Error preparing medical_history statement: " . $conn->error);
    }

    if (!$stmt->bind_param('sssssssssssssssssssss', $visitDate, $mat_id, $clientName, $nickName, $sname, $dob, $reg_date, $sex, $marital_status, $hiv_status, $art_regimen, $regimen_type, $tb_status, $hepc_status, $other_status, $clinical_notes, $clinician_name, $next_appointment, $current_status, $last_vlDate, $results)) {
        throw new Exception("Error binding parameters: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error inserting into medical_history: " . $stmt->error);
    }

    $stmt->close();

    // Update next_appointment in patients table
    if (!empty($next_appointment)) {
        $update_query = "UPDATE patients SET next_appointment = ? WHERE mat_id = ?";
        $update_stmt = $conn->prepare($update_query);

        if (!$update_stmt) {
            throw new Exception("Error preparing patients update statement: " . $conn->error);
        }

        if (!$update_stmt->bind_param('ss', $next_appointment, $mat_id)) {
            throw new Exception("Error binding update parameters: " . $update_stmt->error);
        }

        if (!$update_stmt->execute()) {
            throw new Exception("Error updating patients table: " . $update_stmt->error);
        }

        $update_stmt->close();
    }

    // Commit transaction
    $conn->commit();

    echo "<div style='background-color: #74f7c7; color: darkgreen; font-style: italic; font-size: 16px; padding: 15px; margin: 20px; border: 1px solid #5cb85c; border-radius: 4px;'>
        <strong>Success:</strong> Patient Clinical Information Updated Successfully
    </div>";
    header("Refresh: 3; url=index.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    die("<div style='background-color: #f2dede; color: #a94442; padding: 15px; margin: 20px; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>");
}

$conn->close();
?>