<?php
session_start();
// Include database configuration (assuming this file connects to $conn)
include('../includes/config.php');

// --- 1. Initialize variables ---
$patient_data = [
    'mat_id' => '',
    'name' => '',
    'age' => '',
    'sex' => '',
    'dob' => '',
    'enroll_date' => '',
    'current_dose' => '',
    'supporter_name' => '',
    'supporter_tel' => '',
    'drugname' => '' // New field for drug name
];

// --- 2. Fetch data from URL parameters and Database ---
if (isset($_GET['mat_id'])) {
    // $conn is assumed to be available from config.php
    $mat_id = $conn->real_escape_string($_GET['mat_id']);

    // Best practice: Use a prepared statement to fetch all details from the DB
    $sql = "SELECT clientName, age, sex, dob, reg_date, dosage, drugname, peer_edu_name, peer_edu_phone
            FROM patients
            WHERE mat_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $patient_data['mat_id'] = $mat_id;
        $patient_data['name'] = htmlspecialchars($row['clientName']);
        $patient_data['age'] = htmlspecialchars($row['age']);
        $patient_data['sex'] = htmlspecialchars($row['sex']);
        $patient_data['dob'] = htmlspecialchars($row['dob']);
        $patient_data['enroll_date'] = htmlspecialchars($row['reg_date']);
        $patient_data['current_dose'] = htmlspecialchars($row['dosage']);
        $patient_data['drugname'] = htmlspecialchars($row['drugname']);
        // Use null coalescing operator (??) for optional fields, though the DB query should handle it if columns exist
        $patient_data['supporter_name'] = htmlspecialchars($row['peer_edu_name'] ?? '');
        $patient_data['supporter_tel'] = htmlspecialchars($row['peer_edu_phone'] ?? '');

    } else {
        // Handle case where MAT ID is passed but no patient is found
        $patient_data['mat_id'] = $mat_id . " (Not Found)";
    }
    $stmt->close();
}

// Helper function to handle pre-selected sex
function is_selected($value, $current_sex) {
    // Standardize 'Male'/'Female' to match stored values
    $normalized_sex = strtolower(trim($current_sex));
    // Check for both 'Male'/'Female' strings and potential 1/2 numeric values
    $target = strtolower(trim($value));

    // Check for numeric values if the DB uses them (as per original function logic)
    if (is_numeric($value)) {
        $target = ($value == 1) ? 'male' : 'female';
    } else {
        $target = strtolower(trim($value));
    }

    return ($normalized_sex == $target) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Cessation Assessment Checklist (Form 2E)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }

        .container {
            min-width: 80%; /* Increased max-width for better form layout */
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }
        .form-header img {
            max-width: 80px;
            height: auto;
        }
        .form-header div {
            flex-grow: 1;
            text-align: center;
        }
        .form-header p {
            font-size: 0.8em;
            text-align: right;
            margin: 0;
            width: 150px;
        }

        h1, h2, h4 {
            text-align: center;
            color: #0056b3;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns for general info */
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-group {
            margin-bottom: 0; /* Adjusted for grid layout */
        }

        .info-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
            font-size: 0.9em;
        }

        .info-group input[type="text"],
        .info-group input[type="number"],
        .info-group input[type="date"],
        .info-group input[type="tel"],
        .info-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }
        .info-group input:read-only,
        .info-group select[disabled] {
            background-color: #e9e9e9;
            font-weight: 600; /* Bold the value for clear distinction */
            border-color: #bbb;
            color: #333;
        }

        hr {
            border: 0;
            height: 1px;
            background: #ccc;
            margin: 30px 0;
        }

        .instruction {
            font-style: italic;
            margin-bottom: 15px;
            background-color: #f0f8ff;
            padding: 10px;
            border-left: 4px solid #007bff;
        }

        .cessation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .cessation-table th, .cessation-table td {
            border: 1px solid #ddd;
            padding: 12px 10px;
            text-align: center;
            vertical-align: top;
        }

        .cessation-table th:first-child, .cessation-table td:first-child {
            width: 3%; /* # column */
            font-weight: bold;
        }
        .cessation-table th:nth-child(2), .cessation-table td:nth-child(2) {
            text-align: left;
            width: 70%; /* Question column */
        }
        .cessation-table th:nth-child(3), .cessation-table th:nth-child(4),
        .cessation-table td:nth-child(3), .cessation-table td:nth-child(4) {
            width: 13.5%; /* Yes/No columns */
        }

        .cessation-table thead th {
            background-color: #dbe4f0;
            font-weight: bold;
            color: #0056b3;
        }

        .cessation-table td input[type="radio"] {
            transform: scale(1.5);
            margin: 0;
            cursor: pointer;
        }

        .cessation-table tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #28a745; /* Green for submit */
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }

        button[type="submit"]:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" alt="Government Logo">
            <div><h4>MEDICALLY ASSISTED THERAPY ASSESSMENT CHECKLIST FOR CESSATION (FORM 2E)</h4></div>
            <p>FORM 2E VER. APR. 2022</p>
        </div>

        <form id="form2E" action="process_form2e.php" method="POST">

            <h2>General Information</h2>
            <div class="info-grid">
                <div class="info-group">
                    <label for="mat_id">MAT ID NO.:</label>
                    <input type="text" id="mat_id" name="mat_id" value="<?php echo $patient_data['mat_id']; ?>" readonly required>
                </div>
                <div class="info-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $patient_data['name']; ?>" readonly required>
                </div>
                <div class="info-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="0" value="<?php echo $patient_data['age']; ?>" readonly required>
                </div>
                <div class="info-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" value="<?php echo $patient_data['dob']; ?>" readonly>
                </div>
                <div class="info-group">
                    <label for="sex_display">Sex:</label>
                    <select id="sex_display" disabled>
                        <option value="">Select</option>
                        <option value="Male" <?php echo is_selected('Male', $patient_data['sex']); ?>>Male</option>
                        <option value="Female" <?php echo is_selected('Female', $patient_data['sex']); ?>>Female</option>
                    </select>
                    <input type="hidden" name="sex" value="<?php echo $patient_data['sex']; ?>">
                </div>
                <div class="info-group">
                    <label for="enroll_date">MAT Enrollment Date:</label>
                    <input type="date" id="enroll_date" name="enroll_date" value="<?php echo $patient_data['enroll_date']; ?>" readonly>
                </div>
                <div class="info-group">
                    <label for="drugname">Drug Name (MAT):</label>
                    <input type="text" id="drugname" name="drugname" value="<?php echo $patient_data['drugname']; ?>" readonly>
                </div>
                <div class="info-group">
                    <label for="current_dose">Current MAT Dose (mg):</label>
                    <input type="number" id="current_dose" name="current_dose" step="any" value="<?php echo $patient_data['current_dose']; ?>" readonly>
                </div>
                <div class="info-group">
                    <label for="supporter_name">Treatment Supporter's Name:</label>
                    <input type="text" id="supporter_name" name="supporter_name" value="<?php echo $patient_data['supporter_name']; ?>">
                </div>
                <div class="info-group">
                    <label for="supporter_tel">Telephone No.:</label>
                    <input type="tel" id="supporter_tel" name="supporter_tel" value="<?php echo $patient_data['supporter_tel']; ?>">
                </div>
                <div class="info-group"></div>
            </div>

            <hr>

            <h2>Cessation Readiness Questions</h2>
            <p class="instruction">Select the response for each question. **Yes = 1, No = 0**</p>

            <table class="cessation-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Yes (1)</th>
                        <th>No (0)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Have you been abstaining from drugs of addiction, such as heroin, cannabis, benzodiazepines, etc.?<br>
                            If yes, how long? <input type="number" name="q1_months" style="width: 80px;" min="0"> months (Confirm with Lab tracking form)
                        </td>
                        <td><input type="radio" name="q1" value="1" required></td>
                        <td><input type="radio" name="q1" value="0"></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Do you have a supportive family or non-drug using friends that you spend time with?</td>
                        <td><input type="radio" name="q2" value="1" required></td>
                        <td><input type="radio" name="q2" value="0"></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Do you have a stable living arrangement?</td>
                        <td><input type="radio" name="q3" value="1" required></td>
                        <td><input type="radio" name="q3" value="0"></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Is the client in good mental and physical health?</td>
                        <td><input type="radio" name="q4" value="1" required></td>
                        <td><input type="radio" name="q4" value="0"></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Has the client been on methadone at least for the past 12 months without interruption?</td>
                        <td><input type="radio" name="q5" value="1" required></td>
                        <td><input type="radio" name="q5" value="0"></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Do you really want to get off methadone, buprenorphine or naltrexone?</td>
                        <td><input type="radio" name="q6" value="1" required></td>
                        <td><input type="radio" name="q6" value="0"></td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Do you have a compelling reason to get off methadone, buprenorphine or naltrexone? (logistical constraints/Travel/Job/Ramadhan/Dissatisfaction/other)</td>
                        <td><input type="radio" name="q7" value="1" required></td>
                        <td><input type="radio" name="q7" value="0"></td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>Do you have a main motivation or reason for wanting to get off methadone, buprenorphine or naltrexone?<br>
                            If yes, what is it? <input type="text" name="q8_motivation" style="width: 70%;"> (Feels recovered/logistical constraints/Travel/Job/ Ramadhan/Dissatisfaction/other)
                        </td>
                        <td><input type="radio" name="q8" value="1" required></td>
                        <td><input type="radio" name="q8" value="0"></td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>Do you think you are able to cope with stressful situations without using drugs?</td>
                        <td><input type="radio" name="q9" value="1" required></td>
                        <td><input type="radio" name="q9" value="0"></td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>Are you staying away from former drug using friends?</td>
                        <td><input type="radio" name="q10" value="1" required></td>
                        <td><input type="radio" name="q10" value="0"></td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>Do you live in a neighborhood that is not close to drug using sites?</td>
                        <td><input type="radio" name="q11" value="1" required></td>
                        <td><input type="radio" name="q11" value="0"></td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>Have you stopped engaging in criminal behavior?</td>
                        <td><input type="radio" name="q12" value="1" required></td>
                        <td><input type="radio" name="q12" value="0"></td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>Do you have a stable source of income?</td>
                        <td><input type="radio" name="q13" value="1" required></td>
                        <td><input type="radio" name="q13" value="0"></td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>Have you been on the same methadone, buprenorphine or naltrexone dose for the past 3 months?</td>
                        <td><input type="radio" name="q14" value="1" required></td>
                        <td><input type="radio" name="q14" value="0"></td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>Have you been receiving psychosocial counseling regularly at a MAT clinic or DIC?</td>
                        <td><input type="radio" name="q15" value="1" required></td>
                        <td><input type="radio" name="q15" value="0"></td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>Does your counselor think you are ready to taper off methadone, buprenorphine or naltrexone?</td>
                        <td><input type="radio" name="q16" value="1" required></td>
                        <td><input type="radio" name="q16" value="0"></td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td>Have your urine drug screening results over the past 6 months been negative for heroin and other drugs? (Confirm with Lab tracking form)</td>
                        <td><input type="radio" name="q17" value="1" required></td>
                        <td><input type="radio" name="q17" value="0"></td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td>Do you have friends or family who would be helpful and supportive during weaning?</td>
                        <td><input type="radio" name="q18" value="1" required></td>
                        <td><input type="radio" name="q18" value="0"></td>
                    </tr>
                    <tr>
                        <td>19</td>
                        <td>Would you ask for help if you were unable to cope with the weaning process?<br>
                            If yes, whom would you first go to for help? <input type="text" name="q19_contact" style="width: 70%;"> (Clinician/counselor/spouse/sibling/friend/peer MAT client/ORW/other)
                        </td>
                        <td><input type="radio" name="q19" value="1" required></td>
                        <td><input type="radio" name="q19" value="0"></td>
                    </tr>
                </tbody>
            </table>

            <button type="submit">Calculate Overall Score</button>
        </form>
    </div>
    <script>
        // Function to validate form before submission
        function validateForm2E() {
            // Check if all 19 cessation questions are answered
            const questions = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9', 'q10', 'q11', 'q12', 'q13', 'q14', 'q15', 'q16', 'q17', 'q18', 'q19'];
            let allAnswered = true;

            for (let i = 0; i < questions.length; i++) {
                const radios = document.getElementsByName(questions[i]);
                let checked = false;

                for (let j = 0; j < radios.length; j++) {
                    if (radios[j].checked) {
                        checked = true;
                        break;
                    }
                }

                if (!checked) {
                    allAnswered = false;
                    const row = radios[0].closest('tr');
                    if (row) {
                        row.style.backgroundColor = '#ffe6e6';
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 3000);
                    }
                }
            }

            if (!allAnswered) {
                alert('Please answer all 19 Cessation Readiness Questions before submitting.');
                return false;
            }

            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form2E');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm2E()) {
                        e.preventDefault();
                        return false;
                    }
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if(submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = 'Processing...';
                    }
                });
            }
        });
    </script>
</body>
</html>