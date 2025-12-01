<?php
session_start();
include '../includes/config.php';

// Get mat_id from URL
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : '';

// Fetch patient data from database
$patientData = [];
if (!empty($mat_id)) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE mat_id = ?");
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $patientData = $result->fetch_assoc();

        // Calculate age from dob if dob exists
        if (!empty($patientData['dob'])) {
            $dob = new DateTime($patientData['dob']);
            $today = new DateTime();
            $age = $dob->diff($today)->y;
            $patientData['age'] = $age;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generalized Anxiety Disorder Screening Tool (GAD-7)</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.bundle.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width:70%;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            margin-bottom: 30px;
        }

        h1, h2 {
            text-align: center;
            color: #0056b3;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-group input[type="text"],
        .info-group input[type="number"],
        .info-group input[type="date"],
        .info-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .readonly-input {
            background-color: #f5f5f5;
            color: #666;
        }

        hr {
            border: 0;
            height: 1px;
            background: #ccc;
            margin: 20px 0;
        }

        .instruction {
            font-style: italic;
            margin-bottom: 15px;
        }

        .gad7-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .gad7-table th, .gad7-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .gad7-table th:first-child, .gad7-table td:first-child {
            text-align: left;
            width: 40%;
        }

        .gad7-table thead th {
            background-color: #e9e9e9;
            font-weight: bold;
        }

        .gad7-table td input[type="radio"] {
            transform: scale(1.5);
        }

        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .social-demographic-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }

    </style>
    <script>
        // Function to calculate age from date of birth
        function calculateAge() {
            const dobInput = document.getElementById('dob');
            const ageInput = document.getElementById('age');

            if (dobInput.value) {
                const dob = new Date(dobInput.value);
                const today = new Date();

                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }

                ageInput.value = age;
            }
        }

        // Function to validate form before submission
        function validateForm() {
            // Check if all GAD-7 questions are answered
            const questions = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7'];
            let allAnswered = true;

            for (let i = 0; i < questions.length; i++) {
                const questionName = questions[i];
                const radios = document.getElementsByName(questionName);
                let checked = false;

                for (let j = 0; j < radios.length; j++) {
                    if (radios[j].checked) {
                        checked = true;
                        break;
                    }
                }

                if (!checked) {
                    allAnswered = false;
                    // Highlight the row
                    const row = radios[0].closest('tr');
                    row.style.backgroundColor = '#ffe6e6';

                    // Remove highlighting from other rows
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 3000);
                }
            }

            if (!allAnswered) {
                alert('Please answer all GAD-7 questions before submitting.');
                return false;
            }

            return true;
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate age on page load if dob exists
            calculateAge();

            // Add event listener for dob changes
            const dobInput = document.getElementById('dob');
            if (dobInput) {
                dobInput.addEventListener('change', calculateAge);
            }

            // Add form validation
            const form = document.getElementById('gad7Form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Processing...';
                });
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <div><h4>GENERALIZED ANXIETY DISORDER SCREENING TOOL</h4></div>
            <p>FORM VER. APR. 2022</p>
        </div>

        <h1>Generalized Anxiety Disorder Screening Tool</h1>
        <h2>General Information</h2>

        <?php if (!empty($mat_id) && empty($patientData)): ?>
            <div style="color: red; padding: 10px; border: 1px solid red; background-color: #ffe6e6; margin-bottom: 20px;">
                Error: Patient with MAT ID <?php echo htmlspecialchars($mat_id); ?> not found.
            </div>
        <?php endif; ?>

        <form id="gad7Form" action="process_gad7.php" method="POST" onsubmit="return validateForm()">
            <!-- Hidden field to pass mat_id -->
            <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($mat_id); ?>">

            <div class="social-demographic-grid">
                <div style="grid-column: 1 / span 4;">
                    <h4>PERSONAL DETAILS</h4>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="visitDate">Date of consultation</label>
                            <input type="date" id="visitDate" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="clientName">Client Name</label>
                            <input type="text" id="clientName" name="clientName" class="readonly-input" readonly
                                   value="<?php echo isset($patientData['clientName']) ? htmlspecialchars($patientData['clientName']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="mat_id_display">MAT ID</label>
                            <input type="text" id="mat_id_display" name="mat_id_display" class="readonly-input" readonly
                                   value="<?php echo isset($patientData['mat_id']) ? htmlspecialchars($patientData['mat_id']) : htmlspecialchars($mat_id); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="readonly-input"
                                   value="<?php echo isset($patientData['dob']) ? htmlspecialchars($patientData['dob']) : ''; ?>"
                                   onchange="calculateAge()">
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" class="readonly-input" readonly
                                   value="<?php echo isset($patientData['age']) ? htmlspecialchars($patientData['age']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="occupation">Occupation</label>
                            <input type="text" id="occupation" name="occupation"
                                   value="<?php echo isset($patientData['occupation']) ? htmlspecialchars($patientData['occupation']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="sex">Gender</label>
                            <input type="text" id="sex" name="sex" class="readonly-input" readonly
                                   value="<?php echo isset($patientData['sex']) ? htmlspecialchars($patientData['sex']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="kp_type">KP Type</label>
                            <select id="kp_type" name="kp_type">
                                <option value="">Select KP Type</option>
                                <option value="FSW" <?php echo (isset($patientData['kp_type']) && $patientData['kp_type'] == 'FSW') ? 'selected' : ''; ?>>FSW</option>
                                <option value="MSM" <?php echo (isset($patientData['kp_type']) && $patientData['kp_type'] == 'MSM') ? 'selected' : ''; ?>>MSM</option>
                                <option value="MSW" <?php echo (isset($patientData['kp_type']) && $patientData['kp_type'] == 'MSW') ? 'selected' : ''; ?>>MSW</option>
                                <option value="PWID" <?php echo (isset($patientData['kp_type']) && $patientData['kp_type'] == 'PWID') ? 'selected' : ''; ?>>PWID</option>
                                <option value="TG Man" <?php echo (isset($patientData['kp_type']) && $patientData['kp_type'] == 'TG Man') ? 'selected' : ''; ?>>TG man</option>
                                <option value="TG woman" <?php echo (isset($patientData['kp_type']) && $patientData['kp_type'] == 'TG woman') ? 'selected' : ''; ?>>TG woman</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="vp_type">VP Type</label>
                            <select id="vp_type" name="vp_type">
                                <option value="">Select VP Type</option>
                                <option value="Fisherfolk">Fisherfolk</option>
                                <option value="Truckers">Truckers</option>
                                <option value="Discordant couples">Discordant couples</option>
                                <option value="Persons in prison">Persons in prison</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kp_hotspot">KP Hotspot</label>
                            <input type="text" id="kp_hotspot" name="kp_hotspot"
                                   value="<?php echo isset($patientData['kp_hotspot']) ? htmlspecialchars($patientData['kp_hotspot']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Contact</label>
                            <input type="text" id="contact_phone" name="contact_phone"
                                   value="<?php echo isset($patientData['contact_phone']) ? htmlspecialchars($patientData['contact_phone']) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <h2>GAD-7 Assessment</h2>
            <p>The Seven-item Generalized Anxiety Disorder Assessment (GAD7) is used to perform initial screening. The GAD7 score is obtained by adding the score for each question to yield total points scored across the tool.</p>
            <p class="instruction">Over the last two weeks, how often have you been bothered by the following problems?</p>

            <table class="gad7-table">
                <thead>
                    <tr>
                        <th>Problem</th>
                        <th>Not at all (0)</th>
                        <th>Several days (+1)</th>
                        <th>More than half the days (+2)</th>
                        <th>Nearly every day (+3)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1. Feeling nervous, anxious or on edge</td>
                        <td><input type="radio" name="q1" value="0" required></td>
                        <td><input type="radio" name="q1" value="1"></td>
                        <td><input type="radio" name="q1" value="2"></td>
                        <td><input type="radio" name="q1" value="3"></td>
                    </tr>
                    <tr>
                        <td>2. Not being able to stop worrying or control worrying</td>
                        <td><input type="radio" name="q2" value="0" required></td>
                        <td><input type="radio" name="q2" value="1"></td>
                        <td><input type="radio" name="q2" value="2"></td>
                        <td><input type="radio" name="q2" value="3"></td>
                    </tr>
                    <tr>
                        <td>3. Worrying too much about different things</td>
                        <td><input type="radio" name="q3" value="0" required></td>
                        <td><input type="radio" name="q3" value="1"></td>
                        <td><input type="radio" name="q3" value="2"></td>
                        <td><input type="radio" name="q3" value="3"></td>
                    </tr>
                    <tr>
                        <td>4. Trouble relaxing</td>
                        <td><input type="radio" name="q4" value="0" required></td>
                        <td><input type="radio" name="q4" value="1"></td>
                        <td><input type="radio" name="q4" value="2"></td>
                        <td><input type="radio" name="q4" value="3"></td>
                    </tr>
                    <tr>
                        <td>5. Being so restless that it is hard to sit still</td>
                        <td><input type="radio" name="q5" value="0" required></td>
                        <td><input type="radio" name="q5" value="1"></td>
                        <td><input type="radio" name="q5" value="2"></td>
                        <td><input type="radio" name="q5" value="3"></td>
                    </tr>
                    <tr>
                        <td>6. Becoming easily annoyed or irritable</td>
                        <td><input type="radio" name="q6" value="0" required></td>
                        <td><input type="radio" name="q6" value="1"></td>
                        <td><input type="radio" name="q6" value="2"></td>
                        <td><input type="radio" name="q6" value="3"></td>
                    </tr>
                    <tr>
                        <td>7. Feeling afraid-as if something awful might happen</td>
                        <td><input type="radio" name="q7" value="0" required></td>
                        <td><input type="radio" name="q7" value="1"></td>
                        <td><input type="radio" name="q7" value="2"></td>
                        <td><input type="radio" name="q7" value="3"></td>
                    </tr>
                </tbody>
            </table>

            <button type="submit">Calculate Score and Interpret</button>
        </form>
    </div>
</body>
</html>