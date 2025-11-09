<?php
include "../includes/config.php";

// Retrieve form data with fallback values
$visitDate = $_POST['visitDate'];
$mat_id = $_POST['mat_id'];
$clientName = $_POST['clientName'];
$nickName = $_POST['nickName'];
$sname = $_POST['sname'];
$dob = $_POST['dob'];
$reg_date = $_POST['reg_date'];
$sex = $_POST['sex'];
$marital_status = $_POST['marital_status'];
$hiv_status = $_POST['hiv_status'];
$art_regimen = isset($_POST['art_regimen']) ? $_POST['art_regimen'] : 'Not Provided';
$regimen_type = isset($_POST['regimen_type']) ? $_POST['regimen_type'] : 'Not Provided';
$tb_status = $_POST['tb_status'];
$tb_regimen = isset($_POST['tb_regimen']) ? $_POST['tb_regimen'] : '';
$tb_start_date = isset($_POST['tb_start_date']) ? $_POST['tb_start_date'] : null;
$tb_end_date = isset($_POST['tb_end_date']) ? $_POST['tb_end_date'] : null;
$tpt_regimen = isset($_POST['tpt_regimen']) ? $_POST['tpt_regimen'] : '';
$tpt_start_date = isset($_POST['tpt_start_date']) ? $_POST['tpt_start_date'] : null;
$tpt_end_date = isset($_POST['tpt_end_date']) ? $_POST['tpt_end_date'] : null;
$hepc_status = $_POST['hepc_status'];
$other_status = $_POST['other_status'];
$clinical_notes = $_POST['clinical_notes'];
$current_status = $_POST['current_status'];
$last_vlDate = $_POST['last_vlDate'];
$results = $_POST['results'];
$clinician_name = $_POST['clinician_name'];
$next_appointment = $_POST['next_appointment'];
$appointment_status = $_POST['appointment_status'];

// Handle empty dates for database
$last_vlDate = empty($last_vlDate) ? null : $last_vlDate;
$next_appointment = empty($next_appointment) ? null : $next_appointment;
$tb_start_date = empty($tb_start_date) ? null : $tb_start_date;
$tb_end_date = empty($tb_end_date) ? null : $tb_end_date;
$tpt_start_date = empty($tpt_start_date) ? null : $tpt_start_date;
$tpt_end_date = empty($tpt_end_date) ? null : $tpt_end_date;

// Start transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Insert into medical_history table with all new fields
    $query1 = "INSERT INTO medical_history (
                visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex,
                marital_status, hiv_status, art_regimen, regimen_type, tb_status,
                tb_regimen, tb_start_date, tb_end_date, tpt_regimen, tpt_start_date,
                tpt_end_date, hepc_status, other_status, clinical_notes, current_status,
                last_vlDate, results, clinician_name, next_appointment, appointment_status
               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt1 = $conn->prepare($query1);

    if (!$stmt1) {
        throw new Exception("Error preparing medical_history query: " . $conn->error);
    }

    $stmt1->bind_param(
        'ssssssssssssssssssssssssssss',
        $visitDate,
        $mat_id,
        $clientName,
        $nickName,
        $sname,
        $dob,
        $reg_date,
        $sex,
        $marital_status,
        $hiv_status,
        $art_regimen,
        $regimen_type,
        $tb_status,
        $tb_regimen,
        $tb_start_date,
        $tb_end_date,
        $tpt_regimen,
        $tpt_start_date,
        $tpt_end_date,
        $hepc_status,
        $other_status,
        $clinical_notes,
        $current_status,
        $last_vlDate,
        $results,
        $clinician_name,
        $next_appointment,
        $appointment_status
    );

    if (!$stmt1->execute()) {
        throw new Exception("Error inserting into medical_history: " . $stmt1->error);
    }

    // Insert into viral_load table only if hiv_status is 'Positive'
    if (strtolower($hiv_status) === 'positive') {
        $query2 = "INSERT INTO viral_load (mat_id, clientName, dob, reg_date, sex, hiv_status, art_regimen, regimen_type, clinical_notes, last_vlDate, results, clinician_name, next_appointment)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($query2);

        if (!$stmt2) {
            throw new Exception("Error preparing viral_load query: " . $conn->error);
        }

        $stmt2->bind_param(
            'sssssssssssss',
            $mat_id,
            $clientName,
            $dob,
            $reg_date,
            $sex,
            $hiv_status,
            $art_regimen,
            $regimen_type,
            $clinical_notes,
            $last_vlDate,
            $results,
            $clinician_name,
            $next_appointment
        );

        if (!$stmt2->execute()) {
            throw new Exception("Error inserting into viral_load: " . $stmt2->error);
        }
    }

    // Update the patients table with current status and next appointment
    $query3 = "UPDATE patients
               SET current_status = ?, last_vlDate = ?, results = ?, next_appointment = ?
               WHERE mat_id = ?";
    $stmt3 = $conn->prepare($query3);

    if (!$stmt3) {
        throw new Exception("Error preparing patients update query: " . $conn->error);
    }

    $stmt3->bind_param(
        'sssss',
        $current_status,
        $last_vlDate,
        $results,
        $next_appointment,
        $mat_id
    );

    if (!$stmt3->execute()) {
        throw new Exception("Error updating patients table: " . $stmt3->error);
    }

    // Commit transaction
    $conn->commit();

    echo "<div style='background-color: #74f7c7; color: darkgreen; font-style: italic; font-size: 16px; height: 40px; line-height: 40px; padding: 5px 10px; margin-bottom: 10px; border-radius: 5px; text-align: center;'>
            Patient Clinical Information and Viral Load Data Updated Successfully
          </div>";

    // Redirect back to treatment page after 3 seconds
    header("Refresh: 3; url=clinician_follow_up_form.php");

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "<div style='color: red; background-color: #ffe6e6; padding: 10px; border-radius: 5px; border: 1px solid red;'>
            <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
} finally {
    // Close statements and connection
    if (isset($stmt1)) $stmt1->close();
    if (isset($stmt2)) $stmt2->close();
    if (isset($stmt3)) $stmt3->close();
    $conn->close();
}
?>