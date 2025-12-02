<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Initialize scores
    $total_yes = 0;
    $total_no = 0;

    // 2. Define the list of question keys (q1 to q19)
    $question_keys = [];
    $question_responses = []; // Store individual responses
    for ($i = 1; $i <= 19; $i++) {
        $question_keys[] = 'q' . $i;
    }

    // Define question texts for better display
    $question_texts = [
        'q1' => 'Client is stabilized and improving',
        'q2' => 'Client has achieved treatment goals',
        'q3' => 'Client demonstrates reduced substance use',
        'q4' => 'Client shows improved mental health',
        'q5' => 'Client has stable housing situation',
        'q6' => 'Client maintains employment/education',
        'q7' => 'Client has strong support system',
        'q8' => 'Client attends sessions regularly',
        'q9' => 'Client demonstrates medication compliance',
        'q10' => 'Client shows improved relationships',
        'q11' => 'Client has developed coping skills',
        'q12' => 'Client manages triggers effectively',
        'q13' => 'Client participates in aftercare planning',
        'q14' => 'Client expresses readiness for transition',
        'q15' => 'Client has emergency contact plan',
        'q16' => 'Client understands relapse prevention',
        'q17' => 'Client has follow-up appointments scheduled',
        'q18' => 'Client meets medical stability criteria',
        'q19' => 'Client agrees with cessation decision'
    ];

    // 3. Calculate the total Yes and No scores
    foreach ($question_keys as $key) {
        if (isset($_POST[$key])) {
            $response = (int)$_POST[$key];
            $question_responses[$key] = $response;
            if ($response === 1) {
                $total_yes++;
            } else {
                $total_no++;
            }
        } else {
            $error_message = "Error: Please answer all 19 questions on the form.";
            header("Location: mat_cessation_form.php?message=" . urlencode($error_message));
            exit();
        }
    }

    // 4. Extract other form data
    $name = htmlspecialchars($_POST['name'] ?? 'N/A');
    $mat_id = htmlspecialchars($_POST['mat_id'] ?? 'N/A');
    $age = htmlspecialchars($_POST['age'] ?? 'N/A');
    $assessment_date = htmlspecialchars($_POST['assessment_date'] ?? date('Y-m-d'));
    $clinician = htmlspecialchars($_POST['clinician'] ?? $_SESSION['full_name'] ?? 'N/A');
    $total_questions = count($question_keys);

    // Calculate percentage
    $percentage = round(($total_yes / $total_questions) * 100, 1);

    // Determine recommendation based on score
    $recommendation = "";
    $recommendation_class = "";
    if ($percentage >= 80) {
        $recommendation = "Client meets criteria for MAT cessation. Proceed with tapering plan.";
        $recommendation_class = "recommendation-success";
    } elseif ($percentage >= 60) {
        $recommendation = "Client shows good progress. Consider extended monitoring before cessation.";
        $recommendation_class = "recommendation-warning";
    } else {
        $recommendation = "Client does not meet cessation criteria. Continue MAT and reassess in 30 days.";
        $recommendation_class = "recommendation-danger";
    }

    // 5. Display the Results (HTML Output)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Cessation Assessment Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #007bff;
            margin: 0;
            flex-grow: 1;
            text-align: center;
            font-size: 1.8em;
        }

        .success-banner {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section h2 {
            color: #555;
            border-left: 5px solid #007bff;
            padding-left: 10px;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .info-item strong {
            min-width: 150px;
            color: #555;
        }

        .score-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .score-card {
            text-align: center;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .score-card.yes {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .score-card.no {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .score-card.percentage {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .score-card h3 {
            margin: 0 0 10px 0;
            font-size: 1em;
            opacity: 0.9;
        }

        .score-card .score-value {
            font-size: 3em;
            font-weight: bold;
            margin: 10px 0;
        }

        .score-card .score-label {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .recommendation-box {
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 5px solid;
        }

        .recommendation-success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .recommendation-warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .recommendation-danger {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .recommendation-box h3 {
            margin-top: 0;
            font-size: 1.2em;
        }

        .recommendation-box p {
            margin: 10px 0 0 0;
            font-size: 1.1em;
            line-height: 1.6;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .details-table th,
        .details-table td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            text-align: left;
        }

        .details-table thead th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }

        .details-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .details-table td:first-child {
            width: 5%;
            text-align: center;
            font-weight: bold;
        }

        .details-table td:nth-child(2) {
            width: 70%;
        }

        .details-table td:last-child {
            width: 25%;
            text-align: center;
        }

        .response-yes {
            color: #28a745;
            font-weight: bold;
        }

        .response-no {
            color: #dc3545;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-buttons button,
        .action-buttons a {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-print {
            background-color: #17a2b8;
            color: white;
        }

        .btn-print:hover {
            background-color: #138496;
        }

        .btn-new {
            background-color: #28a745;
            color: white;
        }

        .btn-new:hover {
            background-color: #218838;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        @media print {
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MAT Cessation Assessment Results</h1>
        </div>

        <div class="success-banner">
            ✓ Assessment Completed Successfully
        </div>

        <div class="info-section">
            <h2>Client Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Client Name:</strong>
                    <span><?php echo $name; ?></span>
                </div>
                <div class="info-item">
                    <strong>MAT ID:</strong>
                    <span><?php echo $mat_id; ?></span>
                </div>
                <div class="info-item">
                    <strong>Age:</strong>
                    <span><?php echo $age; ?></span>
                </div>
                <div class="info-item">
                    <strong>Assessment Date:</strong>
                    <span><?php echo date('F d, Y', strtotime($assessment_date)); ?></span>
                </div>
                <div class="info-item">
                    <strong>Assessed By:</strong>
                    <span><?php echo $clinician; ?></span>
                </div>
                <div class="info-item">
                    <strong>Total Questions:</strong>
                    <span><?php echo $total_questions; ?></span>
                </div>
            </div>
        </div>

        <div class="score-cards">
            <div class="score-card yes">
                <h3>Total Yes</h3>
                <div class="score-value"><?php echo $total_yes; ?></div>
                <div class="score-label">Positive Indicators</div>
            </div>
            <div class="score-card no">
                <h3>Total No</h3>
                <div class="score-value"><?php echo $total_no; ?></div>
                <div class="score-label">Areas of Concern</div>
            </div>
            <div class="score-card percentage">
                <h3>Success Rate</h3>
                <div class="score-value"><?php echo $percentage; ?>%</div>
                <div class="score-label">Readiness Score</div>
            </div>
        </div>

        <div class="recommendation-box <?php echo $recommendation_class; ?>">
            <h3>Clinical Recommendation</h3>
            <p><?php echo $recommendation; ?></p>
        </div>

        <div class="info-section">
            <h2>Detailed Assessment Breakdown</h2>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Criterion</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $question_num = 1;
                    foreach ($question_responses as $key => $response):
                        $response_text = ($response === 1) ? 'YES' : 'NO';
                        $response_class = ($response === 1) ? 'response-yes' : 'response-no';
                        $question_text = isset($question_texts[$key]) ? $question_texts[$key] : "Question $question_num";
                    ?>
                    <tr>
                        <td><?php echo $question_num; ?></td>
                        <td><?php echo htmlspecialchars($question_text); ?></td>
                        <td class="<?php echo $response_class; ?>"><?php echo $response_text; ?></td>
                    </tr>
                    <?php
                        $question_num++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <div class="action-buttons">
            <button class="btn-print" onclick="window.print()">🖨️ Print Results</button>
            <a href="mat_cessation_form.php" class="btn-new">📝 New Assessment</a>
            <a href="search_patients.php" class="btn-back">⬅️ Back to Search</a>
        </div>
    </div>
</body>
</html>
<?php
} else {
    // If someone tries to access this page directly
    header("Location: mat_cessation_form.php");
    exit();
}
?>