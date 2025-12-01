<?php
session_start();
include "../includes/config.php";

// Ensure logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$p_id = $_POST['p_id'];
$therapist_id = $_POST['therapist_id'];

// Direct fields stored EXACTLY as they appear
$visit_date   = $_POST['visit_date'];
$mat_id       = $_POST['mat_id'];
$clientName  = $_POST['clientName'];
$dob  = $_POST['dob'];
$sex          = $_POST['sex'];
$age          = $_POST['age'];
$drugname    = $_POST['drugname'];
$dosage       = $_POST['dosage'];
$accommodation = $_POST['accommodation'];
$therapists_name = $_POST['therapist_id'];

// 1. Convert FOLLOW-UP ID ? NAME
$clinic_id = $_POST['visit_name'];
$q = $conn->prepare("SELECT visit_name FROM clinic_visits WHERE clinic_id=?");
$q->bind_param("i", $clinic_id);
$q->execute();
$visit_name = $q->get_result()->fetch_assoc()['visit_name'] ?? null;

// 2. Convert MARITAL STATUS ID ? NAME
$marital_id = $_POST['marital_status'];
$q = $conn->prepare("SELECT marital_status_name FROM marital_status WHERE mar_id=?");
$q->bind_param("i", $marital_id);
$q->execute();
$marital_status_name = $q->get_result()->fetch_assoc()['marital_status_name'] ?? null;

// If OTHER ? override
if (!empty($_POST['other_marital_status'])) {
    $marital_status_name = $_POST['other_marital_status'];
}

// 3. Convert EMPLOYMENT ID ? NAME
$emp_id = $_POST['employment_status'];
$q = $conn->prepare("SELECT emp_status_name FROM employment_status WHERE emp_id=?");
$q->bind_param("i", $emp_id);
$q->execute();
$employment_status_name = $q->get_result()->fetch_assoc()['emp_status_name'] ?? null;

// 4. Convert TREATMENT STAGE ID ? NAME
$stage_id = $_POST['treatment_stage'];
$q = $conn->prepare("SELECT stage_of_rx_name FROM treatment_stage WHERE stage_id=?");
$q->bind_param("i", $stage_id);
$q->execute();
$treatment_stage_name = $q->get_result()->fetch_assoc()['stage_of_rx_name'] ?? null;

// 5. Convert INTERVENTION ID ? NAME
$intervention_id = $_POST['psycho_interventions'];
$q = $conn->prepare("SELECT intervention_name FROM psychosocial_interventions WHERE intervention_id=?");
$q->bind_param("i", $intervention_id);
$q->execute();
$intervention_name = $q->get_result()->fetch_assoc()['intervention_name'] ?? null;

// 6. Convert REINTEGRATION ID ? NAME
$reint_id = $_POST['reintegration_status'];
$q = $conn->prepare("SELECT reint_name FROM reintegration_status WHERE reint_id=?");
$q->bind_param("i", $reint_id);
$q->execute();
$reint_name = $q->get_result()->fetch_assoc()['reint_name'] ?? null;

// 7. Convert REFERRAL ID ? NAME
$ref_id = $_POST['linkage'];
$referral_name = "";
if (!empty($ref_id)) {
    $q = $conn->prepare("SELECT ref_name FROM referral_linkage_services WHERE ref_id=?");
    $q->bind_param("i", $ref_id);
    $q->execute();
    $referral_name = $q->get_result()->fetch_assoc()['ref_name'] ?? "";
}

// 8. Convert MULTISELECT LIVING CONDITIONS ? comma-separated names
$living_conditions = $_POST['living_conditions'] ?? [];
$condition_names = [];

foreach ($living_conditions as $cond_id) {
    $q = $conn->prepare("SELECT condition_name FROM living_conditions WHERE cond_id=?");
    $q->bind_param("i", $cond_id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    if ($row) {
        $condition_names[] = $row['condition_name'];
    }
}
$living_conditions_name = implode(", ", $condition_names);

// Other direct input fields
$intake_date         = $_POST['date_of_intake'];
$living_arrangements = $_POST['living_arrangements'];
$psycho_issues       = $_POST['psycho_issues'];
$legal_issues        = $_POST['legal_issues'];
$gbv_screen          = $_POST['gbv_screen'];
$gbv_support         = $_POST['gbv_support'];
$next_appointment    = $_POST['next_appointment'];
$therapists_notes    = $_POST['therapists_notes'];

// INSERT INTO DATABASE
$stmt = $conn->prepare("
    INSERT INTO psychodar (
        visitDate, dob, visit_name, mat_id, clientName, sex, age, drugname, dosage,
        marital_status, living_arrangements, living_conditions, accommodation,
        employment_status, treatment_stage, psycho_issues, psycho_interventions,
        reintegration_status, legal_issues, gbv_screen, gbv_support,
        referral_linkage, therapists_name, next_appointment, therapists_notes
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");


$stmt->bind_param(
    "sssssssssssssssssssssssss",
    $visit_date, $dob, $visit_name, $mat_id, $clientName, $sex, $age, $drugname, $dosage,
    $marital_status_name, $living_arrangements, $living_conditions_name, $accommodation,
    $employment_status_name, $treatment_stage_name, $psycho_issues, $intervention_name,
    $reint_name, $legal_issues, $gbv_screen, $gbv_support, $referral_name,
    $therapists_name, $next_appointment, $therapists_notes
);

if ($stmt->execute()) {
    echo "<script>alert('PsychoDAR Form submitted successfully');window.location.href='psychodar_updated.php?p_id=$p_id';</script>";
} else {
    echo "Database Error: " . $stmt->error;
}
?>
