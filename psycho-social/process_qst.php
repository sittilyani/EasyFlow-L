<?php
// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Define all question keys
    $question_keys = [
        'q1a', 'q1b', 'q1c', 'q1d', // Anxiety/Depression (max 3 each)
        'q2', 'q3',                  // Self-harm/Suicide (max 2 each)
        'q4', 'q5',                  // Substance Use (max 4 each)
        'q6', 'q7',                  // Trauma (max 2 and max 4)
        'q8', 'q9'                   // Gender Dysphoria (max 2 each)
    ];

    // 2. Calculate the total Global Score
    $global_score = 0;
    foreach ($question_keys as $key) {
        if (isset($_POST[$key])) {
            $global_score += (int)$_POST[$key];
        } else {
            die("Error: Please answer all questions on the form.");
        }
    }

    // 3. Determine QST Positive Status (based on "if any more than 2 score = QST positive" )
    $qst_positive = $global_score > 2;
    $status_text = $qst_positive ? "QST Positive" : "QST Negative";
    $status_color = $qst_positive ? "#e74c3c" : "#2ecc71"; // Red for positive, Green for negative

    // 4. Determine Positive Status for Specific Domains (score > 0 for any key in the domain)
    $domain_results = [
        'Anxiety Disorder (Q1a, Q1b)' => (int)$_POST['q1a'] > 0 || (int)$_POST['q1b'] > 0,
        'Depression Disorder (Q1c, Q1d)' => (int)$_POST['q1c'] > 0 || (int)$_POST['q1d'] > 0,
        'Self-harm and Suicide attempts (Q2, Q3)' => (int)$_POST['q2'] > 0 || (int)$_POST['q3'] > 0,
        'Substance use disorders (Q4, Q5)' => (int)$_POST['q4'] > 0 || (int)$_POST['q5'] > 0,
        'Acute and Post traumatic disorder (Q6, Q7)' => (int)$_POST['q6'] > 0 || (int)$_POST['q7'] > 0,
        'Gender Dysphoria (Q8, Q9)' => (int)$_POST['q8'] > 0 || (int)$_POST['q9'] > 0,
    ];

    // 5. Display the Results (HTML Output)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QST (Form 2B) Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Community Quick Screening Tool (FORM 2B) Results</h1>
        <h2>Assessment Summary</h2>
        <p><strong>Client ID / Name:</strong> <?php echo htmlspecialchars($_POST['client_id'] ?? 'N/A'); ?></p>

        <hr>

        <div style="text-align: center; padding: 20px; border: 2px solid <?php echo $status_color; ?>; border-radius: 8px;">
            <h3>Calculated Global Score: <span style="font-size: 1.5em; color: #0056b3;"><?php echo $global_score; ?></span></h3>
            <p style="font-size: 1.2em; font-weight: bold; color: <?php echo $status_color; ?>">
                Overall Screening Status: <?php echo $status_text; ?>
            </p>
            <?php if ($qst_positive): ?>
                <p style="color: #e74c3c; margin-top: 10px;">
                    [cite_start]?? **Referral Recommended:** All subjects positive for QST should be referred for mental health assessment. [cite: 143]
                </p>
            <?php endif; ?>
        </div>

        <h3>Domain-Specific Screening Results</h3>
        [cite_start]<p>Based on the questions, the tool screened for the following areas[cite: 146, 147, 148, 149, 150, 151]:</p>
        <ul style="list-style-type: none; padding: 0;">
            <?php foreach ($domain_results as $domain => $positive): ?>
                <li style="margin-bottom: 5px;">
                    <span style="font-weight: bold;"><?php echo $domain; ?>:</span>
                    <span style="color: <?php echo $positive ? '#e74c3c' : '#2ecc71'; ?>; font-weight: bold;">
                        <?php echo $positive ? 'POSITIVE' : 'NEGATIVE'; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

        <p style="margin-top: 30px;"><a href="qst_form.html">Go Back to Assessment Form</a></p>
    </div>
</body>
</html>
<?php
} else {
    // If someone tries to access process_qst.php directly
    header("Location: qst_form.html");
    exit();
}
?>