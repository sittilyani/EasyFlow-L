<?php
session_start();
// NOTE: Ensure your config.php path is correct relative to this file
include "../includes/config.php";

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Initialize score
    $total_score = 0;

    // 2. Define the list of question keys and collect all form data
    $question_keys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9'];
    $q_scores = []; // Array to hold individual scores for saving

    // Collect patient and user info from hidden and standard fields
    $p_id = $_POST['p_id'] ?? null;
    $mat_id = $_POST['mat_id'] ?? null;
    $clientName = $_POST['clientName'] ?? 'N/A';
    $age = $_POST['age'] ?? 'N/A';
    $visitDate = $_POST['visitDate'] ?? date('Y-m-d');
    $therapist_id = $_POST['therapist_id'] ?? ($_SESSION['user_id'] ?? 0);
    $therapists_name = $_POST['therapist_name_submit'] ?? 'Unknown Therapist';


    // 3. Calculate the total score and check for missing answers
    foreach ($question_keys as $key) {
        if (isset($_POST[$key])) {
            $score = (int)$_POST[$key];
            $total_score += $score;
            $q_scores[$key] = $score;
        } else {
            // Error handling for incomplete form
            $message = "Error: Please answer all PHQ-9 questions.";
            header("Location: search_phq9.php?message=" . urlencode($message));
            exit();
        }
    }

    // 4. Determine the Provisional Diagnosis and Recommended Management
    $diagnosis = "";
    $management = [];

    if ($total_score >= 20) {
        $diagnosis = "Severe depression*";
        $management[] = "Provide supportive counselling (refer to a psychologist if available).";
        $management[] = "Refer to a medical officer, psychiatrist, or mental health team if available.";
        $management[] = "Severe depression may require patients to start on anti-depressants immediately.";
        $management[] = "If patient is on EFV, substitute with a different ARV after ruling out treatment failure IF APPLICABLE (See 'Managing Single Drug Substitutions for ART').";

    } elseif ($total_score >= 15) {
        $diagnosis = "Moderate-severe depression*";
        $management[] = "Provide supportive counselling (refer to a psychologist if available).";
        $management[] = "Refer to a medical officer, psychiatrist, or mental health team if available.";
        $management[] = "If patient is on EFV, substitute with a different ARV after ruling out treatment failure IF APPLICABLE (See 'Managing Single Drug Substitutions for ART').";

    } elseif ($total_score >= 10) {
        $diagnosis = "Moderate depression*";
        $management[] = "Provide supportive counselling (refer to a psychologist if available).";
        $management[] = "Refer to a medical officer, psychiatrist, or mental health team if available.";
        $management[] = "If patient is on EFV, substitute with a different ARV after ruling out treatment failure IF APPLICABLE (See 'Managing Single Drug Substitutions for ART').";

    } elseif ($total_score >= 5) {
        $diagnosis = "Mild depression";
        $management[] = "Provide counselling support and continue to monitor; refer to mental health team if available.";
        $management[] = "If patient is on EFV, substitute with a different ARV after ruling out treatment failure IF APPLICABLE (See 'Managing Single Drug Substitutions for ART').";

    } else { // Score 0-4
        $diagnosis = "Depression unlikely";
        $management[] = "Repeat screening in future if new concerns that depression has developed.";
    }

    // Note for asterisked diagnoses
    if (strpos($diagnosis, '*') !== false) {
        $management[] = "*Symptoms should ideally be present for at least 2 weeks for a diagnosis of depression and before considering treatment with antidepressant medication.";
    }

    // Combine management points into a single string for storage
    $management_plan_text = implode("\n", $management);

    // --- 5. Database Insertion ---

    // Check for essential IDs before inserting
    if ($p_id && $mat_id) {
        // 1. Construct the column names (7 fixed + 9 questions = 16 columns)
        $columns = "`p_id`, `mat_id`, `visitDate`, `therapist_id`, `total_score`, `diagnosis`, `management_plan`, " . implode(", ", array_map(function($q) { return "`$q`"; }, $question_keys));

        // 2. The total number of placeholders is 16
        $placeholders = str_repeat("?, ", 15) . "?";

        // Prepare the SQL INSERT statement
        $sql = "INSERT INTO phq9_assessments ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);

        // 3. Define the corrected parameter types (16 characters: isssiss for fixed fields + 9 i's for questions)
        // p_id(i), mat_id(s), visitDate(s), therapist_id(i), total_score(i), diagnosis(s), management_plan(s)
        $types = "isssiss" . str_repeat("i", count($question_keys));

        // 4. Create the array of 16 values to bind
        $bind_values = [
            $p_id,
            $mat_id,
            $visitDate,
            $therapist_id,
            $total_score,
            $diagnosis,
            $management_plan_text
        ];
        // Append the question scores (9 values)
        foreach ($q_scores as $score) {
            $bind_values[] = $score;
        }

        // Use call_user_func_array to bind the parameters dynamically
        $stmt->bind_param($types, ...$bind_values);

        if ($stmt->execute()) {
            $stmt->close();
            // Success: Redirect back to the search page with a success message
            $success_message = urlencode("PHQ-9 assessment for " . htmlspecialchars($clientName) . " saved successfully. Diagnosis: " . $diagnosis . ". Total Score: " . $total_score . ".");
            // NOTE: Changing redirection to 'search_phq9.php' as requested
            header("Location: search_phq9.php?message=" . $success_message . "&status=success");
            exit();
        } else {
            // Error during database insertion
            $error_message = urlencode("Error saving assessment: " . $stmt->error);
            $stmt->close();
            header("Location: search_phq9.php?message=" . $error_message . "&status=error");
            exit();
        }
    } else {
        // Error: Missing required patient identifiers
        $message = urlencode("Error: Missing patient ID or MAT ID. Assessment not saved.");
        header("Location: search_phq9.php?message=" . $message . "&status=error");
        exit();
    }


} else {
    // If someone tries to access process_phq9.php directly
    header("Location: phq9_form.php");
    exit();
}
?>