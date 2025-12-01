<?php
session_start();
include '../includes/config.php';

// Check if results exist in session
if (!isset($_SESSION['gad7_results'])) {
    header("Location: search_gad7_form.php");
    exit();
}

$results = $_SESSION['gad7_results'];
unset($_SESSION['gad7_results']); // Clear session data after use

// Determine severity class for styling
$severity_class = strtolower(str_replace(' ', '-', $results['severity']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAD-7 Assessment Results</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .success-alert {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .score-display {
            font-size: 2.5em;
            font-weight: bold;
            color: #2C3162;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .severity-box {
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 1.2em;
            text-align: center;
        }
        .minimal { background-color: #e8f5e9; color: #2e7d32; border: 2px solid #4caf50; }
        .mild { background-color: #fff3e0; color: #ef6c00; border: 2px solid #ff9800; }
        .moderate { background-color: #fff8e1; color: #ff8f00; border: 2px solid #ffb300; }
        .severe { background-color: #ffebee; color: #c62828; border: 2px solid #f44336; }
        .action-box {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        .results-table th {
            background-color: #2C3162;
            color: white;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($results['success_message'])): ?>
            <div class="success-alert">
                ? <?php echo htmlspecialchars($results['success_message']); ?>
            </div>
        <?php endif; ?>

        <h1 class="text-center" style="color: #2C3162;">GAD-7 Assessment Results</h1>

        <div class="patient-info" style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
            <h3>Patient Information</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div><strong>Client Name:</strong> <?php echo htmlspecialchars($results['clientName']); ?></div>
                <div><strong>MAT ID:</strong> <?php echo htmlspecialchars($results['mat_id']); ?></div>
                <div><strong>Age:</strong> <?php echo htmlspecialchars($results['age']); ?> Years</div>
                <div><strong>Assessment Date:</strong> <?php echo htmlspecialchars($results['visitDate']); ?></div>
            </div>
        </div>

        <hr>

        <div class="score-display">
            Total GAD-7 Score: <?php echo $results['total_score']; ?> / 21
        </div>

        <div class="severity-box <?php echo $severity_class; ?>">
            <strong>Anxiety Severity:</strong> <?php echo $results['severity']; ?>
        </div>

        <?php if ($results['warrant_assessment']): ?>
            <div class="action-box">
                <h4>?? Action Required - Further Assessment Needed</h4>
                <p><strong>Score: <?php echo $results['total_score']; ?> (= 8 points)</strong></p>
                <p><strong>Recommended actions:</strong></p>
                <ul>
                    <li>Refer to mental health specialist for comprehensive evaluation</li>
                    <li>Schedule immediate follow-up appointment</li>
                    <li>Provide crisis intervention if needed</li>
                    <li>Consider pharmacological intervention if appropriate</li>
                    <li>Regular monitoring and support</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="action-box">
                <h4>? Screening Complete - Routine Monitoring Recommended</h4>
                <p><strong>Score: <?php echo $results['total_score']; ?> (< 8 points)</strong></p>
                <p><strong>Follow-up recommendations:</strong></p>
                <ul>
                    <li>Continue with routine care</li>
                    <li>Schedule next regular assessment</li>
                    <li>Provide psychoeducation on anxiety management</li>
                    <li>Encourage healthy lifestyle practices</li>
                </ul>
            </div>
        <?php endif; ?>

        <h3>Detailed Question Scores</h3>
        <table class="results-table">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Score (0-3)</th>
                    <th>Interpretation</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $questions = [
                    'q1' => 'Feeling nervous, anxious or on edge',
                    'q2' => 'Not being able to stop worrying or control worrying',
                    'q3' => 'Worrying too much about different things',
                    'q4' => 'Trouble relaxing',
                    'q5' => 'Being so restless that it is hard to sit still',
                    'q6' => 'Becoming easily annoyed or irritable',
                    'q7' => 'Feeling afraid-as if something awful might happen'
                ];

                foreach ($questions as $key => $text):
                    $score = $results['scores'][$key];
                    $interpretation = '';
                    if ($score == 0) $interpretation = 'Not at all';
                    elseif ($score == 1) $interpretation = 'Several days';
                    elseif ($score == 2) $interpretation = 'More than half the days';
                    else $interpretation = 'Nearly every day';
                ?>
                <tr>
                    <td><?php echo $text; ?></td>
                    <td><strong><?php echo $score; ?></strong></td>
                    <td><?php echo $interpretation; ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td><strong>TOTAL SCORE</strong></td>
                    <td><strong><?php echo $results['total_score']; ?></strong></td>
                    <td><?php echo $results['severity']; ?></td>
                </tr>
            </tbody>
        </table>

        <div class="btn-group">
            <a href="gad7_form.php?mat_id=<?php echo urlencode($results['mat_id']); ?>" class="btn btn-primary">
                ?? New Assessment for Same Client
            </a>
            <a href="search_gad7_form.php" class="btn btn-secondary">
                ?? Back to Search
            </a>
            <a href="../patients/view_patient.php?mat_id=<?php echo urlencode($results['mat_id']); ?>" class="btn btn-success">
                ?? View Patient Profile
            </a>
            <button onclick="window.print()" class="btn" style="background-color: #17a2b8; color: white;">
                ??? Print Results
            </button>
        </div>

        <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 4px; font-size: 0.9em;">
            <p><strong>Note:</strong> This screening tool is not a diagnostic instrument. It is intended to identify probable cases of generalized anxiety disorder. Clinical judgment and further assessment are required for diagnosis.</p>
        </div>
    </div>
</body>
</html>