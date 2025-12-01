<?php
session_start();
// NOTE: Ensure your config.php path is correct relative to this file
include "../includes/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Get logged-in user details
$loggedInUserId = $_SESSION['user_id'];
$therapists_name = $_SESSION['full_name'] ?? 'Unknown Therapist';

// --- PHQ-9 FORM DATA FETCH LOGIC ---

$p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;
$currentSettings = [];

// 1. Try fetching by p_id (primary key)
if ($p_id) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $p_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}
// 2. If p_id is not set or failed to fetch, try fetching by mat_id
else if ($mat_id) {
    // Sanitize mat_id for use in prepared statement
    $mat_id_sanitized = htmlspecialchars($mat_id);

    $query = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $mat_id_sanitized);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Ensure the patient's p_id is available for form submission, even if we started with mat_id
$patient_p_id = isset($currentSettings['p_id']) ? $currentSettings['p_id'] : null;

// Handle case where patient data couldn't be found
if (!$currentSettings && ($p_id || $mat_id)) {
    $message = "Error: Patient not found.";
    header("Location: search_phq9.php?message=" . urlencode($message));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Questionnaire-9 (PHQ-9)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- General Styling --- */
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

        /* --- Header Styling --- */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .form-header h4 {
            color: #007bff;
            font-weight: 600;
            text-align: center;
            flex-grow: 1;
        }
        .form-header img {
            margin-right: 20px;
        }
        .form-header p {
            font-size: 0.8em;
            color: #666;
            margin: 0;
            text-align: right;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        h4 {
            color: #555;
            border-left: 5px solid #007bff;
            padding-left: 10px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        hr {
            border: 0;
            height: 1px;
            background: #ccc;
            margin: 30px 0;
        }
        .instruction {
            font-style: italic;
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #ffc107;
        }

        /* --- Input Grid Layout --- */
        .input-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
            font-size: 0.9em;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            width: 100%;
        }
        .readonly-input {
            background-color: #e9ecef;
            color: #495057;
            cursor: not-allowed;
        }

        /* --- Table Styling --- */
        .phq9-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .phq9-table th, .phq9-table td {
            border: 1px solid #e0e0e0;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
        }
        .phq9-table thead th {
            background-color: #007bff;
            color: white;
            font-weight: 700;
            font-size: 0.9em;
        }
        .phq9-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .phq9-table td:first-child {
            width: 5%;
            font-weight: bold;
        }
        .phq9-table td:nth-child(2) {
            text-align: left;
            width: 45%;
        }
        .phq9-table td input[type="radio"] {
            transform: scale(1.3);
            cursor: pointer;
        }

        /* --- Submit Button --- */
        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }
        button[type="submit"]:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <h4>Patient Health Questionnaire-9 (PHQ-9) for Depression Screening</h4>
            <p>FORM VER. APR. 2022</p>
        </div>

        <h2>General Information</h2>
        <form action="process_phq9.php" method="POST">
            <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($patient_p_id); ?>">
            <input type="hidden" name="therapist_id" value="<?php echo htmlspecialchars($loggedInUserId); ?>">
            <input type="hidden" name="therapist_name_submit" value="<?php echo htmlspecialchars($therapists_name); ?>">

            <div class="social-demographic-grid">
                <div class="section-column">
                    <h4>PERSONAL DETAILS</h4>
                    <div class="input-grid">
                        <div class="form-group">
                            <label for="visitDate">Date of consultation</label>
                            <input type="date" name="visitDate" id="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="clientName">Client Name</label>
                            <input type="text" name="clientName" id="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="mat_id">MAT ID</label>
                            <input type="text" name="mat_id" id="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="readonly-input" readonly value="<?php echo isset($currentSettings['dob']) ? htmlspecialchars($currentSettings['dob']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="text" name="age" id="age" class="readonly-input" readonly value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="occupation">Occupation</label>
                            <input type="text" name="occupation" value="<?php echo isset($currentSettings['occupation']) ? htmlspecialchars($currentSettings['occupation']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="sex">Gender</label>
                            <input type="text" name="sex" id="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="kp_type">KP Type</label>
                            <select id="kp_type" name="kp_type">
                                <option value="FSW" <?php if(isset($currentSettings['kp_type']) && $currentSettings['kp_type'] == 'FSW') echo 'selected'; ?>>FSW</option>
                                <option value="MSM" <?php if(isset($currentSettings['kp_type']) && $currentSettings['kp_type'] == 'MSM') echo 'selected'; ?>>MSM</option>
                                <option value="MSW" <?php if(isset($currentSettings['kp_type']) && $currentSettings['kp_type'] == 'MSW') echo 'selected'; ?>>MSW</option>
                                <option value="PWID" <?php if(isset($currentSettings['kp_type']) && $currentSettings['kp_type'] == 'PWID') echo 'selected'; ?>>PWID</option>
                                <option value="TG Man" <?php if(isset($currentSettings['kp_type']) && $currentSettings['kp_type'] == 'TG Man') echo 'selected'; ?>>TG man</option>
                                <option value="TG woman" <?php if(isset($currentSettings['kp_type']) && $currentSettings['kp_type'] == 'TG woman') echo 'selected'; ?>>TG woman</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="vp_type">VP Type</label>
                            <select id="vp_type" name="vp_type">
                                <option value="">N/A</option>
                                <option value="Fisherfolk" <?php if(isset($currentSettings['vp_type']) && $currentSettings['vp_type'] == 'Fisherfolk') echo 'selected'; ?>>Fisherfolk</option>
                                <option value="Truckers" <?php if(isset($currentSettings['vp_type']) && $currentSettings['vp_type'] == 'Truckers') echo 'selected'; ?>>Truckers</option>
                                <option value="Discordant couples" <?php if(isset($currentSettings['vp_type']) && $currentSettings['vp_type'] == 'Discordant couples') echo 'selected'; ?>>Discordant couples</option>
                                <option value="Persons in prison" <?php if(isset($currentSettings['vp_type']) && $currentSettings['vp_type'] == 'Persons in prison') echo 'selected'; ?>>Persons in prison</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="kp_hotspot">KP Hotspot</label>
                            <input type="text" name="kp_hotspot" value="<?php echo isset($currentSettings['kp_hotspot']) ? htmlspecialchars($currentSettings['kp_hotspot']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Contact</label>
                            <input type="text" name="contact_phone" value="<?php echo isset($currentSettings['contact_phone']) ? htmlspecialchars($currentSettings['contact_phone']) : ''; ?>">
                        </div>

                    </div>
                </div>
            <hr>

            <h2>PHQ-9 Assessment</h2>
            <p class="instruction">Over the last **2 weeks**, how often have you been bothered by any of the following problems?</p>

            <table class="phq9-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Problem</th>
                        <th>Not at all (0)</th>
                        <th>Several days (1)</th>
                        <th>More than half the days (2)</th>
                        <th>Nearly every day (3)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Little interest or pleasure in doing things</td><td><input type="radio" name="q1" value="0" required></td><td><input type="radio" name="q1" value="1"></td><td><input type="radio" name="q1" value="2"></td><td><input type="radio" name="q1" value="3"></td></tr>
                    <tr><td>2</td><td>Feeling down, depressed, or hopeless</td><td><input type="radio" name="q2" value="0" required></td><td><input type="radio" name="q2" value="1"></td><td><input type="radio" name="q2" value="2"></td><td><input type="radio" name="q2" value="3"></td></tr>
                    <tr><td>3</td><td>Trouble falling or staying asleep, or sleeping too much</td><td><input type="radio" name="q3" value="0" required></td><td><input type="radio" name="q3" value="1"></td><td><input type="radio" name="q3" value="2"></td><td><input type="radio" name="q3" value="3"></td></tr>
                    <tr><td>4</td><td>Feeling tired or having little energy</td><td><input type="radio" name="q4" value="0" required></td><td><input type="radio" name="q4" value="1"></td><td><input type="radio" name="q4" value="2"></td><td><input type="radio" name="q4" value="3"></td></tr>
                    <tr><td>5</td><td>Poor appetite or overeating</td><td><input type="radio" name="q5" value="0" required></td><td><input type="radio" name="q5" value="1"></td><td><input type="radio" name="q5" value="2"></td><td><input type="radio" name="q5" value="3"></td></tr>
                    <tr><td>6</td><td>Feeling bad about yourself, or that you are a failure, or that you have let yourself or your family down</td><td><input type="radio" name="q6" value="0" required></td><td><input type="radio" name="q6" value="1"></td><td><input type="radio" name="q6" value="2"></td><td><input type="radio" name="q6" value="3"></td></tr>
                    <tr><td>7</td><td>Trouble concentrating on things (e.g., reading the newspaper or listening to a radio programme)</td><td><input type="radio" name="q7" value="0" required></td><td><input type="radio" name="q7" value="1"></td><td><input type="radio" name="q7" value="2"></td><td><input type="radio" name="q7" value="3"></td></tr>
                    <tr><td>8</td><td>Moving or speaking so slowly that other people could have noticed. Or the opposite, being so fidgety or restless that you have been moving around a lot more than usual</td><td><input type="radio" name="q8" value="0" required></td><td><input type="radio" name="q8" value="1"></td><td><input type="radio" name="q8" value="2"></td><td><input type="radio" name="q8" value="3"></td></tr>
                    <tr><td>9</td><td>Thoughts that you would be better off dead or of hurting yourself in some way</td><td><input type="radio" name="q9" value="0" required></td><td><input type="radio" name="q9" value="1"></td><td><input type="radio" name="q9" value="2"></td><td><input type="radio" name="q9" value="3"></td></tr>
                </tbody>
            </table>

            <button type="submit">Calculate Score and Save Assessment</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the DOB input value
            const dobInput = document.getElementById('dob');
            const ageInput = document.getElementById('age');
            const dobString = dobInput ? dobInput.value : null;

            if (dobString) {
                // Function to calculate age based on date of birth
                function calculateAge(dob) {
                    const birthDate = new Date(dob);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDifference = today.getMonth() - birthDate.getMonth();

                    // Adjust age if the birthday hasn't passed yet this year
                    if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    return age;
                }

                const calculatedAge = calculateAge(dobString);

                // Update the age field only if the calculation is valid (non-negative)
                if (calculatedAge >= 0) {
                    ageInput.value = calculatedAge;
                } else {
                    ageInput.value = 'N/A';
                }
            } else {
                ageInput.value = 'N/A (DOB missing)';
            }
        });
    </script>
</body>
</html>