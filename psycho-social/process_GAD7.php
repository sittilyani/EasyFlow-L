<?php
// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Initialize score
    $total_score = 0;

    // 2. Define the list of question keys from the form
    $question_keys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7'];

    // 3. Calculate the total score
    foreach ($question_keys as $key) {
        // Check if the question was answered (value will be 0, 1, 2, or 3)
        if (isset($_POST[$key])) {
            // Add the value of the answer to the total score.
            // The value is already the score (0, +1, +2, or +3)
            $total_score += (int)$_POST[$key];
        } else {
            // Handle case where not all questions were answered (optional)
            die("Error: Please answer all GAD-7 questions.");
        }
    }

    // 4. Determine the Anxiety Severity Interpretation [cite: 43]
    $severity = "";
    $warrant_assessment = false;

    if ($total_score >= 15) {
        $severity = "Severe anxiety"; // Score greater than 15 [cite: 47]
    } elseif ($total_score >= 10) {
        $severity = "Moderate anxiety"; // Score 10-14 [cite: 46]
    } elseif ($total_score >= 5) {
        $severity = "Mild anxiety"; // Score 5-9 [cite: 45]
    } else {
        $severity = "Minimal anxiety"; // Score 0-4 [cite: 44]
    }

    // 5. Determine if further assessment is warranted (Cut-off >= 8) [cite: 38, 39]
    if ($total_score >= 8) {
        $warrant_assessment = true;
    }

    // 6. Display the Results (HTML Output)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAD-7 Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>GAD-7 Screening Results</h1>
        <h2>Assessment Summary</h2>
        <p><strong>KP Name:</strong> <?php echo htmlspecialchars($_POST['kp_name']); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($_POST['age']); ?> Years</p>

        <hr>

        <h3>Calculated GAD-7 Score: <span style="color: #c0392b; font-size: 1.5em;"><?php echo $total_score; ?></span></h3>

        <h3>Interpretation</h3>
        <p><strong>Anxiety Severity:</strong>
        <span style="font-weight: bold;"><?php echo $severity; ?></span>
        (Based on a score of <?php echo $total_score; ?>)</p>

        <?php if ($warrant_assessment): ?>
            <div style="background-color: #fceae5; border: 1px solid #e74c3c; padding: 15px; border-radius: 4px; margin-top: 20px;">
                <p style="color: #e74c3c; font-weight: bold;">
                ?? Action Required: Further diagnostic assessment is warranted
                [cite_start]to confirm the presence and type of anxiety disorder, as the score (<?php echo $total_score; ?>) is $\geq$ 8 points. [cite: 38, 39]
                </p>
            </div>
        <?php else: ?>
            <div style="background-color: #e5fce5; border: 1px solid #2ecc71; padding: 15px; border-radius: 4px; margin-top: 20px;">
                <p style="color: #2ecc71; font-weight: bold;">
                ? Screening indicates Minimal or Mild anxiety.
                </p>
            </div>
        <?php endif; ?>

        <p style="margin-top: 30px;"><a href="gad7_form.html">Take another assessment</a></p>
    </div>
</body>
</html>
<?php
} else {
    // If someone tries to access process_gad7.php directly
    header("Location: gad7_form.html");
    exit();
}
?>