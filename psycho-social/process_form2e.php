<?php
// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Initialize scores
    $total_yes = 0;
    $total_no = 0;

    // 2. Define the list of question keys (q1 to q19)
    $question_keys = [];
    for ($i = 1; $i <= 19; $i++) {
        $question_keys[] = 'q' . $i;
    }

    // 3. Calculate the total Yes and No scores
    foreach ($question_keys as $key) {
        // Check if the question was answered
        if (isset($_POST[$key])) {
            $response = (int)$_POST[$key];

            if ($response === 1) {
                $total_yes++;
            } else { // Assumes 0 for No
                $total_no++;
            }
        } else {
            // Error handling if a mandatory question is missed
            die("Error: Please answer all 19 questions on the form.");
        }
    }

    // 4. Extract other form data for the result display
    $name = htmlspecialchars($_POST['name'] ?? 'N/A');
    $mat_id = htmlspecialchars($_POST['mat_id'] ?? 'N/A');
    $age = htmlspecialchars($_POST['age'] ?? 'N/A');
    $total_questions = count($question_keys);


    // 5. Display the Results (HTML Output)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 2E Cessation Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>MAT Cessation Assessment Results</h1>
        <h2>Client Summary</h2>
        <p><strong>Name:</strong> <?php echo $name; ?></p>
        <p><strong>MAT ID NO.:</strong> <?php echo $mat_id; ?></p>
        <p><strong>Age:</strong> <?php echo $age; ?></p>

        <hr>

        <h3>Assessment Score (Overall Score: Yes=1 & No=0)</h3>

        <table class="phq9-table" style="width: 50%; margin: 20px auto;">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Yes (Overall Score)</td>
                    <td style="font-weight: bold; color: #2ecc71; font-size: 1.2em;"><?php echo $total_yes; ?></td>
                </tr>
                <tr>
                    <td>Total No</td>
                    <td style="font-weight: bold; color: #e74c3c; font-size: 1.2em;"><?php echo $total_no; ?></td>
                </tr>
                <tr>
                    <td>Total Questions</td>
                    <td><?php echo $total_questions; ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 30px; text-align: center;">
            <p><strong>Raw data submitted (for clinician review):</strong></p>
            <textarea readonly style="width: 80%; height: 150px; font-family: monospace; padding: 10px;">
<?php print_r($_POST); ?>
            </textarea>
        </div>

        <p style="margin-top: 30px;"><a href="mat_cessation_form.html">Go Back to Assessment Form</a></p>
    </div>
</body>
</html>
<?php
} else {
    // If someone tries to access process_form2e.php directly
    header("Location: mat_cessation_form.php");
    exit();
}
?>