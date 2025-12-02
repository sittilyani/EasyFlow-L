<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if results exist in session
if (!isset($_SESSION['phq9_results'])) {
    header("Location: search_phq9_form.php?message=" . urlencode("No results to display"));
    exit();
}

$results = $_SESSION['phq9_results'];

// Clear the session data after retrieving (optional - remove if you want to keep it)
// unset($_SESSION['phq9_results']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHQ-9 Assessment Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 900px;
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

        .header h2 {
            color: #007bff;
            margin: 0;
            flex-grow: 1;
            text-align: center;
        }

        .header img {
            margin-right: 20px;
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

        .results-section {
            margin-bottom: 30px;
        }

        .results-section h3 {
            color: #555;
            border-left: 5px solid #007bff;
            padding-left: 10px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .info-item strong {
            min-width: 140px;
            color: #555;
        }

        .score-display {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin: 25px 0;
        }

        .score-display h2 {
            margin: 0;
            font-size: 3em;
            font-weight: bold;
        }

        .score-display p {
            margin: 10px 0 0 0;
            font-size: 1.2em;
        }

        .diagnosis-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .diagnosis-box h4 {
            color: #856404;
            margin-top: 0;
            font-size: 1.3em;
        }

        .diagnosis-box p {
            color: #856404;
            font-size: 1.1em;
            font-weight: 600;
            margin: 0;
        }

        .management-box {
            background-color: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .management-box h4 {
            color: #0c5460;
            margin-top: 0;
            font-size: 1.2em;
        }

        .management-box ul {
            margin: 10px 0;
            padding-left: 25px;
        }

        .management-box li {
            margin: 10px 0;
            line-height: 1.6;
            color: #004085;
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

        .btn-print {
            background-color: #17a2b8;
            color: white;
        }

        .btn-print:hover {
            background-color: #138496;
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
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government of Kenya">
            <h2>PHQ-9 Assessment Results</h2>
        </div>

        <div class="success-banner">
            ? Assessment Successfully Saved!
        </div>

        <div class="results-section">
            <h3>Patient Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Patient Name:</strong>
                    <span><?php echo htmlspecialchars($results['clientName']); ?></span>
                </div>
                <div class="info-item">
                    <strong>MAT ID:</strong>
                    <span><?php echo htmlspecialchars($results['mat_id']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Age:</strong>
                    <span><?php echo htmlspecialchars($results['age']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Assessment Date:</strong>
                    <span><?php echo date('F d, Y', strtotime($results['visitDate'])); ?></span>
                </div>
                <div class="info-item">
                    <strong>Assessed by:</strong>
                    <span><?php echo htmlspecialchars($results['therapist_name']); ?></span>
                </div>
            </div>
        </div>

        <div class="score-display">
            <h2><?php echo $results['total_score']; ?></h2>
            <p>Total PHQ-9 Score (out of 27)</p>
        </div>

        <div class="diagnosis-box">
            <h4>Provisional Diagnosis</h4>
            <p><?php echo htmlspecialchars($results['diagnosis']); ?></p>
        </div>

        <div class="management-box">
            <h4>Recommended Management Plan</h4>
            <ul>
                <?php foreach ($results['management'] as $step): ?>
                    <li><?php echo htmlspecialchars($step); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="action-buttons">
            <button class="btn-print" onclick="window.print()">??? Print Results</button>
            <a href="search_phq9_form.php" class="btn-new">?? New Assessment</a>
            <a href="search_phq9.php" class="btn-back">?? Back to Search</a>
        </div>
    </div>

    <script>
        // Optional: Auto-clear session after displaying results
        window.addEventListener('beforeunload', function() {
            // This will clear the results when navigating away
            // Remove if you want to keep the ability to refresh the page
        });
    </script>
</body>
</html>