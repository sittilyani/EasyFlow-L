<?php
session_start();
include "../includes/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name
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

// Get the patient ID from the query parameter
$p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;

// Initialize data arrays
$medicalHistoryData = [];

if ($p_id) {
    // First, get the mat_id using p_id from the patients table
    $matIdQuery = "SELECT mat_id FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($matIdQuery);
    $stmt->bind_param('i', $p_id);
    $stmt->execute();
    $matIdResult = $stmt->get_result();
    $mat_id = null;
    if ($matIdResult->num_rows > 0) {
        $mat_id = $matIdResult->fetch_assoc()['mat_id'];
    }
    $stmt->close();

    // Now use mat_id to fetch the LATEST medical history record
    if ($mat_id) {
        $medicalHistoryQuery = "
            SELECT
                mh.*,
                TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age,
                p.clientName,
                p.nickName,
                p.sname,
                p.dob,
                p.reg_date,
                p.sex,
                p.marital_status,
                p.current_status,
                p.next_appointment
            FROM medical_history mh
            LEFT JOIN patients p ON mh.mat_id = p.mat_id
            WHERE mh.mat_id = ?
            ORDER BY mh.visitDate DESC, mh.id DESC
            LIMIT 1";

        $stmt = $conn->prepare($medicalHistoryQuery);
        $stmt->bind_param('s', $mat_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $medicalHistoryData = $result->fetch_assoc();
            } else {
                // If no medical history found, get basic patient data
                $patientQuery = "
                    SELECT
                        p.*,
                        TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age
                    FROM patients p
                    WHERE p.mat_id = ?";
                $stmt2 = $conn->prepare($patientQuery);
                $stmt2->bind_param('s', $mat_id);
                $stmt2->execute();
                $patientResult = $stmt2->get_result();
                if ($patientResult->num_rows > 0) {
                    $medicalHistoryData = $patientResult->fetch_assoc();
                    // Set default HIV status to Negative for new patients
                    $medicalHistoryData['hiv_status'] = 'Negative';
                }
                $stmt2->close();
            }
        } else {
            die("Query Error: " . $stmt->error);
        }
        $stmt->close();

        // Fetch the LATEST viral load results separately if not in medical_history
        if (!isset($medicalHistoryData['last_vlDate']) || empty($medicalHistoryData['last_vlDate'])) {
            $latestVlQuery = "
                SELECT
                    last_vlDate,
                    results
                FROM viral_load
                WHERE mat_id = ?
                ORDER BY vl_id DESC
                LIMIT 1";
            $stmt = $conn->prepare($latestVlQuery);
            $stmt->bind_param('s', $mat_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $latestVlData = $result->fetch_assoc();
                    $medicalHistoryData['last_vlDate'] = $latestVlData['last_vlDate'];
                    $medicalHistoryData['results'] = $latestVlData['results'];
                }
            }
            $stmt->close();
        }
    }
}

// Function to format date for HTML input
function formatDateForInput($date) {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }

    // If date is already in YYYY-MM-DD format, return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }

    // If date is in different format, convert it
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
}

// Define default values for missing fields
$defaultData = [
    'id' => '',
    'visitDate' => date('Y-m-d'),
    'mat_id' => '',
    'clientName' => '',
    'nickName' => '',
    'sname' => '',
    'dob' => '',
    'age' => '',
    'reg_date' => '',
    'sex' => '',
    'hiv_status' => 'Negative', // Default to Negative
    'marital_status' => '',
    'art_regimen' => '',
    'regimen_type' => '',
    'tb_status' => '',
    'tb_regimen' => '',
    'tb_start_date' => '',
    'tb_end_date' => '',
    'tpt_regimen' => '',
    'tpt_start_date' => '',
    'tpt_end_date' => '',
    'hepc_status' => '',
    'other_status' => '',
    'clinical_notes' => '',
    'current_status' => '',
    'last_vlDate' => '',
    'results' => '',
    'clinician_name' => $clinician_name,
    'next_appointment' => '',
    'appointment_status' => ''
];
$medicalHistoryData = array_merge($defaultData, $medicalHistoryData);

// Format all dates for HTML input
$medicalHistoryData['visitDate'] = formatDateForInput($medicalHistoryData['visitDate']);
$medicalHistoryData['dob'] = formatDateForInput($medicalHistoryData['dob']);
$medicalHistoryData['reg_date'] = formatDateForInput($medicalHistoryData['reg_date']);
$medicalHistoryData['last_vlDate'] = formatDateForInput($medicalHistoryData['last_vlDate']);
$medicalHistoryData['next_appointment'] = formatDateForInput($medicalHistoryData['next_appointment']);
$medicalHistoryData['tb_start_date'] = formatDateForInput($medicalHistoryData['tb_start_date']);
$medicalHistoryData['tb_end_date'] = formatDateForInput($medicalHistoryData['tb_end_date']);
$medicalHistoryData['tpt_start_date'] = formatDateForInput($medicalHistoryData['tpt_start_date']);
$medicalHistoryData['tpt_end_date'] = formatDateForInput($medicalHistoryData['tpt_end_date']);

// Handle form submission feedback
$successMessages = [];
$errorMessages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['mat_id']) || empty($_POST['clientName'])) {
        $errorMessages[] = "MAT ID and Client Name are required.";
    } else {
        // Assume clinicianForm_process.php handles the insert
        // Redirect back to clinician_list.php with a success message
        $successMessages[] = "Form submitted successfully.";
        header("Location: clinician_list.php?message=" . urlencode("Form submitted successfully."));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinician Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    

    <style>
        .form-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-gap: 20px;
            margin: 0 50px;
            padding: 20px;
            background: #CCFFFF;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .readonly-input {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .clinical-notes-container {
            grid-column: 1 / -1;
            margin-bottom: 20px;
        }
        .clinical-notes-container textarea {
            width: 100%;
            min-height: 120px;
            resize: vertical;
        }
        .tb-fields-container, .tpt-fields-container {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        .section-title {
            grid-column: 1 / -1;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #dee2e6;
        }
        .submit-container {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 20px;
        }
        .date-input {
            /* Ensure date inputs display properly */
            min-height: 38px;
        }
    </style>
</head>
<body>
    <div class="content-main">
    <h2>HIV clinical follow up form</h2>

    <?php if (!empty($successMessages)): ?>
        <div class="alert alert-success">
            <h4>Success:</h4>
            <ul>
                <?php foreach ($successMessages as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessages)): ?>
        <div class="alert alert-danger">
            <h4>Errors:</h4>
            <ul>
                <?php foreach ($errorMessages as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($medicalHistoryData['mat_id'])): ?>
        <form action="clinicianForm_process-new.php" method="post" class="post">
            <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($p_id); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($medicalHistoryData['id']); ?>">

            <div class="form-container">
                <!-- Visit Date -->
                <div class="form-group">
                    <label for="visitDate">Visit Date</label>
                    <input type="date" name="visitDate" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['visitDate']); ?>" required>
                </div>

                <!-- Patient Basic Information -->
                <div class="form-group">
                    <label for="mat_id">MAT ID</label>
                    <input type="text" name="mat_id" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['mat_id']); ?>">
                </div>
                <div class="form-group">
                    <label for="clientName">Client Name</label>
                    <input type="text" name="clientName" class="form-control" value="<?php echo htmlspecialchars($medicalHistoryData['clientName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nickName">Nick Name</label>
                    <input type="text" name="nickName" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['nickName']); ?>">
                </div>
                <div class="form-group">
                    <label for="sname">Sur Name</label>
                    <input type="text" name="sname" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['sname']); ?>">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" name="dob" class="form-control date-input readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['dob']); ?>">
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="text" name="age" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['age']); ?>">
                </div>
                <div class="form-group">
                    <label for="reg_date">Enrolment Date</label>
                    <input type="date" name="reg_date" class="form-control date-input readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['reg_date']); ?>">
                </div>
                <div class="form-group">
                    <label for="sex">Gender</label>
                    <input type="text" name="sex" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['sex']); ?>">
                </div>
                <div class="form-group">
                    <label for="marital_status">Marital Status</label>
                    <input type="text" name="marital_status" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['marital_status'] ?? ''); ?>">
                </div>

                <!-- HIV Status with dynamic readonly logic -->
                <div class="form-group">
                    <label for="hiv_status">HIV Status</label>
                    <select name="hiv_status" id="hiv_status" class="form-control"
                        <?php echo ($medicalHistoryData['hiv_status'] === 'Positive') ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                        <?php
                        $sql = "SELECT hiv_status_name FROM tbl_hiv_status";
                        $result = $conn->query($sql);
                        $current_hiv_status = $medicalHistoryData['hiv_status'] ?? 'Negative';
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['hiv_status_name'] == $current_hiv_status) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['hiv_status_name']) . "' $selected>" . htmlspecialchars($row['hiv_status_name']) . "</option>";
                            }
                        } else {
                            echo "<option value='Negative' selected>Negative</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- ART Regimen (conditionally enabled) -->
                <div class="form-group">
                    <label for="art_regimen">ART Regimen</label>
                    <select name="art_regimen" id="art_regimen" class="form-control"
                        <?php echo ($medicalHistoryData['hiv_status'] === 'Negative' || $medicalHistoryData['hiv_status'] === 'Unknown') ? 'disabled' : ''; ?>>
                        <option value="">Select ART Regimen</option>
                        <?php
                        $sql = "SELECT regimen_name FROM regimens";
                        $result = $conn->query($sql);
                        $current_art_regimen = $medicalHistoryData['art_regimen'] ?? '';
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['regimen_name'] == $current_art_regimen) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['regimen_name']) . "' $selected>" . htmlspecialchars($row['regimen_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Regimen Type (conditionally enabled) -->
                <div class="form-group">
                    <label for="regimen_type">Regimen Type</label>
                    <select name="regimen_type" id="regimen_type" class="form-control"
                        <?php echo ($medicalHistoryData['hiv_status'] === 'Negative' || $medicalHistoryData['hiv_status'] === 'Unknown') ? 'disabled' : ''; ?>>
                        <option value="">Select Regimen Type</option>
                        <?php
                        $sql = "SELECT regimen_type_name FROM regimen_type";
                        $result = $conn->query($sql);
                        $current_regimen_type = $medicalHistoryData['regimen_type'] ?? '';
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['regimen_type_name'] == $current_regimen_type) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['regimen_type_name']) . "' $selected>" . htmlspecialchars($row['regimen_type_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- TB Status with dynamic fields -->
                <div class="form-group">
                    <label for="tb_status">TB Status</label>
                    <select name="tb_status" id="tb_status" class="form-control"
                        <?php echo ($medicalHistoryData['tb_status'] === 'Positive') ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                        <option value="">Select TB Status</option>
                        <?php
                        $sql = "SELECT status_name FROM tb_status";
                        $result = $conn->query($sql);
                        $current_tb_status = $medicalHistoryData['tb_status'] ?? '';
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == $current_tb_status) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['status_name']) . "' $selected>" . htmlspecialchars($row['status_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- TB Treatment Fields (shown when TB Positive) -->
                <div id="tbFieldsContainer" class="tb-fields-container" style="display: <?php echo ($medicalHistoryData['tb_status'] === 'Positive') ? 'grid' : 'none'; ?>;">
                    <div class="section-title">TB Treatment Details</div>

                    <div class="form-group">
                        <label for="tb_regimen">TB Regimen</label>
                        <select name="tb_regimen" id="tb_regimen" class="form-control">
                            <option value="">Select TB Regimen</option>
                            <?php
                            $sql = "SELECT regimen_name FROM tb_regimens";
                            $result = $conn->query($sql);
                            $current_tb_regimen = $medicalHistoryData['tb_regimen'] ?? '';
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['regimen_name'] == $current_tb_regimen) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['regimen_name']) . "' $selected>" . htmlspecialchars($row['regimen_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tb_start_date">TB Start Date</label>
                        <input type="date" name="tb_start_date" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['tb_start_date']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tb_end_date">TB End Date</label>
                        <input type="date" name="tb_end_date" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['tb_end_date']); ?>">
                    </div>
                </div>

                <!-- TPT Fields (shown when TB is not Positive) -->
                <div id="tptFieldsContainer" class="tpt-fields-container" style="display: <?php echo ($medicalHistoryData['tb_status'] !== 'Positive' && !empty($medicalHistoryData['tb_status'])) ? 'grid' : 'none'; ?>;">
                    <div class="section-title">TPT (TB Preventive Therapy) Details</div>

                    <div class="form-group">
                        <label for="tpt_regimen">TPT Regimen</label>
                        <select name="tpt_regimen" id="tpt_regimen" class="form-control">
                            <option value="">Select TPT Regimen</option>
                            <?php
                            $sql = "SELECT tpt_regimen_name FROM tpt_regimens";
                            $result = $conn->query($sql);
                            $current_tpt_regimen = $medicalHistoryData['tpt_regimen'] ?? '';
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['tpt_regimen_name'] == $current_tpt_regimen) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['tpt_regimen_name']) . "' $selected>" . htmlspecialchars($row['tpt_regimen_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tpt_start_date">TPT Start Date</label>
                        <input type="date" name="tpt_start_date" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['tpt_start_date']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tpt_end_date">TPT End Date</label>
                        <input type="date" name="tpt_end_date" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['tpt_end_date']); ?>">
                    </div>
                </div>

                <!-- Other Status Fields -->
                <div class="form-group">
                    <label for="hepc_status">Hepatitis C Status</label>
                    <select name="hepc_status" class="form-control">
                        <option value="">Select Hepatitis C Status</option>
                        <?php
                        $sql = "SELECT status_name FROM hepc_status";
                        $result = $conn->query($sql);
                        $current_hepc_status = $medicalHistoryData['hepc_status'] ?? '';
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == $current_hepc_status) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['status_name']) . "' $selected>" . htmlspecialchars($row['status_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="other_status">Other Disease Status</label>
                    <select name="other_status" class="form-control">
                        <option value="">Select Other Status</option>
                        <?php
                        $sql = "SELECT status_name FROM other_status";
                        $result = $conn->query($sql);
                        $current_other_status = $medicalHistoryData['other_status'] ?? '';
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == $current_other_status) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['status_name']) . "' $selected>" . htmlspecialchars($row['status_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Current Status -->
                <div class="form-group">
                    <label for="current_status">Current Status</label>
                    <input type="text" name="current_status" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['current_status']); ?>">
                </div>

                <!-- Viral Load Information -->
                <div class="form-group">
                    <label for="last_vlDate">Last VL Date</label>
                    <input type="date" name="last_vlDate" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['last_vlDate']); ?>">
                </div>

                <div class="form-group">
                    <label for="results">VL Results</label>
                    <input type="text" name="results" class="form-control" value="<?php echo htmlspecialchars($medicalHistoryData['results'] ?? 'None', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Next Appointment -->
                <div class="form-group">
                    <label for="next_appointment">Next Appointment Date</label>
                    <input type="date" name="next_appointment" class="form-control date-input" value="<?php echo htmlspecialchars($medicalHistoryData['next_appointment']); ?>">
                </div>

                <!-- Appointment Status -->
                <div class="form-group">
                    <label for="appointment_status">Appointment Status</label>
                    <select name="appointment_status" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="Scheduled" <?php echo ($medicalHistoryData['appointment_status'] == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="Completed" <?php echo ($medicalHistoryData['appointment_status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo ($medicalHistoryData['appointment_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="No Show" <?php echo ($medicalHistoryData['appointment_status'] == 'No Show') ? 'selected' : ''; ?>>No Show</option>
                        <option value="Rescheduled" <?php echo ($medicalHistoryData['appointment_status'] == 'Rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
                    </select>
                </div>

                <!-- Clinical Notes - Full Width -->
                <div class="clinical-notes-container">
                    <label for="clinical_notes">Clinical Notes</label>
                    <textarea name="clinical_notes" id="clinical_notes" class="form-control" cols="30" rows="4"><?php echo htmlspecialchars($medicalHistoryData['clinical_notes']); ?></textarea>
                </div>

                <!-- Clinician Name -->
                <div class="form-group">
                    <label for="clinician_name">Clinician Name</label>
                    <input type="text" name="clinician_name" class="form-control readonly-input" readonly value="<?php echo htmlspecialchars($medicalHistoryData['clinician_name']); ?>">
                </div>

                <!-- Submit Button -->
                <div class="submit-container">
                    <button type="submit" name="submit" class="btn btn-primary custom-submit-btn">Submit Form</button>
                </div>
            </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const hivStatusSelect = document.getElementById('hiv_status');
                const artRegimenSelect = document.getElementById('art_regimen');
                const regimenTypeSelect = document.getElementById('regimen_type');
                const tbStatusSelect = document.getElementById('tb_status');
                const tbFieldsContainer = document.getElementById('tbFieldsContainer');
                const tptFieldsContainer = document.getElementById('tptFieldsContainer');

                function toggleARTFields() {
                    const hivStatus = hivStatusSelect.value;

                    if (hivStatus === 'Negative' || hivStatus === 'Unknown') {
                        artRegimenSelect.disabled = true;
                        regimenTypeSelect.disabled = true;
                        artRegimenSelect.value = '';
                        regimenTypeSelect.value = '';
                    } else {
                        artRegimenSelect.disabled = false;
                        regimenTypeSelect.disabled = false;
                    }
                }

                function toggleTBFields() {
                    const tbStatus = tbStatusSelect.value;

                    // Hide both containers first
                    tbFieldsContainer.style.display = 'none';
                    tptFieldsContainer.style.display = 'none';

                    if (tbStatus === 'Positive') {
                        tbFieldsContainer.style.display = 'grid';
                    } else if (tbStatus && tbStatus !== 'Positive') {
                        tptFieldsContainer.style.display = 'grid';
                    }
                }

                // HIV Status event listeners
                if (!hivStatusSelect.hasAttribute('readonly')) {
                    hivStatusSelect.addEventListener('change', toggleARTFields);
                }

                // TB Status event listeners
                if (!tbStatusSelect.hasAttribute('readonly')) {
                    tbStatusSelect.addEventListener('change', toggleTBFields);
                }

                // Initial state on page load
                toggleARTFields();
                toggleTBFields();
            });
        </script>

    </div>
    <?php else: ?>
        <div class="alert alert-danger">No patient found for the provided ID.</div>
    <?php endif; ?>
</body>
</html>