<?php
session_start();
include "../includes/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$patient_id = isset($_POST['p_id']) ? intval($_POST['p_id']) : 0;
if ($patient_id <= 0) {
    die("Invalid patient ID.");
}

// Check if this is a partial save or final submission
$is_partial_save = isset($_POST['partial_save']) && $_POST['partial_save'] === 'true';
$current_section = $_POST['current_section'] ?? '';
$encounter_id = $_POST['encounter_id'] ?? 0;

// Collect all form data
$form_data = $_POST;

// Remove technical fields from form data
unset($form_data['partial_save']);
unset($form_data['current_section']);
unset($form_data['encounter_id']);

if ($is_partial_save) {
    // Handle partial save (save to drafts)
    handlePartialSave($conn, $patient_id, $form_data, $current_section);
} else {
    // Handle final submission
    handleFinalSubmission($conn, $patient_id, $form_data, $encounter_id);
}

function handlePartialSave($conn, $patient_id, $form_data, $current_section) {
    $clinician_id = $_SESSION['user_id'];

    // Check if draft already exists
    $check_sql = "SELECT id FROM clinical_encounter_drafts WHERE patient_id = ? AND clinician_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $patient_id, $clinician_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    $form_data_json = json_encode($form_data);

    if ($result->num_rows > 0) {
        // Update existing draft
        $row = $result->fetch_assoc();
        $draft_id = $row['id'];

        $update_sql = "UPDATE clinical_encounter_drafts SET form_data = ?, current_section = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ssi', $form_data_json, $current_section, $draft_id);

        if ($update_stmt->execute()) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?save_result=SECTION_SAVED:" . $current_section . ":" . $draft_id);
        } else {
            die("Error updating draft: " . $update_stmt->error);
        }
        $update_stmt->close();
    } else {
        // Insert new draft
        $insert_sql = "INSERT INTO clinical_encounter_drafts (patient_id, form_data, current_section, clinician_id) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('issi', $patient_id, $form_data_json, $current_section, $clinician_id);

        if ($insert_stmt->execute()) {
            $draft_id = $insert_stmt->insert_id;
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?save_result=SECTION_SAVED:" . $current_section . ":" . $draft_id);
        } else {
            die("Error creating draft: " . $insert_stmt->error);
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

function handleFinalSubmission($conn, $patient_id, $form_data, $encounter_id) {
    // Prepare data for main table (similar to your existing code, but structured)
    $main_data = prepareMainData($form_data);

    if ($encounter_id > 0) {
        // Update existing encounter
        updateEncounter($conn, $encounter_id, $main_data);
    } else {
        // Create new encounter
        $encounter_id = createEncounter($conn, $patient_id, $main_data);
    }

    // Save drug histories
    saveDrugHistories($conn, $encounter_id, $form_data);

    // Delete draft if it exists
    deleteDraft($conn, $patient_id);

    // Redirect to success page
    header("Location: ../clinician/clinical_encounter_search.php?success=1&encounter_id=" . $encounter_id);
}

function prepareMainData($form_data) {
    // This function should prepare all the data similar to your existing code
    // I'll include the key structure, but you'll need to adapt your existing data preparation

    return [
        'facility_name' => mysqli_real_escape_string($conn, $form_data['facility_name'] ?? ''),
        'mfl_code' => mysqli_real_escape_string($conn, $form_data['mfl_code'] ?? ''),
        'county' => mysqli_real_escape_string($conn, $form_data['county'] ?? ''),
        'sub_county' => mysqli_real_escape_string($conn, $form_data['sub_county'] ?? ''),
        'enrolment_date' => !empty($form_data['enrolment_date']) ? date('Y-m-d', strtotime(str_replace('/', '-', $form_data['enrolment_date']))) : null,
        'enrolment_time' => $form_data['enrolment_time'] ?? null,
        'visit_type' => isset($form_data['visit_type']) ? implode(',', $form_data['visit_type']) : '',
        'client_name' => mysqli_real_escape_string($conn, $form_data['client_name'] ?? ''),
        'nickname' => mysqli_real_escape_string($conn, $form_data['nickname'] ?? ''),
        'mat_id' => mysqli_real_escape_string($conn, $form_data['mat_id'] ?? ''),
        'sex' => mysqli_real_escape_string($conn, $form_data['sex'] ?? ''),
        'presenting_complaints' => mysqli_real_escape_string($conn, $form_data['presenting_complaints'] ?? ''),
        // ... include all other fields from your existing code
        'status' => 'complete'
    ];
}

function createEncounter($conn, $patient_id, $data) {
    // Build the SQL query dynamically based on your table structure
    $columns = ['patient_id', 'status'];
    $values = [$patient_id, 'complete'];
    $placeholders = ['?', '?'];
    $types = 'is';

    foreach ($data as $key => $value) {
        $columns[] = $key;
        $placeholders[] = '?';
        $values[] = $value;
        $types .= 's'; // Adjust type based on actual data type
    }

    $sql = "INSERT INTO clinical_encounters (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        return $stmt->insert_id;
    } else {
        die("Error creating encounter: " . $stmt->error);
    }
}

function updateEncounter($conn, $encounter_id, $data) {
    $updates = [];
    $values = [];
    $types = '';

    foreach ($data as $key => $value) {
        $updates[] = "$key = ?";
        $values[] = $value;
        $types .= 's'; // Adjust type based on actual data type
    }

    $values[] = $encounter_id;
    $types .= 'i';

    $sql = "UPDATE clinical_encounters SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);

    if (!$stmt->execute()) {
        die("Error updating encounter: " . $stmt->error);
    }
}

function saveDrugHistories($conn, $encounter_id, $form_data) {
    // Delete existing drug histories
    $delete_sql = "DELETE FROM patient_drug_histories WHERE encounter_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $encounter_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Insert new drug histories (your existing code)
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

    $drug_sql = "INSERT INTO patient_drug_histories (encounter_id, drug_type, age_first_use, duration_years, frequency, quantity, route, last_used) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $drug_stmt = $conn->prepare($drug_sql);

    foreach ($drugs as $key => $type) {
        if (isset($form_data['drug_age_first_use'][$key]) || isset($form_data['drug_frequency'][$key])) {
            $age = !empty($form_data['drug_age_first_use'][$key]) ? intval($form_data['drug_age_first_use'][$key]) : null;
            $duration = !empty($form_data['drug_duration'][$key]) ? intval($form_data['drug_duration'][$key]) : null;
            $frequency = $form_data['drug_frequency'][$key] ?? null;
            $quantity = mysqli_real_escape_string($conn, $form_data['drug_quantity'][$key] ?? '');
            $route = $form_data['drug_route'][$key] ?? null;
            $last_used = !empty($form_data['drug_last_used'][$key]) ? $form_data['drug_last_used'][$key] : null;

            $drug_stmt->bind_param('isiiisss', $encounter_id, $type, $age, $duration, $frequency, $quantity, $route, $last_used);
            $drug_stmt->execute();
        }
    }
    $drug_stmt->close();
}

function deleteDraft($conn, $patient_id) {
    $clinician_id = $_SESSION['user_id'];
    $delete_sql = "DELETE FROM clinical_encounter_drafts WHERE patient_id = ? AND clinician_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('ii', $patient_id, $clinician_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

$conn->close();
exit();
?>