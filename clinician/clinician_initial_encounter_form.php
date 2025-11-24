<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$clinician_id = $_SESSION['user_id'];
$patient_id = $_GET['p_id'] ?? null;
$action = $_GET['action'] ?? 'start';
$triage_id = $_GET['triage_id'] ?? null;

if (!$patient_id) {
    header("Location: search_patient.php");
    exit();
}

// Fetch patient details
$patient_query = "SELECT * FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($patient_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found");
}

// Fetch facility details
$facility_query = "SELECT facilityname FROM facility_settings LIMIT 1";
$facility_result = mysqli_query($conn, $facility_query);
$facility = mysqli_fetch_assoc($facility_result);

// Fetch clinician details
$clinician_query = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($clinician_query);
$stmt->bind_param('i', $clinician_id);
$stmt->execute();
$clinician_result = $stmt->get_result();
$clinician = $clinician_result->fetch_assoc();
$clinician_name = $clinician['first_name'] . ' ' . $clinician['last_name'];
$stmt->close();

// Check for existing triage
$triage_data = null;
if ($action === 'continue' && $triage_id) {
    $triage_query = "SELECT * FROM triage_services WHERE id = ? AND patient_id = ? AND status = 'incomplete'";
    $stmt = $conn->prepare($triage_query);
    $stmt->bind_param('ii', $triage_id, $patient_id);
    $stmt->execute();
    $triage_result = $stmt->get_result();
    if ($triage_result->num_rows > 0) {
        $triage_data = $triage_result->fetch_assoc();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_triage'])) {
        // Save Part A (Triage) data
        $facility_name = mysqli_real_escape_string($conn, $_POST['facility_name'] ?? '');
        $mfl_code = mysqli_real_escape_string($conn, $_POST['mfl_code'] ?? '');
        $county = mysqli_real_escape_string($conn, $_POST['county'] ?? '');
        $sub_county = mysqli_real_escape_string($conn, $_POST['sub_county'] ?? '');
        $enrolment_date = !empty($_POST['enrolment_date']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['enrolment_date']))) : null;
        $enrolment_time = $_POST['enrolment_time'] ?? null;
        $visit_type = isset($_POST['visit_type']) ? implode(',', $_POST['visit_type']) : '';
        $client_name = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
        $nickname = mysqli_real_escape_string($conn, $_POST['nickname'] ?? '');
        $mat_id = mysqli_real_escape_string($conn, $_POST['mat_id'] ?? '');
        $sex = mysqli_real_escape_string($conn, $_POST['sex'] ?? '');
        $presenting_complaints = mysqli_real_escape_string($conn, $_POST['presenting_complaints'] ?? '');

        // Vital signs
        $pulse = !empty($_POST['pulse']) ? intval($_POST['pulse']) : null;
        $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? intval($_POST['oxygen_saturation']) : null;
        $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure'] ?? '');
        $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
        $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $bmi = !empty($_POST['bmi']) ? floatval($_POST['bmi']) : null;
        $bmi_interpretation = $_POST['bmi_interpretation'] ?? '';

        // COWS data
        $cows_provider = mysqli_real_escape_string($conn, $_POST['cows_provider'] ?? '');
        $cows_date = $_POST['cows_date'] ?? null;

        // Insert into triage_services
        $sql = "INSERT INTO triage_services (
            patient_id, clinician_id, facility_name, mfl_code, county, sub_county,
            enrolment_date, enrolment_time, visit_type, client_name, nickname, mat_id, sex, presenting_complaints,
            pulse, oxygen_saturation, blood_pressure, temperature, respiratory_rate, height, weight, bmi, bmi_interpretation,
            cows_provider, cows_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'incomplete')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'iissssssssssssiisdiiddsss',
            $patient_id, $clinician_id, $facility_name, $mfl_code, $county, $sub_county,
            $enrolment_date, $enrolment_time, $visit_type, $client_name, $nickname, $mat_id, $sex, $presenting_complaints,
            $pulse, $oxygen_saturation, $blood_pressure, $temperature, $respiratory_rate, $height, $weight, $bmi, $bmi_interpretation,
            $cows_provider, $cows_date
        );

        if ($stmt->execute()) {
            $triage_id = $stmt->insert_id;
            $stmt->close();

            // Redirect to continue with clinical assessment
            header("Location: clinician_initial_encounter_form.php?p_id=$patient_id&action=continue&triage_id=$triage_id");
            exit();
        } else {
            die("Error saving triage data: " . $stmt->error);
        }
    } elseif (isset($_POST['submit_complete'])) {
        // Save complete form
        $triage_id = $_POST['triage_id'];

        // Process clinical data (simplified for this example)
        $diagnosis_opioid_use = $_POST['diagnosis_opioid_use'] ?? '';
        $other_diagnoses = mysqli_real_escape_string($conn, $_POST['other_diagnoses'] ?? '');
        $treatment_plan = mysqli_real_escape_string($conn, $_POST['treatment_plan'] ?? '');
        $medication_prescribed = isset($_POST['medication_prescribed']) ? implode(',', $_POST['medication_prescribed']) : '';
        $initial_dose = mysqli_real_escape_string($conn, $_POST['initial_dose'] ?? '');
        $next_appointment = $_POST['next_appointment'] ?? null;
        $patient_consent = isset($_POST['patient_consent']) ? 'yes' : 'no';

        // Insert into clinical_encounters
        $sql = "INSERT INTO clinical_encounters (
            triage_id, patient_id, clinician_id, diagnosis_opioid_use, other_diagnoses,
            treatment_plan, medication_prescribed, initial_dose, next_appointment,
            clinician_name, clinician_signature, patient_consent, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'iiisssssssss',
            $triage_id, $patient_id, $clinician_id, $diagnosis_opioid_use, $other_diagnoses,
            $treatment_plan, $medication_prescribed, $initial_dose, $next_appointment,
            $clinician_name, $clinician_name, $patient_consent
        );

        if ($stmt->execute()) {
            $encounter_id = $stmt->insert_id;
            $stmt->close();

            // Update triage status to complete
            $update_sql = "UPDATE triage_services SET status = 'complete' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('i', $triage_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Redirect to success page
            header("Location: clinical_encounter_search.php?success=1");
            exit();
        } else {
            die("Error saving clinical encounter: " . $stmt->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Encounter Form</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: none; margin: 0; padding: 20px; line-height: 1.6; min-height: 100vh; }
        .form-container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 30px; background: #ffffff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border: 1px solid #e1e8ed; }
        .form-header { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 25px; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 3px solid #2c3e50; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; }
        .header-center { text-align: center; }
        .form-header h2 { color: #2c3e50; font-size: 28px; margin: 0; font-weight: 700; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .form-header h4 { color: #6633CC; font-size: 20px; margin: 8px 0; font-weight: 600; }
        .form-header p { color: #6c757d; font-size: 14px; text-align: right; margin: 0; font-weight: 500; }
        .form-section { background: #f8f9fa; padding: 25px; margin: 25px 0; border-radius: 12px; border-left: 5px solid #3498db; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); }
        .section-header { color: #2c3e50; font-size: 22px; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #3498db; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group { display: flex; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .form-group label { width: 280px; font-weight: 600; color: #2c3e50; font-size: 15px; margin-right: 20px; }
        .form-group input, .form-group select, .form-group textarea { flex: 1; min-width: 300px; padding: 12px 15px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: #ffffff; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); }
        .readonly-input, .read-only { cursor: not-allowed; background: #FFCCFF !important; border-color: #dda0dd !important; color: #4b0082; font-weight: 500; }
        .required-field::after { content: " *"; color: #e74c3c; font-weight: bold; }
        .submit-button { display: block; margin: 40px auto 0; padding: 15px 40px; background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; border: none; border-radius: 8px; font-size: 17px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .submit-button:hover { background: linear-gradient(135deg, #2980b9, #1f618d); transform: translateY(-2px); }
        .checkbox-group { display: flex; gap: 15px; flex-wrap: wrap; }
        .checkbox-group label { width: auto; display: flex; align-items: center; gap: 5px; }
        .checkbox-group input[type="checkbox"] { width: auto; min-width: auto; }
        .drug-history-table, .cows-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .drug-history-table th, .drug-history-table td, .cows-table th, .cows-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .drug-history-table th, .cows-table th { background-color: #f2f2f2; font-weight: bold; }

        /* Patient info header */
        .patient-info-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .patient-info-item {
            display: flex;
            flex-direction: column;
        }
        .patient-info-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .patient-info-value {
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>CLINICAL ENCOUNTER FORM</h4>
            </div>
            <p>VER.APRIL 2023 FORM 3A</p>
        </div>

        <!-- Patient Information -->
        <div class="patient-info-header">
            <h3 style="margin: 0 0 10px 0; color: white;">PATIENT INFORMATION</h3>
            <div class="patient-info-grid">
                <div class="patient-info-item">
                    <span class="patient-info-label">CLIENT NAME</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($patient['clientName'] . ' ' . $patient['sname']); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">MAT ID</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($patient['mat_id']); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">AGE</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($patient['age']); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">SEX</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($patient['sex']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($action === 'continue' && $triage_data): ?>
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 5px solid #27ae60; margin-bottom: 20px;">
                <strong>? Continuing Incomplete Form</strong> - Please complete the remaining sections below.
            </div>
        <?php endif; ?>

        <form method="POST" id="clinicalForm">
            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
            <input type="hidden" name="clinician_id" value="<?php echo $clinician_id; ?>">
            <input type="hidden" name="triage_id" value="<?php echo $triage_id; ?>">

            <?php if ($action === 'start' || !$triage_data): ?>
                <!-- PART A: FACILITY INFORMATION -->
                <div class="form-section">
                    <h3 class="section-header">Facility Information</h3>
                    <div class="form-group">
                        <label for="facility_name" class="required-field">Facility Name:</label>
                        <input type="text" name="facility_name" class="readonly-input" readonly value="<?php echo $facility ? htmlspecialchars($facility['facilityname']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="mfl_code" class="required-field">MFL Code:</label>
                        <input type="text" name="mfl_code" class="readonly-input" readonly value="<?php echo htmlspecialchars($patient['mflcode']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="county">County:</label>
                        <input type="text" name="county" class="read-only" readonly value="<?php echo htmlspecialchars($patient['county']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sub_county">Sub County:</label>
                        <input type="text" name="sub_county" class="read-only" readonly value="<?php echo htmlspecialchars($patient['scounty']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="enrolment_date" class="required-field">Enrolment Date:</label>
                        <input type="text" name="enrolment_date" class="readonly-input" readonly value="<?php echo date('d/m/Y'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="enrolment_time">Time:</label>
                        <input type="time" name="enrolment_time" value="<?php echo date('H:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="visit_type">Visit Type:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="visit_type[]" value="induction"> INDUCTION</label>
                            <label><input type="checkbox" name="visit_type[]" value="reinduction"> REINDUCTION</label>
                        </div>
                    </div>
                </div>

                <!-- PART A: CLIENT PROFILE -->
                <div class="form-section">
                    <h3 class="section-header">Client Profile</h3>
                    <div class="form-group">
                        <label for="client_name" class="required-field">Client Name:</label>
                        <input type="text" name="client_name" class="readonly-input" readonly value="<?php echo htmlspecialchars($patient['clientName'] . ' ' . $patient['sname']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="nickname">Nickname:</label>
                        <input type="text" name="nickname" value="<?php echo htmlspecialchars($patient['nickName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mat_id" class="required-field">MAT ID:</label>
                        <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo htmlspecialchars($patient['mat_id']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sex" class="required-field">Sex:</label>
                        <input type="text" name="sex" class="readonly-input" readonly value="<?php echo htmlspecialchars($patient['sex']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="presenting_complaints">Presenting Complaints:</label>
                        <textarea name="presenting_complaints" rows="3"></textarea>
                    </div>
                </div>

                <!-- PART A: VITAL SIGNS -->
                <div class="form-section">
                    <h3 class="section-header">Vital Signs</h3>
                    <div class="form-group">
                        <label for="pulse">Pulse:</label>
                        <input type="number" name="pulse" min="0" max="200"> bpm
                    </div>
                    <div class="form-group">
                        <label for="oxygen_saturation">Oxygen Saturation:</label>
                        <input type="number" name="oxygen_saturation" min="0" max="100"> %
                    </div>
                    <div class="form-group">
                        <label for="blood_pressure">Blood Pressure:</label>
                        <input type="text" name="blood_pressure" placeholder="e.g., 120/80"> mmHg
                    </div>
                    <div class="form-group">
                        <label for="temperature">Temperature:</label>
                        <input type="number" name="temperature" step="0.1" min="30" max="45"> °C
                    </div>
                    <div class="form-group">
                        <label for="respiratory_rate">Respiratory Rate:</label>
                        <input type="number" name="respiratory_rate" min="0" max="60"> breaths/min
                    </div>
                    <div class="form-group">
                        <label for="height">Height:</label>
                        <input type="number" name="height" step="0.1" min="0" max="250"> cm
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight:</label>
                        <input type="number" name="weight" step="0.1" min="0" max="300"> kg
                    </div>
                    <div class="form-group">
                        <label for="bmi">BMI:</label>
                        <input type="number" name="bmi" step="0.1" min="0" max="100" readonly>
                        <select name="bmi_interpretation">
                            <option value="">Select interpretation</option>
                            <option value="underweight">Underweight (<18.5)</option>
                            <option value="normal">Normal (18.5-24.9)</option>
                            <option value="overweight">Overweight (25-29.9)</option>
                            <option value="obesity">Obesity (>30)</option>
                        </select>
                    </div>
                </div>

                <!-- CLINICAL OPIATE WITHDRAWAL SCALE (COWS) -->
                <div class="form-section clinical-section" id="section-cows">
                    <h3 class="section-header" style="color: #9b59b6;">CLINICAL OPIATE WITHDRAWAL SCALE (COWS)</h3>

                    <table class="cows-table">
                        <thead>
                            <tr>
                                <th>Symptom</th>
                                <th>Score</th>
                                <th>Time 1</th>
                                <th>Time 2</th>
                                <th>Time 3</th>
                                <th>Time 4</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cows_symptoms = array(
                                'Resting Pulse Rate' => array(
                                    '0' => 'pulse rate 80 or below',
                                    '1' => 'pulse rate 81-100',
                                    '2' => 'pulse rate 101-120',
                                    '4' => 'pulse rate greater than 120'
                                ),
                                'Sweating' => array(
                                    '1' => 'no report of chills or flushing',
                                    '2' => 'subjective report of chills or flushing',
                                    '3' => 'flushed or observable moistness on face',
                                    '4' => 'beads of sweat on brow or face',
                                    '5' => 'sweat streaming off face'
                                ),
                                'Restlessness' => array(
                                    '0' => 'able to sit still',
                                    '1' => 'reports difficulty sitting still, but is able to do so',
                                    '3' => 'frequent shifting or extraneous movements of legs/arms',
                                    '5' => 'Unable to sit still for more than a few seconds'
                                ),
                                'Pupil size' => array(
                                    '0' => 'pupils pinned or normal size for room light',
                                    '1' => 'pupils possibly larger than normal for room light',
                                    '2' => 'pupils moderately dilated',
                                    '5' => 'pupils so dilated that only the rim of the iris is visible'
                                ),
                                'Bone or Joint aches' => array(
                                    '0' => 'not present',
                                    '1' => 'mild diffuse discomfort',
                                    '2' => 'patient reports severe diffuse aching of joints/muscles',
                                    '4' => 'patient is rubbing joints or muscles and is unable to sit still because of discomfort'
                                ),
                                'Runny nose or tearing' => array(
                                    '0' => 'not present',
                                    '1' => 'nasal stuffiness or unusually moist eyes',
                                    '2' => 'nose running or tearing',
                                    '4' => 'nose constantly running or tears streaming down cheeks'
                                ),
                                'GI Upset' => array(
                                    '0' => 'no GI symptoms',
                                    '1' => 'stomach cramps',
                                    '2' => 'nausea or loose stool',
                                    '3' => 'vomiting or diarrhea',
                                    '5' => 'Multiple episodes of diarrhea or vomiting'
                                ),
                                'Tremor' => array(
                                    '0' => 'No tremor',
                                    '1' => 'tremor can be felt, but not observed',
                                    '2' => 'slight tremor observable',
                                    '4' => 'gross tremor or muscle twitching'
                                ),
                                'Yawning' => array(
                                    '0' => 'no yawning',
                                    '1' => 'yawning once or twice during assessment',
                                    '2' => 'yawning three or more times during assessment',
                                    '4' => 'yawning several times/minute'
                                ),
                                'Anxiety or Irritability' => array(
                                    '0' => 'none',
                                    '1' => 'patient reports increasing irritability or anxiousness',
                                    '2' => 'patient obviously irritable anxious',
                                    '4' => 'patient so irritable or anxious that participation in the assessment is difficult'
                                ),
                                'Gooseflesh skin' => array(
                                    '0' => 'skin is smooth',
                                    '3' => 'piloerrection of skin can be felt or hairs standing up on arms',
                                    '5' => 'prominent piloerrection'
                                )
                            );

                            foreach($cows_symptoms as $symptom => $scores) {
                                echo "<tr>";
                                echo "<td class='symptom'>$symptom</td>";
                                echo "<td>";
                                foreach($scores as $score => $description) {
                                    echo "<div>$score: $description</div>";
                                }
                                echo "</td>";
                                for($i = 1; $i <= 4; $i++) {
                                    echo "<td><input type='number' name='cows_{$symptom}_time{$i}' min='0' max='5' style='width: 50px;'></td>";
                                }
                                echo "</tr>";
                            }
                            ?>
                            <tr>
                                <td colspan="2"><strong>Total scores</strong></td>
                                <?php for($i = 1; $i <= 4; $i++): ?>
                                <td><input type="number" name="cows_total_time<?php echo $i; ?>" readonly style="width: 50px; background-color: #f0f0f0;"></td>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <td colspan="2"><strong>Scale Interpretation</strong></td>
                                <?php for($i = 1; $i <= 4; $i++): ?>
                                <td>
                                    <select name="cows_interpretation_time<?php echo $i; ?>" style="width: 100%;">
                                        <option value="">Select</option>
                                        <option value="mild">Mild (5-12)</option>
                                        <option value="moderate">Moderate (13-24)</option>
                                        <option value="moderately_severe">Moderately Severe (25-36)</option>
                                        <option value="severe">Severe (>36)</option>
                                    </select>
                                </td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>

                    <div class="form-group">
                        <label for="cows_provider">Name of Service Provider:</label>
                        <input type="text" name="cows_provider" value="<?php echo htmlspecialchars($clinician_name); ?>" class="read-only" readonly>
                    </div>

                    <div class="form-group">
                        <label for="cows_date">Date:</label>
                        <input type="date" name="cows_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <button type="submit" name="submit_triage" class="submit-button">
                            Save Part A & Continue to Clinical Assessment
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <!-- PART B: CLINICAL ASSESSMENT -->
            <div class="form-section clinical-section">
                <h3 class="section-header" style="color: #9b59b6;">PART B: CLINICAL ASSESSMENT</h3>

                <!-- PERSONAL MEDICAL HISTORY -->
                <h4>10. PERSONAL MEDICAL HISTORY</h4>
                <table class="drug-history-table">
                    <thead>
                        <tr>
                            <th>Have you ever been diagnosed with any of the following illnesses?</th>
                            <th>Yes/No</th>
                            <th>If so, current medication and dose for illness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $medical_conditions = array(
                            'a' => 'Asthma',
                            'b' => 'Heart disease',
                            'c' => 'Tuberculosis',
                            'd' => 'Liver disease',
                            'e' => 'STDs (syphilis, chlamydia, gonorrhoea, etc.)',
                            'f' => 'Accidents or surgery',
                            'g' => 'HIV',
                            'h' => 'Hypertension',
                            'i' => 'Hepatitis B',
                            'j' => 'Hepatitis C',
                            'k' => 'Diabetes'
                        );

                        foreach($medical_conditions as $key => $condition) {
                            echo "<tr>";
                            echo "<td>$condition</td>";
                            echo "<td>
                                <div class='checkbox-group'>
                                    <label><input type='radio' name='medical_history[$key]' value='yes'> Yes</label>
                                    <label><input type='radio' name='medical_history[$key]' value='no'> No</label>
                                </div>
                            </td>";
                            echo "<td><input type='text' name='medical_medication[$key]'></td>";
                            echo "</tr>";
                        }
                        ?>
                        <tr>
                            <td>HIV Date of Diagnosis:</td>
                            <td colspan="2"><input type="date" name="hiv_diagnosis_date"></td>
                        </tr>
                        <tr>
                            <td>HIV Facility of Care:</td>
                            <td colspan="2"><input type="text" name="hiv_facility_care"></td>
                        </tr>
                        <tr>
                            <td>m. Any other medical problems or medications:</td>
                            <td colspan="2"><textarea name="other_medical_problems" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td>n. Do you have any allergies?</td>
                            <td colspan="2">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="allergies[]" value="sulphur"> Sulphur</label>
                                    <label><input type="checkbox" name="allergies[]" value="penicillin"> Penicillin</label>
                                    <label><input type="checkbox" name="allergies[]" value="none"> None</label>
                                    <label><input type="checkbox" name="allergies[]" value="other"> Other (Specify): <input type="text" name="allergies_other"></label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- REPRODUCTIVE HEALTH HISTORY -->
                <h4>11. REPRODUCTIVE HEALTH HISTORY</h4>

                <div class="form-group">
                    <label for="contraception_use">a. Are you using any contraception?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="contraception_use" value="yes"> Yes</label>
                        <label><input type="radio" name="contraception_use" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contraception_method">b. If YES, which method are you using? (Mark all responses mentioned)</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $contraception_methods = array(
                            'male_condom' => 'Male condom',
                            'female_condom' => 'Female condom',
                            'injectables' => 'Injectables',
                            'iud' => 'IUD',
                            'implants' => 'Implants',
                            'lactational_amenorrhea' => 'Lactational Amenorrhea',
                            'foam_jelly' => 'Foam/jelly',
                            'withdrawal' => 'Withdrawal',
                            'rhythm_method' => 'Rhythm method',
                            'abstain' => 'Abstain',
                            'emergency_contraception' => 'Emergency contraception',
                            'female_sterilization' => 'Female sterilization',
                            'male_sterilization' => 'Male sterilization',
                            'pill' => 'Pill',
                            'none' => 'None'
                        );

                        foreach($contraception_methods as $key => $method) {
                            echo "<label><input type='checkbox' name='contraception_method[]' value='$key'> $method</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_menstrual_period">c. Date of Last Menstrual Period (LMP):</label>
                    <input type="date" name="last_menstrual_period">
                </div>

                <div class="form-group">
                    <label for="pregnancy_status">d. Pregnancy status:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="pregnancy_status" value="pregnant"> Pregnant</label>
                        <label><input type="radio" name="pregnancy_status" value="not_pregnant"> Not Pregnant</label>
                        <label><input type="radio" name="pregnancy_status" value="unknown"> Unknown</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pregnancy_weeks">e. If pregnant, how many weeks?</label>
                    <input type="number" name="pregnancy_weeks" min="0" max="50"> weeks
                </div>

                <div class="form-group">
                    <label for="breastfeeding">f. Are you breastfeeding?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="breastfeeding" value="yes"> Yes</label>
                        <label><input type="radio" name="breastfeeding" value="no"> No</label>
                    </div>
                </div>

                <!-- MENTAL HEALTH HISTORY -->
                <h4>12. MENTAL HEALTH HISTORY</h4>

                <div class="form-group">
                    <label for="mental_health_diagnosis">a. Have you ever been diagnosed with a mental health condition?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="mental_health_diagnosis" value="yes"> Yes</label>
                        <label><input type="radio" name="mental_health_diagnosis" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_condition">b. If YES, which condition?</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $mental_health_conditions = array(
                            'depression' => 'Depression',
                            'anxiety' => 'Anxiety',
                            'bipolar' => 'Bipolar',
                            'schizophrenia' => 'Schizophrenia',
                            'ptsd' => 'PTSD',
                            'other' => 'Other (Specify): <input type="text" name="mental_health_other">'
                        );

                        foreach($mental_health_conditions as $key => $condition) {
                            echo "<label><input type='checkbox' name='mental_health_condition[]' value='$key'> $condition</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_medication">c. Are you currently taking any medication for mental health?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="mental_health_medication" value="yes"> Yes</label>
                        <label><input type="radio" name="mental_health_medication" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_medication_details">d. If YES, please specify medication and dose:</label>
                    <textarea name="mental_health_medication_details" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="suicidal_thoughts">e. Have you had any thoughts of harming yourself or ending your life in the past month?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="suicidal_thoughts" value="yes"> Yes</label>
                        <label><input type="radio" name="suicidal_thoughts" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="psychiatric_hospitalization">f. Have you ever been hospitalized for psychiatric reasons?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="psychiatric_hospitalization" value="yes"> Yes</label>
                        <label><input type="radio" name="psychiatric_hospitalization" value="no"> No</label>
                    </div>
                </div>

                <!-- FAMILY HISTORY -->
                <h4>13. FAMILY HISTORY</h4>

                <div class="form-group">
                    <label for="family_drug_use">a. Is there any history of drug use in your family?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="family_drug_use" value="yes"> Yes</label>
                        <label><input type="radio" name="family_drug_use" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="family_mental_health">b. Is there any history of mental health conditions in your family?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="family_mental_health" value="yes"> Yes</label>
                        <label><input type="radio" name="family_mental_health" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="family_medical_conditions">c. Is there any history of the following medical conditions in your family?</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $family_medical_conditions = array(
                            'diabetes' => 'Diabetes',
                            'hypertension' => 'Hypertension',
                            'heart_disease' => 'Heart Disease',
                            'cancer' => 'Cancer',
                            'other' => 'Other (Specify): <input type="text" name="family_medical_other">'
                        );

                        foreach($family_medical_conditions as $key => $condition) {
                            echo "<label><input type='checkbox' name='family_medical_conditions[]' value='$key'> $condition</label>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- PHYSICAL EXAMINATION SECTION -->
            <div class="form-section examination-section">
                <h3 class="section-header" style="color: #c0392b;">14. PHYSICAL EXAMINATION</h3>

                <div class="form-group">
                    <label for="general_appearance">a. General Appearance:</label>
                    <textarea name="general_appearance" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="skin_examination">b. Skin:</label>
                    <textarea name="skin_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="head_examination">c. Head:</label>
                    <textarea name="head_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="eyes_examination">d. Eyes:</label>
                    <textarea name="eyes_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="ears_examination">e. Ears:</label>
                    <textarea name="ears_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="nose_examination">f. Nose:</label>
                    <textarea name="nose_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="mouth_throat_examination">g. Mouth and Throat:</label>
                    <textarea name="mouth_throat_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="neck_examination">h. Neck:</label>
                    <textarea name="neck_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="chest_examination">i. Chest:</label>
                    <textarea name="chest_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="heart_examination">j. Heart:</label>
                    <textarea name="heart_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="abdomen_examination">k. Abdomen:</label>
                    <textarea name="abdomen_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="genitalia_examination">l. Genitalia:</label>
                    <textarea name="genitalia_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="extremities_examination">m. Extremities:</label>
                    <textarea name="extremities_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="neurological_examination">n. Neurological:</label>
                    <textarea name="neurological_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="musculoskeletal_examination">o. Musculoskeletal:</label>
                    <textarea name="musculoskeletal_examination" rows="3"></textarea>
                </div>
            </div>

            <!-- DIAGNOSIS AND TREATMENT PLAN -->
            <div class="form-section treatment-section">
                <h3 class="section-header" style="color: #43a047;">15. DIAGNOSIS AND TREATMENT PLAN</h3>

                <div class="form-group">
                    <label for="diagnosis_opioid_use">a. Diagnosis of Opioid Use Disorder:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="diagnosis_opioid_use" value="mild"> Mild</label>
                        <label><input type="radio" name="diagnosis_opioid_use" value="moderate"> Moderate</label>
                        <label><input type="radio" name="diagnosis_opioid_use" value="severe"> Severe</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="other_diagnoses">b. Other Diagnoses:</label>
                    <textarea name="other_diagnoses" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="treatment_plan">c. Treatment Plan:</label>
                    <textarea name="treatment_plan" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="medication_prescribed">d. Medication Prescribed:</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $medications = array(
                            'methadone' => 'Methadone',
                            'buprenorphine' => 'Buprenorphine',
                            'naltrexone' => 'Naltrexone',
                            'other' => 'Other (Specify): <input type="text" name="medication_other">'
                        );

                        foreach($medications as $key => $medication) {
                            echo "<label><input type='checkbox' name='medication_prescribed[]' value='$key'> $medication</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="initial_dose">e. Initial Dose:</label>
                    <input type="text" name="initial_dose">
                </div>

                <div class="form-group">
                    <label for="next_appointment" class="required-field">f. Next Appointment Date:</label>
                    <input type="date" name="next_appointment" required>
                </div>

                <div class="form-group">
                    <label for="clinician_name">g. Clinician Name:</label>
                    <input type="text" name="clinician_name" value="<?php echo htmlspecialchars($clinician_name); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="clinician_signature">h. Clinician Signature:</label>
                    <input type="text" name="clinician_signature" value="<?php echo htmlspecialchars($clinician_name); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="patient_consent">i. Patient Consent for Treatment:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="patient_consent" value="yes" required> I have read and understood the information provided and consent to treatment</label>
                    </div>
                </div>

            <button type="submit" class="submit-button">
                Submit Initial Clinical Encounter Form
            </button>

            <div class="section-navigation">
                    <div class="section-status" id="treatment-plan-status">
                        Status: <span class="status-unsaved">Unsaved</span>
                    </div>
                    <button type="submit" class="submit-button" id="final-submit">
                        Submit Initial Clinical Encounter Form
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // BMI calculation
            const heightField = document.querySelector('input[name="height"]');
            const weightField = document.querySelector('input[name="weight"]');
            const bmiField = document.querySelector('input[name="bmi"]');

            function calculateBMI() {
                if (heightField && heightField.value && weightField && weightField.value && bmiField) {
                    const heightInMeters = heightField.value / 100;
                    const bmi = (weightField.value / (heightInMeters * heightInMeters)).toFixed(1);
                    bmiField.value = bmi;
                }
            }

            if (heightField && weightField && bmiField) {
                heightField.addEventListener('input', calculateBMI);
                weightField.addEventListener('input', calculateBMI);
            }

            // Set minimum date for next appointment
            const today = new Date().toISOString().split('T')[0];
            const appointmentField = document.querySelector('input[name="next_appointment"]');
            if (appointmentField) {
                appointmentField.min = today;
            }
        });
    </script>
</body>
</html>