<?php
// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Initialize score
    $total_score = 0;

    // 2. Define the list of question keys from the form (q1 to q9)
    $question_keys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9'];

    // 3. Calculate the total score
    foreach ($question_keys as $key) {
        // Check if the question was answered (value will be 0, 1, 2, or 3)
        if (isset($_POST[$key])) {
            // Add the value of the answer to the total score.
            $total_score += (int)$_POST[$key];
        } else {
            // Handle case where not all questions were answered (optional)
            die("Error: Please answer all PHQ-9 questions.");
        }
    }

    // 4. Determine the Provisional Diagnosis and Recommended Management
    $diagnosis = "";
    $management = [];

    if ($total_score >= 20) {
        $diagnosis = "Severe depression*";
        $management[] = "Provide supportive counselling (refer to a psychologist if available).";
        $management[] = "Refer to a medical officer, psychiatrist, or mental health team if available.";
        $management[] = "Severe depression may require patients to start on anti-depressants immediately[cite: 33].";
        // Check for EFV substitution if applicable, based on your source
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

    // Note for asterisked diagnoses [cite: 32]
    if (strpos($diagnosis, '*') !== false) {
        $management[] = "*Symptoms should ideally be present for at least 2 weeks for a diagnosis of depression and before considering treatment with antidepressant medication[cite: 32].";
    }

    // 5. Display the Results (HTML Output)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHQ-9 Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .result-box {
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .severe { background-color: #fceae5; border: 1px solid #e74c3c; color: #e74c3c; }
        .moderate { background-color: #fcf8e5; border: 1px solid #f39c12; color: #f39c12; }
        .mild { background-color: #e5f5fc; border: 1px solid #3498db; color: #3498db; }
        .unlikely { background-color: #e5fce5; border: 1px solid #2ecc71; color: #2ecc71; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHQ-9 Screening Results</h1>
        <h2>Assessment Summary</h2>
        <p><strong>Client Name:</strong> <?php echo htmlspecialchars($_POST['client_name']); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($_POST['age']); ?> Years</p>

        <hr>

        <h3>Calculated PHQ-9 Score: <span style="font-size: 1.5em;"><?php echo $total_score; ?> / 27</span></h3>

        <div class="result-box
            <?php
                if ($total_score >= 20) echo 'severe';
                elseif ($total_score >= 10) echo 'moderate';
                elseif ($total_score >= 5) echo 'mild';
                else echo 'unlikely';
            ?>">
            <h3>Provisional Diagnosis: <span style="font-weight: bold;"><?php echo $diagnosis; ?></span></h3>

            <p style="font-weight: bold; margin-top: 10px;">Recommended Management:</p>
            <ul>
                <?php foreach ($management as $item): ?>
                    <li><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p style="margin-top: 30px;"><a href="phq9_form.php">Take another assessment</a></p>
    </div>
</body>
</html>
<?php
} else {
    // If someone tries to access process_phq9.php directly
    header("Location: phq9_form.php");
    exit();
}
?>