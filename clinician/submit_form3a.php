<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$clinician_id = $_SESSION['user_id'];

// Check if this is a partial save or final submission
$is_partial_save = isset($_POST['partial_save']) && $_POST['partial_save'] === 'true';
$current_section = $_POST['current_section'] ?? 'facility';
$patient_id = $_POST['p_id'] ?? null;
$draft_id = $_POST['draft_id'] ?? null;
$encounter_id = $_POST['encounter_id'] ?? null;

if (!$patient_id) {
    header("Location: clinician_initial_encounter_form.php?save_error=" . urlencode("Patient ID is required"));
    exit();
}

if ($is_partial_save) {
    // Handle partial save (draft) to clinical_encounter_drafts table

    // Prepare form data for storage (remove system fields)
    $form_data = $_POST;
    unset($form_data['partial_save']);
    unset($form_data['current_section']);
    unset($form_data['draft_id']);
    unset($form_data['encounter_id']);
    unset($form_data['p_id']);

    $json_data = json_encode($form_data);

    if ($draft_id && $draft_id > 0) {
        // Update existing draft
        $sql = "UPDATE clinical_encounter_drafts
                SET form_data = ?, current_section = ?, updated_at = NOW()
                WHERE id = ? AND clinician_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssii', $json_data, $current_section, $draft_id, $clinician_id);
            if ($stmt->execute()) {
                $new_draft_id = $draft_id;
                $stmt->close();

                // Redirect back to form with success message
                $redirect_url = "clinician_initial_encounter_form.php?p_id=$patient_id&draft_id=$new_draft_id&save_success=" . urlencode("Section saved successfully! Continuing to " . $current_section . " section.");
                header("Location: $redirect_url");
                exit();
            } else {
                error_log("Update failed: " . $stmt->error);
                $stmt->close();
            }
        }
    } else {
        // Create new draft
        $sql = "INSERT INTO clinical_encounter_drafts (clinician_id, patient_id, form_data, current_section, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iiss', $clinician_id, $patient_id, $json_data, $current_section);
            if ($stmt->execute()) {
                $new_draft_id = $stmt->insert_id;
                $stmt->close();

                // Redirect back to form with success message
                $redirect_url = "clinician_initial_encounter_form.php?p_id=$patient_id&draft_id=$new_draft_id&save_success=" . urlencode("Section saved successfully! Continuing to " . $current_section . " section.");
                header("Location: $redirect_url");
                exit();
            } else {
                error_log("Insert failed: " . $stmt->error);
                $stmt->close();
            }
        }
    }

    // If we reach here, there was an error
    $redirect_url = "clinician_initial_encounter_form.php?p_id=$patient_id&save_error=" . urlencode("Error saving draft. Please try again.");
    header("Location: $redirect_url");
    exit();

} else {
    // Handle final submission to clinical_encounters table (your original code)

    // Prepare data for main table
    $facility_name = mysqli_real_escape_string($conn, $_POST['facility_name'] ?? '');
    $mfl_code = mysqli_real_escape_string($conn, $_POST['mfl_code'] ?? '');
    $county = mysqli_real_escape_string($conn, $_POST['county'] ?? '');
    $sub_county = mysqli_real_escape_string($conn, $_POST['sub_county'] ?? '');
    $enrolment_date = !empty($_POST['enrolment_date']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['enrolment_date']))) : null;
    $enrolment_time = $_POST['enrolment_time'] ?? null;
    $visit_type = isset($_POST['visit_type']) ? implode(',', $_POST['visit_type']) : '';
    $nickname = mysqli_real_escape_string($conn, $_POST['nickname'] ?? '');
    $presenting_complaints = mysqli_real_escape_string($conn, $_POST['presenting_complaints'] ?? '');
    $injecting_history = $_POST['injecting_history'] ?? null;
    $reasons_injecting = isset($_POST['reasons_injecting']) ? implode(',', $_POST['reasons_injecting']) : '';
    $reasons_injecting_other = mysqli_real_escape_string($conn, $_POST['reasons_injecting_other'] ?? '');
    $flash_blood = $_POST['flash_blood'] ?? null;
    $shared_needles = $_POST['shared_needles'] ?? null;
    $injecting_complications = $_POST['injecting_complications'] ?? null;
    $drug_overdose = $_POST['drug_overdose'] ?? null;
    $pulse = !empty($_POST['pulse']) ? intval($_POST['pulse']) : null;
    $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? intval($_POST['oxygen_saturation']) : null;
    $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure'] ?? '');
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $bmi = !empty($_POST['bmi']) ? floatval($_POST['bmi']) : null;
    $bmi_interpretation = $_POST['bmi_interpretation'] ?? '';
    $cows_provider = mysqli_real_escape_string($conn, $_POST['cows_provider'] ?? '');
    $cows_date = $_POST['cows_date'] ?? null;

    // Collect COWS scores
    $cows_symptoms = [
        'Resting Pulse Rate' => 'cows_Resting Pulse Rate',
        'Sweating' => 'cows_Sweating',
        'Restlessness' => 'cows_Restlessness',
        'Pupil size' => 'cows_Pupil size',
        'Bone or Joint aches' => 'cows_Bone or Joint aches',
        'Runny nose or tearing' => 'cows_Runny nose or tearing',
        'GI Upset' => 'cows_GI Upset',
        'Tremor' => 'cows_Tremor',
        'Yawning' => 'cows_Yawning',
        'Anxiety or Irritability' => 'cows_Anxiety or Irritability',
        'Gooseflesh skin' => 'cows_Gooseflesh skin'
    ];
    $cows_scores = [];
    foreach ($cows_symptoms as $symptom => $prefix) {
        $cows_scores[$symptom] = [
            !empty($_POST[$prefix . '_time1']) ? intval($_POST[$prefix . '_time1']) : 0,
            !empty($_POST[$prefix . '_time2']) ? intval($_POST[$prefix . '_time2']) : 0,
            !empty($_POST[$prefix . '_time3']) ? intval($_POST[$prefix . '_time3']) : 0,
            !empty($_POST[$prefix . '_time4']) ? intval($_POST[$prefix . '_time4']) : 0
        ];
    }
    $cows_scores_json = json_encode($cows_scores);
    $cows_totals = [
        !empty($_POST['cows_total_time1']) ? intval($_POST['cows_total_time1']) : 0,
        !empty($_POST['cows_total_time2']) ? intval($_POST['cows_total_time2']) : 0,
        !empty($_POST['cows_total_time3']) ? intval($_POST['cows_total_time3']) : 0,
        !empty($_POST['cows_total_time4']) ? intval($_POST['cows_total_time4']) : 0
    ];
    $cows_totals_json = json_encode($cows_totals);
    $cows_interpretations = [
        $_POST['cows_interpretation_time1'] ?? '',
        $_POST['cows_interpretation_time2'] ?? '',
        $_POST['cows_interpretation_time3'] ?? '',
        $_POST['cows_interpretation_time4'] ?? ''
    ];
    $cows_interpretations_json = json_encode($cows_interpretations);

    // Medical history
    $medical_conditions = ['a' => 'Asthma', 'b' => 'Heart disease', 'c' => 'Tuberculosis', 'd' => 'Liver disease', 'e' => 'STDs', 'f' => 'Accidents or surgery', 'g' => 'HIV', 'h' => 'Hypertension', 'i' => 'Hepatitis B', 'j' => 'Hepatitis C', 'k' => 'Diabetes'];
    $medical_history = [];
    $medical_medication = [];
    foreach ($medical_conditions as $key => $cond) {
        $medical_history[$key] = $_POST['medical_history'][$key] ?? 'no';
        $medical_medication[$key] = mysqli_real_escape_string($conn, $_POST['medical_medication'][$key] ?? '');
    }
    $medical_history_json = json_encode($medical_history);
    $medical_medication_json = json_encode($medical_medication);
    $hiv_diagnosis_date = $_POST['hiv_diagnosis_date'] ?? null;
    $hiv_facility_care = mysqli_real_escape_string($conn, $_POST['hiv_facility_care'] ?? '');
    $other_medical_problems = mysqli_real_escape_string($conn, $_POST['other_medical_problems'] ?? '');
    $allergies = isset($_POST['allergies']) ? implode(',', $_POST['allergies']) : '';
    $allergies_other = mysqli_real_escape_string($conn, $_POST['allergies_other'] ?? '');
    $contraception_use = $_POST['contraception_use'] ?? null;
    $contraception_method = isset($_POST['contraception_method']) ? implode(',', $_POST['contraception_method']) : '';
    $last_menstrual_period = $_POST['last_menstrual_period'] ?? null;
    $pregnancy_status = $_POST['pregnancy_status'] ?? '';
    $pregnancy_weeks = !empty($_POST['pregnancy_weeks']) ? intval($_POST['pregnancy_weeks']) : null;
    $breastfeeding = $_POST['breastfeeding'] ?? null;
    $mental_health_diagnosis = $_POST['mental_health_diagnosis'] ?? null;
    $mental_health_condition = isset($_POST['mental_health_condition']) ? implode(',', $_POST['mental_health_condition']) : '';
    $mental_health_other = mysqli_real_escape_string($conn, $_POST['mental_health_other'] ?? '');
    $mental_health_medication = $_POST['mental_health_medication'] ?? null;
    $mental_health_medication_details = mysqli_real_escape_string($conn, $_POST['mental_health_medication_details'] ?? '');
    $suicidal_thoughts = $_POST['suicidal_thoughts'] ?? null;
    $psychiatric_hospitalization = $_POST['psychiatric_hospitalization'] ?? null;
    $family_drug_use = $_POST['family_drug_use'] ?? null;
    $family_mental_health = $_POST['family_mental_health'] ?? null;
    $family_medical_conditions = isset($_POST['family_medical_conditions']) ? implode(',', $_POST['family_medical_conditions']) : '';
    $family_medical_other = mysqli_real_escape_string($conn, $_POST['family_medical_other'] ?? '');
    $general_appearance = mysqli_real_escape_string($conn, $_POST['general_appearance'] ?? '');
    $skin_examination = mysqli_real_escape_string($conn, $_POST['skin_examination'] ?? '');
    $head_examination = mysqli_real_escape_string($conn, $_POST['head_examination'] ?? '');
    $eyes_examination = mysqli_real_escape_string($conn, $_POST['eyes_examination'] ?? '');
    $ears_examination = mysqli_real_escape_string($conn, $_POST['ears_examination'] ?? '');
    $nose_examination = mysqli_real_escape_string($conn, $_POST['nose_examination'] ?? '');
    $mouth_throat_examination = mysqli_real_escape_string($conn, $_POST['mouth_throat_examination'] ?? '');
    $neck_examination = mysqli_real_escape_string($conn, $_POST['neck_examination'] ?? '');
    $chest_examination = mysqli_real_escape_string($conn, $_POST['chest_examination'] ?? '');
    $heart_examination = mysqli_real_escape_string($conn, $_POST['heart_examination'] ?? '');
    $abdomen_examination = mysqli_real_escape_string($conn, $_POST['abdomen_examination'] ?? '');
    $genitalia_examination = mysqli_real_escape_string($conn, $_POST['genitalia_examination'] ?? '');
    $extremities_examination = mysqli_real_escape_string($conn, $_POST['extremities_examination'] ?? '');
    $neurological_examination = mysqli_real_escape_string($conn, $_POST['neurological_examination'] ?? '');
    $musculoskeletal_examination = mysqli_real_escape_string($conn, $_POST['musculoskeletal_examination'] ?? '');
    $diagnosis_opioid_use = $_POST['diagnosis_opioid_use'] ?? '';
    $other_diagnoses = mysqli_real_escape_string($conn, $_POST['other_diagnoses'] ?? '');
    $treatment_plan = mysqli_real_escape_string($conn, $_POST['treatment_plan'] ?? '');
    $medication_prescribed = isset($_POST['medication_prescribed']) ? implode(',', $_POST['medication_prescribed']) : '';
    $medication_other = mysqli_real_escape_string($conn, $_POST['medication_other'] ?? '');
    $initial_dose = mysqli_real_escape_string($conn, $_POST['initial_dose'] ?? '');
    $next_appointment = $_POST['next_appointment'] ?? null;
    $clinician_name = mysqli_real_escape_string($conn, $_POST['clinician_name'] ?? '');
    $clinician_signature = mysqli_real_escape_string($conn, $_POST['clinician_signature'] ?? '');
    $patient_consent = isset($_POST['patient_consent']) ? 'yes' : 'no';

    // Insert into clinical_encounters
    $sql = "INSERT INTO clinical_encounters (
        patient_id, facility_name, mfl_code, county, sub_county, enrolment_date, enrolment_time, visit_type, nickname, presenting_complaints,
        injecting_history, reasons_injecting, reasons_injecting_other, flash_blood, shared_needles, injecting_complications, drug_overdose,
        pulse, oxygen_saturation, blood_pressure, temperature, respiratory_rate, height, weight, bmi, bmi_interpretation,
        cows_provider, cows_date, cows_scores, cows_totals, cows_interpretations,
        medical_history, medical_medication, hiv_diagnosis_date, hiv_facility_care, other_medical_problems, allergies, allergies_other,
        contraception_use, contraception_method, last_menstrual_period, pregnancy_status, pregnancy_weeks, breastfeeding,
        mental_health_diagnosis, mental_health_condition, mental_health_other, mental_health_medication, mental_health_medication_details,
        suicidal_thoughts, psychiatric_hospitalization, family_drug_use, family_mental_health, family_medical_conditions, family_medical_other,
        general_appearance, skin_examination, head_examination, eyes_examination, ears_examination, nose_examination, mouth_throat_examination,
        neck_examination, chest_examination, heart_examination, abdomen_examination, genitalia_examination, extremities_examination,
        neurological_examination, musculoskeletal_examination, diagnosis_opioid_use, other_diagnoses, treatment_plan,
        medication_prescribed, medication_other, initial_dose, next_appointment, clinician_name, clinician_signature, patient_consent,
        status, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, NOW()
    )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $status = 'completed'; // Final submission status

    $stmt->bind_param(
        'isssssssssssssssiiisdiiddssssssssssssssssssissssssssssssssssssssssssssssssssssssssssss',
        $patient_id, $facility_name, $mfl_code, $county, $sub_county, $enrolment_date, $enrolment_time, $visit_type, $nickname, $presenting_complaints,
        $injecting_history, $reasons_injecting, $reasons_injecting_other, $flash_blood, $shared_needles, $injecting_complications, $drug_overdose,
        $pulse, $oxygen_saturation, $blood_pressure, $temperature, $respiratory_rate, $height, $weight, $bmi, $bmi_interpretation,
        $cows_provider, $cows_date, $cows_scores_json, $cows_totals_json, $cows_interpretations_json,
        $medical_history_json, $medical_medication_json, $hiv_diagnosis_date, $hiv_facility_care, $other_medical_problems, $allergies, $allergies_other,
        $contraception_use, $contraception_method, $last_menstrual_period, $pregnancy_status, $pregnancy_weeks, $breastfeeding,
        $mental_health_diagnosis, $mental_health_condition, $mental_health_other, $mental_health_medication, $mental_health_medication_details,
        $suicidal_thoughts, $psychiatric_hospitalization, $family_drug_use, $family_mental_health, $family_medical_conditions, $family_medical_other,
        $general_appearance, $skin_examination, $head_examination, $eyes_examination, $ears_examination, $nose_examination, $mouth_throat_examination,
        $neck_examination, $chest_examination, $heart_examination, $abdomen_examination, $genitalia_examination, $extremities_examination,
        $neurological_examination, $musculoskeletal_examination, $diagnosis_opioid_use, $other_diagnoses, $treatment_plan,
        $medication_prescribed, $medication_other, $initial_dose, $next_appointment, $clinician_name, $clinician_signature, $patient_consent,
        $status
    );

    if (!$stmt->execute()) {
        die("Error inserting encounter: " . $stmt->error);
    }

    $encounter_id = $stmt->insert_id;
    $stmt->close();

    // Insert drug histories
    $drugs = [
        'a' => 'Heroin',
        'b' => 'Cannabis Sativa',
        'c' => 'Tobacco',
        'd' => 'Benzodiazepines',
        'e' => 'Alcohol',
        'f' => 'Amphetamine',
        'g' => 'Cocaine',
        'h' => 'Miraa',
        'i' => 'Glue',
        'j' => 'Barbiturates',
        'k' => 'Phencyclidine',
        'l' => 'Other'
    ];

    $drug_sql = "INSERT INTO patient_drug_histories (encounter_id, drug_type, age_first_use, duration_years, frequency, quantity, route, last_used)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $drug_stmt = $conn->prepare($drug_sql);

    foreach ($drugs as $key => $type) {
        $age = !empty($_POST['drug_age_first_use'][$key]) ? intval($_POST['drug_age_first_use'][$key]) : null;
        $duration = !empty($_POST['drug_duration'][$key]) ? intval($_POST['drug_duration'][$key]) : null;
        $frequency = $_POST['drug_frequency'][$key] ?? null;
        $quantity = mysqli_real_escape_string($conn, $_POST['drug_quantity'][$key] ?? '');
        $route = $_POST['drug_route'][$key] ?? null;
        $last_used = !empty($_POST['drug_last_used'][$key]) ? $_POST['drug_last_used'][$key] : null;

        if ($age || $duration || $frequency || $quantity || $route || $last_used) {
            $drug_stmt->bind_param('isiiisss', $encounter_id, $type, $age, $duration, $frequency, $quantity, $route, $last_used);
            if (!$drug_stmt->execute()) {
                die("Error inserting drug history: " . $drug_stmt->error);
            }
        }
    }
    $drug_stmt->close();

    // After successful final submission, delete the draft if it exists
    if ($draft_id && $draft_id > 0) {
        $delete_sql = "DELETE FROM clinical_encounter_drafts WHERE id = ? AND clinician_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $draft_id, $clinician_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }

    $conn->close();

    // Success - redirect to search page
    header("Location: ../clinician/clinical_encounter_search.php?success=1");
    exit();
}
?>