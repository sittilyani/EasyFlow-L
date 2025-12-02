<?php
session_start();
include "../includes/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $total_score = 0;
    $question_keys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9'];
    $q_scores = [];

    $p_id = $_POST['p_id'] ?? null;
    $mat_id = $_POST['mat_id'] ?? null;
    $clientName = $_POST['clientName'] ?? 'N/A';
    $age = $_POST['age'] ?? 'N/A';
    $visitDate = $_POST['visitDate'] ?? date('Y-m-d');
    $therapist_id = $_POST['therapist_id'] ?? ($_SESSION['user_id'] ?? 0);
    $therapists_name = $_POST['therapist_name_submit'] ?? 'Unknown Therapist';

    foreach ($question_keys as $key) {
        if (isset($_POST[$key])) {
            $score = (int)$_POST[$key];
            $total_score += $score;
            $q_scores[$key] = $score;
        } else {
            $message = "Error: Please answer all PHQ-9 questions.";
            header("Location: search_phq9_form.php?message=" . urlencode($message));
            exit();
        }
    }

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

    } else {
        $diagnosis = "Depression unlikely";
        $management[] = "Repeat screening in future if new concerns that depression has developed.";
    }

    if (strpos($diagnosis, '*') !== false) {
        $management[] = "*Symptoms should ideally be present for at least 2 weeks for a diagnosis of depression and before considering treatment with antidepressant medication.";
    }

    $management_plan_text = implode("\n", $management);

    // --- Database Insertion ---
    if ($p_id && $mat_id) {
        $columns = "`p_id`, `mat_id`, `visitDate`, `therapist_id`, `total_score`, `diagnosis`, `management_plan`, " . implode(", ", array_map(function($q) { return "`$q`"; }, $question_keys));
        $placeholders = str_repeat("?, ", 15) . "?";
        $sql = "INSERT INTO phq9_assessments ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $types = "isssiss" . str_repeat("i", count($question_keys));

        $bind_values = [
            $p_id,
            $mat_id,
            $visitDate,
            $therapist_id,
            $total_score,
            $diagnosis,
            $management_plan_text
        ];
        foreach ($q_scores as $score) {
            $bind_values[] = $score;
        }

        $stmt->bind_param($types, ...$bind_values);

        if ($stmt->execute()) {
            $stmt->close();

            // Store results in session for display
            $_SESSION['phq9_results'] = [
                'clientName' => $clientName,
                'mat_id' => $mat_id,
                'age' => $age,
                'visitDate' => $visitDate,
                'total_score' => $total_score,
                'diagnosis' => $diagnosis,
                'management' => $management,
                'therapist_name' => $therapists_name
            ];

            // Redirect to results page
            header("Location: phq9_results.php");
            exit();
        } else {
            $error_message = urlencode("Error saving assessment: " . $stmt->error);
            $stmt->close();
            header("Location: search_phq9_form.php?message=" . $error_message . "&status=error");
            exit();
        }
    } else {
        $message = urlencode("Error: Missing patient ID or MAT ID. Assessment not saved.");
        header("Location: search_phq9_form.php?message=" . $message . "&status=error");
        exit();
    }

} else {
    header("Location: search_phq9_form.php");
    exit();
}
?>