<?php
include '../includes/config.php';

// Set character set
$conn->set_charset("utf8mb4");

// Function to sanitize input data
function sanitize_input($data) {
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function to generate MAT ID
function generate_mat_id($mat_id, $visit_type) {
    $timestamp = time();
    $visit_code = (strtolower($visit_type) == 'initial') ? 'INIT' : 'FUP';
    return "MAT_" . $mat_id . "_" . $visit_code . "_" . $timestamp;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. SANITIZE AND VALIDATE INPUT DATA ---

    // General
    $visit_type = sanitize_input($_POST['visit_type']);
    $visitDate = sanitize_input($_POST['visitDate']);
    $clientName = sanitize_input($_POST['clientName']);
    $mat_id = sanitize_input($_POST['mat_id']);
    $sex = sanitize_input($_POST['sex']);
    $other_sex_specify = isset($_POST['other_sex_specify']) ? sanitize_input($_POST['other_sex_specify']) : null;

    // Clinical assessment
    $pre_complaints = sanitize_input($_POST['pre_complaints']);
    $corr_complaints = sanitize_input($_POST['corr_complaints']);
    $hx_illness = sanitize_input($_POST['hx_illness']);
    $past_psych_hx = sanitize_input($_POST['past_psych_hx']);
    $past_med_hx = sanitize_input($_POST['past_med_hx']);

    // Substance use
    $sub_use_hx = sanitize_input($_POST['sub_use_hx']);
    $fam_hx = sanitize_input($_POST['fam_hx']);

    // Social history
    $intake_date = sanitize_input($_POST['intake_date']);
    $marital_status = sanitize_input($_POST['marital_status']);
    $marital_other_specify = isset($_POST['marital_other_specify']) ? sanitize_input($_POST['marital_other_specify']) : null;
    $living_arrangements = sanitize_input($_POST['living_arrangements']);

    // Process array inputs (comma-separated strings)
    $living_arrangements_detail = isset($_POST['living_arrangements_detail']) ?
        implode(", ", array_map('sanitize_input', $_POST['living_arrangements_detail'])) : null;
    $living_other_specify = isset($_POST['living_other_specify']) ? sanitize_input($_POST['living_other_specify']) : null;

    $previous_treatment = sanitize_input($_POST['previous_treatment']);
    $treatment_specify = isset($_POST['treatment_specify']) ? sanitize_input($_POST['treatment_specify']) : null;

    // Sexual history
    $sexually_active = sanitize_input($_POST['sexually_active']);
    $sexual_partners = sanitize_input($_POST['sexual_partners']);
    $unprotected_sex = sanitize_input($_POST['unprotected_sex']);

    // Education and occupation
    $education_level = sanitize_input($_POST['education_level']);
    $education_other_specify = isset($_POST['education_other_specify']) ? sanitize_input($_POST['education_other_specify']) : null;
    $has_income = sanitize_input($_POST['has_income']);
    $income_specify = isset($_POST['income_specify']) ? sanitize_input($_POST['income_specify']) : null;
    $employment_status = sanitize_input($_POST['employment_status']);
    $missed_work = sanitize_input($_POST['missed_work']);
    $fired_work = sanitize_input($_POST['fired_work']);

    // Family relationships
    $family_relationship = sanitize_input($_POST['family_relationship']);

    // Family and social support
    $has_dependents = sanitize_input($_POST['has_dependents']);
    $dependents = isset($_POST['dependents']) ?
        implode(", ", array_map('sanitize_input', $_POST['dependents'])) : null;
    $dependent_other_specify = isset($_POST['dependent_other_specify']) ? sanitize_input($_POST['dependent_other_specify']) : null;
    $has_support = sanitize_input($_POST['has_support']);

    // Process support table data (JSON encoding)
    $support_data = [];
    $support_data_json = null;
    if ($has_support == 'yes') {
        $support_relationships = [
            "Spouse/Partner", "Mother", "Father", "Brother", "Sister",
            "Child", "Peer educator/Outreach worker", "Other"
        ];
        foreach ($support_relationships as $relationship) {
            $rel_key = strtolower(str_replace(['/', ' '], ['_', '_'], $relationship));
            $support_data[$relationship] = [
                'cash' => isset($_POST["support_cash_$rel_key"]) ? 1 : 0,
                'food' => isset($_POST["support_food_$rel_key"]) ? 1 : 0,
                'shelter' => isset($_POST["support_shelter_$rel_key"]) ? 1 : 0,
                'psychological' => isset($_POST["support_psychological_$rel_key"]) ? 1 : 0
            ];
        }
        $support_data_json = json_encode($support_data);
    }

    // Additional history
    $ante_hx = sanitize_input($_POST['ante_hx']);
    $dev_hx = sanitize_input($_POST['dev_hx']);
    $child_hx = sanitize_input($_POST['child_hx']);

    // GBV
    $gbv_experience = sanitize_input($_POST['gbv_experience']);
    $gbv_description = isset($_POST['gbv_description']) ? sanitize_input($_POST['gbv_description']) : null;
    $gbv_reported = isset($_POST['gbv_reported']) ? sanitize_input($_POST['gbv_reported']) : null;
    $gbv_medical = isset($_POST['gbv_medical']) ? sanitize_input($_POST['gbv_medical']) : null;

    // Legal status
    $has_case = sanitize_input($_POST['has_case']);

    // Assign value to $case_type for binding (comma-separated list)
    $case_type = isset($_POST['case_type']) ?
        implode(", ", array_map('sanitize_input', $_POST['case_type'])) : null;

    // Process legal cases data (JSON encoding)
    $legal_cases_data = [];
    $legal_cases_json = null;
    if ($has_case == 'yes') {
        $legal_cases = [
            "Shoplifting/vandalism", "Drunk and disorderly", "Drug possession",
            "Drug peddling", "Weapons offense", "Burglary", "Robbery",
            "Assault", "Rape", "Murder", "Sex work", "Fraud/forgery"
        ];
        foreach ($legal_cases as $case) {
            $case_key = strtolower(str_replace(['/', ' '], ['_', '_'], $case));
            $legal_cases_data[$case] = [
                'committed' => isset($_POST["case_committed_$case_key"]) ? 1 : 0,
                'arrested' => isset($_POST["case_arrested_$case_key"]) ? 1 : 0
            ];
        }
        // Assuming 'case_data' column stores this JSON
        $legal_cases_json = json_encode($legal_cases_data);
    }

    // Clinical assessment findings
    $premord_hx = sanitize_input($_POST['premord_hx']);
    $forens_hx = sanitize_input($_POST['forens_hx']);
    $phys_exam = sanitize_input($_POST['phys_exam']);
    $mental_exam = sanitize_input($_POST['mental_exam']);
    $diagnosis = sanitize_input($_POST['diagnosis']);
    $mgt_plan = sanitize_input($_POST['mgt_plan']);

    // Therapist information
    $therapist_name = sanitize_input($_POST['therapist_name']);
    $service_date = sanitize_input($_POST['service_date']);
    $rx_supporter = sanitize_input($_POST['rx_supporter']);
    $referral = sanitize_input($_POST['referral']);

    // Generate MAT ID
    $mat_id_generated = generate_mat_id($mat_id, $visit_type);

    // --- 2. PREPARE AND EXECUTE SQL STATEMENT ---

    // *** FIX: p_id IS REMOVED from the list (55 columns) ***
    $sql = "INSERT INTO psychosocial_intake_form_1a (
        visit_type, visitDate, clientName, mat_id, sex, other_sex_specify,
        pre_complaints, corr_complaints, hx_illness, past_psych_hx, past_med_hx,
        sub_use_hx, fam_hx, intake_date, marital_status, marital_other_specify,
        living_arrangements, living_arrangements_detail, living_other_specify,
        previous_treatment, treatment_specify, sexually_active, sexual_partners,
        unprotected_sex, education_level, education_other_specify, has_income,
        income_specify, employment_status, missed_work, fired_work, family_relationship,
        has_dependents, dependents, dependent_other_specify, has_support,
        support_data, ante_hx, dev_hx, child_hx, gbv_experience, gbv_description,
        gbv_reported, gbv_medical, has_case, case_type, case_data, premord_hx,
        forens_hx, phys_exam, mental_exam, diagnosis, mgt_plan, therapist_name,
        service_date, rx_supporter, referral
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";

    $stmt = $conn->prepare($sql);

    // *** FIX: Total number of parameters is 55. All are assumed to be strings 's'. ***
    $bind_types = str_repeat('s', 57);

    if ($stmt) {
        $stmt->bind_param(
            $bind_types,
            $visit_type, $visitDate, $clientName, $mat_id_generated, $sex, $other_sex_specify,
            $pre_complaints, $corr_complaints, $hx_illness, $past_psych_hx, $past_med_hx,
            $sub_use_hx, $fam_hx, $intake_date, $marital_status, $marital_other_specify,
            $living_arrangements, $living_arrangements_detail, $living_other_specify,
            $previous_treatment, $treatment_specify, $sexually_active, $sexual_partners,
            $unprotected_sex, $education_level, $education_other_specify, $has_income,
            $income_specify, $employment_status, $missed_work, $fired_work, $family_relationship,
            $has_dependents, $dependents, $dependent_other_specify, $has_support,
            $support_data_json, $ante_hx, $dev_hx, $child_hx, $gbv_experience, $gbv_description,
            $gbv_reported, $gbv_medical, $has_case, $case_type, $legal_cases_json, $premord_hx,
            $forens_hx, $phys_exam, $mental_exam, $diagnosis, $mgt_plan, $therapist_name,
            $service_date, $rx_supporter, $referral
        );

        if ($stmt->execute()) {
            $psy_id = $stmt->insert_id;
            echo "<script>alert('Form submitted successfully! Record ID: $psy_id'); window.location.href = 'psychosocial_intake_form_1a.php';</script>";
        } else {
            error_log("SQL Execute Error: " . $stmt->error);
            echo "<script>alert('Error submitting form: Please check server logs.'); window.history.back();</script>";
        }

        $stmt->close();
    } else {
        error_log("SQL Prepare Error: " . $conn->error);
        echo "<script>alert('Database error: Please check server logs.'); window.history.back();</script>";
    }

    $conn->close();
} else {
    header("Location: psychosocial_intake_form_1a.php");
    exit();
}
?>