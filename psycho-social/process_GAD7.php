<?php
session_start();
include "../includes/config.php";

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Initialize score and define question keys
    $total_score = 0;
    $question_keys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7'];
    $q_scores = [];

    // Collect patient and user info from POST data
    $mat_id = $_POST['mat_id'] ?? null;
    $clientName = $_POST['clientName'] ?? 'N/A';
    $visitDate = $_POST['visitDate'] ?? date('Y-m-d');
    $age = $_POST['age'] ?? 'N/A'; // Assuming age is available from the form if needed for display
    $therapist_id = $_SESSION['user_id'] ?? 0;

    // Fetch p_id (patient's primary key) from mat_id
    $p_id = null;
    if ($mat_id) {
        $stmt_p = $conn->prepare("SELECT p_id FROM patients WHERE mat_id = ?");
        $stmt_p->bind_param('s', $mat_id);
        $stmt_p->execute();
        $result_p = $stmt_p->get_result();
        if ($row_p = $result_p->fetch_assoc()) {
            $p_id = $row_p['p_id'];
        }
        $stmt_p->close();
    }


    // 2. Calculate the total score and check for missing answers
    foreach ($question_keys as $key) {
        if (isset($_POST[$key])) {
            $score = (int)$_POST[$key];
            $total_score += $score;
            $q_scores[$key] = $score;
        } else {
            die("Error: Please answer all GAD-7 questions.");
        }
    }

    // 3. Determine the Provisional Diagnosis and Recommended Management
    $diagnosis = "";
    $management = [];

    if ($total_score >= 15) {
        $diagnosis = "Severe anxiety";
        $management[] = "Provide supportive counselling (refer to a psychologist if available).";
        $management[] = "Refer to a medical officer, psychiatrist, or mental health team immediately.";
        $management[] = "Consider pharmacological treatment (e.g., SSRIs) in consultation with a specialist.";
    } elseif ($total_score >= 10) {
        $diagnosis = "Moderate anxiety";
        $management[] = "Provide supportive counselling and psychoeducation.";
        $management[] = "Refer to a psychologist or mental health team.";
        $management[] = "Monitor closely; consider referral to a medical officer if symptoms worsen or persist.";
    } elseif ($total_score >= 5) {
        $diagnosis = "Mild anxiety";
        $management[] = "Provide basic counselling support and psychoeducation (e.g., coping mechanisms, breathing exercises).";
        $management[] = "Continue to monitor and re-screen in 4-6 weeks.";
        $management[] = "Refer to a mental health worker or peer support group if available.";
    } else { // Score 0-4
        $diagnosis = "Minimal anxiety";
        $management[] = "No further intervention required at this time.";
        $management[] = "Repeat screening in future if new concerns arise.";
    }

    $management_plan_text = implode("\n", $management); // For saving

    // --- 4. Database Insertion (Only if p_id is found) ---
    $save_successful = false;
    if ($p_id && $mat_id) {
        $columns = "`p_id`, `mat_id`, `visitDate`, `therapist_id`, `total_score`, `diagnosis`, `management_plan`, " . implode(", ", array_map(function($q) { return "`$q`"; }, $question_keys));
        $placeholders = str_repeat("?, ", 13) . "?";
        $sql = "INSERT INTO gad7_assessments ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $types = "isssiss" . str_repeat("i", count($question_keys));

        $bind_values = [
            $p_id, $mat_id, $visitDate, $therapist_id, $total_score, $diagnosis, $management_plan_text
        ];
        foreach ($q_scores as $score) {
            $bind_values[] = $score;
        }

        $stmt->bind_param($types, ...$bind_values);

        if ($stmt->execute()) {
            $save_successful = true;
        } else {
            // Handle DB error
            $message = urlencode("Database Error: Could not save assessment. " . $stmt->error);
            header("Location: search_gad7.php?message=" . $message . "&status=error");
            exit();
        }
        $stmt->close();
    } else {
        // Missing IDs error - stop process
        $message = urlencode("Error: Missing patient ID or MAT ID. Assessment not saved.");
        header("Location: search_gad7.php?message=" . $message . "&status=error");
        exit();
    }


// --- 5. Display the Results (HTML Output) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAD-7 Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        h3 {
            margin-top: 20px;
            color: #555;
        }
        .result-box {
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 1.1em;
            line-height: 1.6;
        }
        /* Color classes based on GAD-7 thresholds */
        .severe { background-color: #fceae5; border: 1px solid #e74c3c; color: #e74c3c; } /* >= 15 */
        .moderate { background-color: #fcf8e5; border: 1px solid #f39c12; color: #f39c12; } /* >= 10 */
        .mild { background-color: #e5f5fc; border: 1px solid #3498db; color: #3498db; } /* >= 5 */
        .minimal { background-color: #e5fce5; border: 1px solid #2ecc71; color: #2ecc71; } /* 0-4 */

        .management-list {
            margin-top: 15px;
            padding-left: 20px;
            list-style-type: disc;
        }
        .management-list li {
            margin-bottom: 8px;
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .action-buttons a {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: opacity 0.3s;
        }
        .action-buttons a.another {
            background-color: #007bff;
            color: white;
        }
        .action-buttons a.exit {
            background-color: #6c757d;
            color: white;
        }
        .action-buttons a:hover {
            opacity: 0.8;
        }
        .success-message {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>GAD-7 Screening Results</h1>

        <?php if ($save_successful): ?>
            <div class="success-message">
                ✅ **Assessment successfully saved to the database.**
            </div>
        <?php endif; ?>

        <h2>Assessment Summary</h2>
        <p><strong>Client Name:</strong> <?php echo htmlspecialchars($clientName); ?></p>
        <p><strong>MAT ID:</strong> <?php echo htmlspecialchars($mat_id); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($visitDate); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?> Years</p>

        <hr>

        <h3>Calculated GAD-7 Score: <span style="font-size: 1.5em; font-weight: bold;"><?php echo $total_score; ?> / 21</span></h3>

        <div class="result-box
            <?php
                if ($total_score >= 15) echo 'severe';
                elseif ($total_score >= 10) echo 'moderate';
                elseif ($total_score >= 5) echo 'mild';
                else echo 'minimal';
            ?>">
            <h3>Provisional Diagnosis: <span style="font-weight: bold;"><?php echo $diagnosis; ?></span></h3>

            <p style="font-weight: bold; margin-top: 10px;">Recommended Management:</p>
            <ul class="management-list">
                <?php
                // Display the management plan that was generated earlier
                foreach ($management as $item): ?>
                    <li><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="search_gad7.php" class="another">Start Another Assessment</a>

            <a href="../dashboard.php" class="exit">Exit to Main Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php
} else {
    // If someone tries to access process_gad7.php directly without POST
    header("Location: gad7_form.php");
    exit();
}
?>