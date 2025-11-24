<?php
// save_triage.php - Save Part A data to triage_services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_triage'])) {
    $patient_id = $_POST['patient_id'];
    $clinician_id = $_POST['clinician_id'];

    // Prepare triage data
    $facility_name = mysqli_real_escape_string($conn, $_POST['facility_name'] ?? '');
    $mfl_code = mysqli_real_escape_string($conn, $_POST['mfl_code'] ?? '');
    $county = mysqli_real_escape_string($conn, $_POST['county'] ?? '');
    $sub_county = mysqli_real_escape_string($conn, $_POST['sub_county'] ?? '');
    $enrolment_date = !empty($_POST['enrolment_date']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['enrolment_date']))) : null;
    $enrolment_time = $_POST['enrolment_time'] ?? null;
    $visit_type = isset($_POST['visit_type']) ? implode(',', $_POST['visit_type']) : '';
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
    $nickname = mysqli_real_escape_string($conn, $_POST['nickname'] ?? '');
    $mat_id = mysqli_real_escape_string($conn, $_POST['mat_id'] ?? '');
    $sex = mysqli_real_escape_string($conn, $_POST['sex'] ?? '');
    $presenting_complaints = mysqli_real_escape_string($conn, $_POST['presenting_complaints'] ?? '');

    // Vital signs
    $pulse = !empty($_POST['pulse']) ? intval($_POST['pulse']) : null;
    $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? intval($_POST['oxygen_saturation']) : null;
    $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure'] ?? '');
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $bmi = !empty($_POST['bmi']) ? floatval($_POST['bmi']) : null;
    $bmi_interpretation = $_POST['bmi_interpretation'] ?? '';

    // COWS data
    $cows_provider = mysqli_real_escape_string($conn, $_POST['cows_provider'] ?? '');
    $cows_date = $_POST['cows_date'] ?? null;

    // Collect COWS scores (simplified)
    $cows_scores = [];
    $cows_totals = [0, 0, 0, 0];
    $cows_interpretations = ['', '', '', ''];

    $cows_scores_json = json_encode($cows_scores);
    $cows_totals_json = json_encode($cows_totals);
    $cows_interpretations_json = json_encode($cows_interpretations);

    // Insert into triage_services
    $sql = "INSERT INTO triage_services (
        patient_id, clinician_id, facility_name, mfl_code, county, sub_county,
        enrolment_date, enrolment_time, visit_type, client_name, nickname, mat_id, sex, presenting_complaints,
        pulse, oxygen_saturation, blood_pressure, temperature, respiratory_rate, height, weight, bmi, bmi_interpretation,
        cows_provider, cows_date, cows_scores, cows_totals, cows_interpretations, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'incomplete')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'iissssssssssssiisdiiddsssssss',
        $patient_id, $clinician_id, $facility_name, $mfl_code, $county, $sub_county,
        $enrolment_date, $enrolment_time, $visit_type, $client_name, $nickname, $mat_id, $sex, $presenting_complaints,
        $pulse, $oxygen_saturation, $blood_pressure, $temperature, $respiratory_rate, $height, $weight, $bmi, $bmi_interpretation,
        $cows_provider, $cows_date, $cows_scores_json, $cows_totals_json, $cows_interpretations_json
    );

    if ($stmt->execute()) {
        $triage_id = $stmt->insert_id;
        $stmt->close();

        // Redirect to continue with clinical assessment
        header("Location: clinician_initial_encounter_form.php?p_id=$patient_id&action=continue&triage_id=$triage_id");
        exit();
    } else {
        die("Error saving triage data: " . $stmt->error);
    }
}
?>