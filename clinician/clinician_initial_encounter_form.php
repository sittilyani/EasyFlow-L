<?php
session_start();
include "../includes/config.php";

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// NEW CODE: Load draft data if available
$draft_data = [];
$current_section = 'facility';
$draft_id = null;
$encounter_id = null;
$patient_id = null;

// Get patient ID from URL if available
if (isset($_GET['p_id'])) {
    $patient_id = intval($_GET['p_id']);
}

// Load draft data if draft_id is provided
if (isset($_GET['draft_id'])) {
    $draft_id = intval($_GET['draft_id']);
    $draft_sql = "SELECT * FROM clinical_encounter_drafts WHERE id = ? AND clinician_id = ?";
    $draft_stmt = $conn->prepare($draft_sql);
    $draft_stmt->bind_param('ii', $draft_id, $loggedInUserId);
    $draft_stmt->execute();
    $draft_result = $draft_stmt->get_result();

    if ($draft_result->num_rows > 0) {
        $draft_row = $draft_result->fetch_assoc();
        $draft_data = json_decode($draft_row['form_data'], true);
        $current_section = $draft_row['current_section'];
        $patient_id = $draft_row['patient_id']; // Get patient_id from draft
    }
    $draft_stmt->close();
}

// Load from encounter drafts
if (isset($_GET['encounter_id'])) {
    $encounter_id = intval($_GET['encounter_id']);
    $encounter_sql = "SELECT * FROM clinical_encounters WHERE id = ? AND status = 'draft'";
    $encounter_stmt = $conn->prepare($encounter_sql);
    $encounter_stmt->bind_param('i', $encounter_id);
    $encounter_stmt->execute();
    $encounter_result = $encounter_stmt->get_result();

    if ($encounter_result->num_rows > 0) {
        $encounter_row = $encounter_result->fetch_assoc();
        // Convert encounter data to form data format
        $draft_data = convertEncounterToFormData($encounter_row);
        $current_section = $encounter_row['current_section'];
        $patient_id = $encounter_row['patient_id'];
    }
    $encounter_stmt->close();
}

// Function to convert encounter data to form data format
function convertEncounterToFormData($encounter_row) {
    $form_data = [];
    // Map database fields to form field names
    $form_data['facility_name'] = $encounter_row['facility_name'] ?? '';
    $form_data['mfl_code'] = $encounter_row['mfl_code'] ?? '';
    $form_data['county'] = $encounter_row['county'] ?? '';
    $form_data['sub_county'] = $encounter_row['sub_county'] ?? '';
    $form_data['enrolment_date'] = $encounter_row['enrolment_date'] ?? '';
    $form_data['enrolment_time'] = $encounter_row['enrolment_time'] ?? '';
    // ... add all other field mappings ...
    return $form_data;
}

// Fetch patient details if patient_id is available
$currentSettings = [];
if ($patient_id) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

// If no patient_id but we have mat_id in GET (for new forms), find patient by mat_id
if (!$patient_id && isset($_GET['mat_id']) && !empty($_GET['mat_id'])) {
    $mat_id = $_GET['mat_id'];
    $query = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $currentSettings = $result->fetch_assoc();
        $patient_id = $currentSettings['p_id'];
    }
}

// Fetch facility name from facility settings table
$facility_query = "SELECT facility_id, facilityname FROM facility_settings LIMIT 1";
$facility_result = mysqli_query($conn, $facility_query);
$facility = mysqli_fetch_assoc($facility_result);

// Fetch the logged-in user's name from tblusers
$clinician_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $clinician_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Check for save success message
$save_message = '';
if (isset($_GET['save_success'])) {
    $save_message = $_GET['save_success'];
}

// Check if we have patient data to proceed
if (!$patient_id && !isset($_GET['search_patient'])) {
    // Redirect to patient search if no patient is selected
    header("Location: search_patient.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Clinic Initial Encounter Form</title>
    <style>
        /* Your existing CSS styles remain the same */
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; line-height: 1.6; min-height: 100vh; }
        .form-container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 30px; background: #ffffff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border: 1px solid #e1e8ed; }
        .form-header { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 25px; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 3px solid #2c3e50; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; }
        .header-center { text-align: center; }
        .form-header h2 { color: #2c3e50; font-size: 28px; margin: 0; font-weight: 700; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .form-header h4 { color: #6633CC; font-size: 20px; margin: 8px 0; font-weight: 600; }
        .form-header p { color: #6c757d; font-size: 14px; text-align: right; margin: 0; font-weight: 500; }
        .form-section { background: #f8f9fa; padding: 25px; margin: 25px 0; border-radius: 12px; border-left: 5px solid #3498db; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .form-section:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12); }
        .section-header { color: #2c3e50; font-size: 22px; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #3498db; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group { display: flex; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .form-group label { width: 280px; font-weight: 600; color: #2c3e50; font-size: 15px; margin-right: 20px; }
        .form-group input, .form-group select, .form-group textarea { flex: 1; min-width: 300px; padding: 12px 15px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: #ffffff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); transform: translateY(-1px); }
        .form-group select { background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); cursor: pointer; }
        .readonly-input, .read-only { cursor: not-allowed; background: #FFCCFF !important; border-color: #dda0dd !important; color: #4b0082; font-weight: 500; }
        .required-field::after { content: " *"; color: #e74c3c; font-weight: bold; }
        .submit-button { display: block; margin: 40px auto 0; padding: 15px 40px; background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; border: none; border-radius: 8px; font-size: 17px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3); text-transform: uppercase; letter-spacing: 0.5px; }
        .submit-button:hover { background: linear-gradient(135deg, #2980b9, #1f618d); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4); }
        .drug-history-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .drug-history-table th, .drug-history-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .drug-history-table th { background-color: #f2f2f2; font-weight: bold; }
        .checkbox-group { display: flex; gap: 15px; flex-wrap: wrap; }
        .checkbox-group label { width: auto; display: flex; align-items: center; gap: 5px; }
        .checkbox-group input[type="checkbox"] { width: auto; min-width: auto; }
        .cows-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .cows-table th, .cows-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .cows-table th { background-color: #f2f2f2; font-weight: bold; }
        .cows-table .symptom { text-align: left; }
        .profile-section { background: linear-gradient(135deg, #e8f5e8 0%, #f0f8ff 100%); border-left: 5px solid #27ae60; }
        .clinical-section { background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border-left: 5px solid #9b59b6; }
        .history-section { background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%); border-left: 5px solid #e67e22; }
        .examination-section { background: linear-gradient(135deg, #ffebee 0%, #f3e5f5 100%); border-left: 5px solid #c0392b; }
        .treatment-section { background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-left: 5px solid #43a047; }

        /* Section navigation styles */
        .section-navigation {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e1e8ed;
        }

        .save-proceed-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #27ae60, #219a52);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .save-proceed-btn:hover {
            background: linear-gradient(135deg, #219a52, #1e8449);
            transform: translateY(-2px);
        }
        .save-proceed-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        .section-status {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
            font-weight: 600;
        }
        .status-saved {
            color: #27ae60;
        }
        .status-unsaved {
            color: #e74c3c;
        }

        /* Success message styles */
        .save-success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid #28a745;
            margin-bottom: 20px;
            font-weight: 600;
        }

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

        @media (max-width: 768px) {
            .form-container { width: 95%; padding: 20px; margin: 10px auto; }
            .form-header { grid-template-columns: 1fr; text-align: center; gap: 15px; }
            .form-header p { text-align: center; }
            .form-group { flex-direction: column; align-items: flex-start; }
            .form-group label { width: 100%; margin-bottom: 8px; }
            .form-group input, .form-group select, .form-group textarea { width: 100%; min-width: unset; }
            .drug-history-table, .cows-table { font-size: 12px; }
            .patient-info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo" style="filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.1));">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>CLINICAL ENCOUNTER FORM</h4>
            </div>
            <p>VER.APRIL 2023 FORM 3A</p>
        </div>

        <!-- Patient Information Header -->
        <?php if ($currentSettings): ?>
        <div class="patient-info-header">
            <h3 style="margin: 0 0 10px 0; color: white;">PATIENT INFORMATION</h3>
            <div class="patient-info-grid">
                <div class="patient-info-item">
                    <span class="patient-info-label">CLIENT NAME</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($currentSettings['clientName'] . ' ' . $currentSettings['sname']); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">MAT ID</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($currentSettings['mat_id']); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">AGE</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($currentSettings['age'] ?? 'N/A'); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">SEX</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($currentSettings['sex'] ?? 'N/A'); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">PHONE</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($currentSettings['client_phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">REGISTRATION DATE</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars($currentSettings['reg_date'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($draft_data)): ?>
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 5px solid #27ae60; margin-bottom: 20px;">
                <strong>? Draft Loaded</strong> - Continuing from <strong><?php echo ucfirst(str_replace('-', ' ', $current_section)); ?></strong> section.
                Your previous progress has been restored.
            </div>
        <?php endif; ?>

        <?php if (!empty($save_message)): ?>
            <div class="save-success-message">
                ? <?php echo htmlspecialchars($save_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$currentSettings): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                <h3>Patient Not Found</h3>
                <p>No patient record found. Please ensure you have selected a valid patient.</p>
                <a href="search_patient.php" style="color: #004085; text-decoration: underline;">Search for Patient</a>
            </div>
        <?php else: ?>

        <form action="submit_form3a.php" method="POST" id="clinicalForm">
            <!-- Hidden fields for save functionality -->
            <input type="hidden" name="partial_save" id="partial_save" value="false">
            <input type="hidden" name="current_section" id="current_section" value="<?php echo $current_section; ?>">
            <input type="hidden" name="draft_id" id="draft_id" value="<?php echo $draft_id; ?>">
            <input type="hidden" name="encounter_id" id="encounter_id" value="<?php echo $encounter_id; ?>">
            <input type="hidden" name="p_id" value="<?php echo $patient_id; ?>">

            <!-- FACILITY INFORMATION SECTION -->
            <div class="form-section profile-section" id="section-facility">
                <h3 class="section-header" style="color: #27ae60;">Facility Information</h3>

                <div class="form-group">
                    <label for="facility_name" class="required-field">Facility Name:</label>
                    <input type="text" name="facility_name" class="readonly-input" readonly value="<?php echo $facility ? htmlspecialchars($facility['facilityname']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="mfl_code" class="required-field">MFL Code:</label>
                    <input type="text" name="mfl_code" class="readonly-input" readonly value="<?php echo $currentSettings ? htmlspecialchars($currentSettings['mflcode']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="county">County:</label>
                    <input type="text" name="county" class="read-only" readonly value="<?php echo isset($currentSettings['county']) ? htmlspecialchars($currentSettings['county']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="sub_county">Sub County:</label>
                    <input type="text" name="sub_county" class="read-only" readonly value="<?php echo isset($currentSettings['scounty']) ? htmlspecialchars($currentSettings['scounty']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="enrolment_date" class="required-field">Enrolment Date (dd/mm/yyyy):</label>
                    <input type="text" name="enrolment_date" class="readonly-input" readonly value="<?php echo isset($currentSettings['reg_date']) ? htmlspecialchars($currentSettings['reg_date']) : date('d/m/Y'); ?>">
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

                <div class="section-navigation">
                    <div class="section-status" id="facility-status">
                        Status: <span class="status-<?php echo ($current_section == 'facility' && empty($draft_data)) ? 'unsaved' : 'saved'; ?>">
                            <?php echo ($current_section == 'facility' && empty($draft_data)) ? 'Unsaved' : 'Saved'; ?>
                        </span>
                    </div>
                    <button type="button" class="save-proceed-btn" data-section="facility" data-next="client-profile">
                        Save & Proceed to Client Profile
                    </button>
                </div>
            </div>

            <!-- PART A: CLIENT PROFILE SECTION -->
            <div class="form-section profile-section" id="section-client-profile">
                <h3 class="section-header" style="color: #27ae60;">PART A: CLIENT PROFILE</h3>

                <div class="form-group">
                    <label for="client_name" class="required-field">Client Name:</label>
                    <input type="text" id="client_name" name="client_name" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName'] . ' ' . $currentSettings['sname']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="nickname">Nickname:</label>
                    <input type="text" id="nickname" name="nickname" value="<?php echo isset($currentSettings['nickName']) ? htmlspecialchars($currentSettings['nickName']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="mat_id" class="required-field">MAT/Unique ID Number:</label>
                    <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="sex" class="required-field">Sex:</label>
                    <input type="text" id="sex" name="sex" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="text" id="age" name="age" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="text" id="dob" name="dob" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['dob']) ? htmlspecialchars($currentSettings['dob']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="presenting_complaints">Presenting Complaints:</label>
                    <textarea name="presenting_complaints" id="presenting_complaints" cols="30" rows="3"></textarea>
                </div>

                <!-- Continue with the rest of your form sections... -->

                <div class="section-navigation">
                    <div class="section-status" id="client-profile-status">
                        Status: <span class="status-<?php echo ($current_section == 'client-profile') ? 'saved' : 'unsaved'; ?>">
                            <?php echo ($current_section == 'client-profile') ? 'Saved' : 'Unsaved'; ?>
                        </span>
                    </div>
                    <button type="button" class="save-proceed-btn" data-section="client-profile" data-next="vital-signs">
                        Save & Proceed to Vital Signs
                    </button>
                </div>
            </div>

            <!-- VITAL SIGNS SECTION -->
            <div class="form-section clinical-section" id="section-vital-signs">
                <h3 class="section-header" style="color: #9b59b6;">VITAL SIGNS</h3>

                <div class="form-group">
                    <label for="pulse">a. Pulse:</label>
                    <input type="number" name="pulse" min="0" max="200"> bpm
                </div>

                <div class="form-group">
                    <label for="oxygen_saturation">b. Oxygen saturation:</label>
                    <input type="number" name="oxygen_saturation" min="0" max="100"> %
                </div>

                <div class="form-group">
                    <label for="blood_pressure">c. Blood pressure:</label>
                    <input type="text" name="blood_pressure" placeholder="e.g., 120/80"> mmHg
                </div>

                <div class="form-group">
                    <label for="temperature">d. Temperature:</label>
                    <input type="number" name="temperature" step="0.1" min="30" max="45"> °C
                </div>

                <div class="form-group">
                    <label for="respiratory_rate">e. Respiratory rate:</label>
                    <input type="number" name="respiratory_rate" min="0" max="60"> breaths/min
                </div>

                <div class="form-group">
                    <label for="height">f. Height:</label>
                    <input type="number" name="height" step="0.1" min="0" max="250"> cm
                </div>

                <div class="form-group">
                    <label for="weight">g. Weight:</label>
                    <input type="number" name="weight" step="0.1" min="0" max="300"> kg
                </div>

                <div class="form-group">
                    <label for="bmi">h. BMI:</label>
                    <input type="number" name="bmi" step="0.1" min="0" max="100">
                    <select name="bmi_interpretation">
                        <option value="">Select interpretation</option>
                        <option value="underweight">Underweight (<18.5)</option>
                        <option value="normal">Normal (18.5-24.9)</option>
                        <option value="overweight">Overweight (25-29.9)</option>
                        <option value="obesity">Obesity (>30)</option>
                    </select>
                </div>

                <div class="section-navigation">
                    <div class="section-status" id="vital-signs-status">
                        Status: <span class="status-unsaved">Unsaved</span>
                    </div>
                    <button type="button" class="save-proceed-btn" data-section="vital-signs" data-next="cows">
                        Save & Proceed to COWS
                    </button>
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

            <div class="section-navigation">
                    <div class="section-status" id="cows-status">
                        Status: <span class="status-unsaved">Unsaved</span>
                    </div>
                    <button type="button" class="save-proceed-btn" data-section="cows" data-next="clinical-assessment">
                        Save & Proceed to Clinical Assessment
                    </button>
                </div>
            </div>

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

                <div class="section-navigation">
                        <div class="section-status" id="clinical-assessment-status">
                            Status: <span class="status-unsaved">Unsaved</span>
                        </div>
                        <button type="button" class="save-proceed-btn" data-section="clinical-assessment" data-next="physical-examination">
                            Save & Proceed to Physical Examination
                        </button>
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

            <div class="section-navigation">
                    <div class="section-status" id="physical-examination-status">
                        Status: <span class="status-unsaved">Unsaved</span>
                    </div>
                    <button type="button" class="save-proceed-btn" data-section="physical-examination" data-next="treatment-plan">
                        Save & Proceed to Treatment Plan
                    </button>
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

    <script src="../assets/js/bootstrap.bundle.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date for appointment to today
            const today = new Date().toISOString().split('T')[0];
            const appointmentField = document.querySelector('input[name="next_appointment"]');

            if (appointmentField) {
                appointmentField.min = today;
            }

            // Calculate BMI when height and weight are entered
            const heightField = document.querySelector('input[name="height"]');
            const weightField = document.querySelector('input[name="weight"]');
            const bmiField = document.querySelector('input[name="bmi"]');

            function calculateBMI() {
                if (heightField.value && weightField.value) {
                    const heightInMeters = heightField.value / 100;
                    const bmi = (weightField.value / (heightInMeters * heightInMeters)).toFixed(1);
                    bmiField.value = bmi;
                }
            }

            if (heightField && weightField && bmiField) {
                heightField.addEventListener('input', calculateBMI);
                weightField.addEventListener('input', calculateBMI);
            }

            // Calculate COWS total scores
            const cowsTables = document.querySelectorAll('.cows-table');
            cowsTables.forEach(table => {
                for (let i = 1; i <= 4; i++) {
                    const timeInputs = table.querySelectorAll(`input[name$="_time${i}"]`);
                    const totalField = table.querySelector(`input[name="cows_total_time${i}"]`);

                    if (timeInputs && totalField) {
                        timeInputs.forEach(input => {
                            input.addEventListener('input', function() {
                                let total = 0;
                                timeInputs.forEach(input => {
                                    if (input.value) {
                                        total += parseInt(input.value);
                                    }
                                });
                                totalField.value = total;
                            });
                        });
                    }
                }
            });

            // Section Save and Proceed functionality
            const saveButtons = document.querySelectorAll('.save-proceed-btn');
            const form = document.getElementById('clinicalForm');
            const partialSaveField = document.getElementById('partial_save');
            const currentSectionField = document.getElementById('current_section');
            const encounterIdField = document.getElementById('encounter_id');

            saveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const section = this.dataset.section;
                    const nextSection = this.dataset.next;

                    // Set hidden fields for partial save
                    partialSaveField.value = 'true';
                    currentSectionField.value = section;

                    // Submit form for partial save
                    form.submit();
                });
            });

            // Final submission
            const finalSubmit = document.getElementById('final-submit');
            if (finalSubmit) {
                finalSubmit.addEventListener('click', function(e) {
                    e.preventDefault();
                    partialSaveField.value = 'false';
                    currentSectionField.value = 'complete';
                    form.submit();
                });
            }

            // Check if there's a saved encounter ID in sessionStorage
            const savedEncounterId = sessionStorage.getItem('encounter_id');
            if (savedEncounterId) {
                encounterIdField.value = savedEncounterId;
            }

            // Auto-save form data to sessionStorage
            const formFields = form.querySelectorAll('input, select, textarea');
            formFields.forEach(field => {
                field.addEventListener('change', function() {
                    const formData = new FormData(form);
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        data[key] = value;
                    }
                    sessionStorage.setItem('form_autosave', JSON.stringify(data));
                });
            });

            // Load auto-saved data
            const savedData = sessionStorage.getItem('form_autosave');
            if (savedData) {
                const data = JSON.parse(savedData);
                formFields.forEach(field => {
                    if (data[field.name] !== undefined) {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            field.checked = (data[field.name] === field.value);
                        } else {
                            field.value = data[field.name];
                        }
                    }
                });
            }
        });

        // Handle response from server
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const saveResult = urlParams.get('save_result');

            if (saveResult) {
                const [action, section, encounterId] = saveResult.split(':');

                if (action === 'SECTION_SAVED' && encounterId) {
                    // Update encounter ID
                    document.getElementById('encounter_id').value = encounterId;
                    sessionStorage.setItem('encounter_id', encounterId);

                    // Update section status
                    const statusElement = document.getElementById(`${section}-status`);
                    if (statusElement) {
                        const statusSpan = statusElement.querySelector('span');
                        statusSpan.textContent = 'Saved';
                        statusSpan.className = 'status-saved';
                    }

                    // Scroll to next section if available
                    const nextSection = document.querySelector(`[data-section="${section}"]`).dataset.next;
                    if (nextSection) {
                        document.getElementById(`section-${nextSection}`).scrollIntoView({
                            behavior: 'smooth'
                        });
                    }

                    // Clear URL parameters
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
        / NEW CODE: Add this after your existing JavaScript
// Auto-scroll to current section when loading draft
document.addEventListener('DOMContentLoaded', function() {
    const currentSection = '<?php echo $current_section; ?>';
    if (currentSection && currentSection !== 'facility') {
        const sectionElement = document.getElementById(`section-${currentSection}`);
        if (sectionElement) {
            setTimeout(() => {
                sectionElement.scrollIntoView({ behavior: 'smooth' });
                // Highlight the current section
                sectionElement.style.boxShadow = '0 0 0 3px #3498db';
                setTimeout(() => {
                    sectionElement.style.boxShadow = '';
                }, 3000);
            }, 500);
        }
    }

    // Populate form with draft data
    const draftData = <?php echo json_encode($draft_data); ?>;
    if (draftData && Object.keys(draftData).length > 0) {
        populateFormWithDraftData(draftData);

        // Show draft loaded message
        showNotification('Draft loaded successfully! Continuing from ' + currentSection, 'success');
    }
});

function populateFormWithDraftData(data) {
    for (const [key, value] of Object.entries(data)) {
        try {
            const elements = document.querySelectorAll(`[name="${key}"]`);

            elements.forEach(element => {
                if (element.type === 'checkbox') {
                    // Handle checkboxes (arrays)
                    if (Array.isArray(value)) {
                        element.checked = value.includes(element.value);
                    } else if (typeof value === 'string' && value.includes(',')) {
                        // Handle comma-separated values
                        const values = value.split(',');
                        element.checked = values.includes(element.value);
                    }
                } else if (element.type === 'radio') {
                    // Handle radio buttons
                    if (element.value === value) {
                        element.checked = true;
                    }
                } else {
                    // Handle text inputs, selects, textareas
                    if (element.tagName === 'SELECT' && element.multiple) {
                        // Handle multiple select
                        const values = Array.isArray(value) ? value : [value];
                        Array.from(element.options).forEach(option => {
                            option.selected = values.includes(option.value);
                        });
                    } else {
                        element.value = value;
                    }
                }
            });
        } catch (error) {
            console.log('Error setting field:', key, error);
        }
    }

    // Trigger any calculated fields (like BMI, COWS totals)
    calculateBMI();
    calculateCOWSTotals();
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#27ae60' : '#3498db'};
        color: white;
        border-radius: 5px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        font-weight: bold;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function calculateCOWSTotals() {
    // Recalculate COWS totals after loading draft data
    const cowsTables = document.querySelectorAll('.cows-table');
    cowsTables.forEach(table => {
        for (let i = 1; i <= 4; i++) {
            const timeInputs = table.querySelectorAll(`input[name$="_time${i}"]`);
            const totalField = table.querySelector(`input[name="cows_total_time${i}"]`);

            if (timeInputs && totalField) {
                let total = 0;
                timeInputs.forEach(input => {
                    if (input.value) {
                        total += parseInt(input.value);
                    }
                });
                totalField.value = total;
            }
        }
    });
}
</script>
</body>
</html>