<?php
session_start();
include '../includes/config.php';

$page_title = 'Routine Dispensing';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$conn->set_charset('utf8mb4');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

// Initialize variables to avoid PHP notices
$currentSettings = [];
$photo = null;
$num_rows = 0;
$new_num_rows = 0;
$appointmentDate = 'NO APPOINTMENT DATE. Refer to Clinician';
$lastvisitDate = 'No previous visit found';
$daysToAppointment = 0;
$isMissed = false;
$otherPrescriptions = []; // Initialized here, will be populated below
$prescriptionCount = 0; // Initialize prescription count

if ($userId) {
    // Fetch the current settings for the user
    $query = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();

    // Calculate the start date (1st of the month)
    $startDate = date('Y-m-01');
    // Calculate the end date (yesterday)
    $endDate = date('Y-m-d', strtotime('-1 day'));

    // Construct the SQL query with a placeholder for the mat_id parameter
    $query2 = "SELECT COUNT(*) AS num_rows
                 FROM patients p
                 JOIN pharmacy d ON p.mat_id = d.mat_id
                 WHERE p.mat_id = ?
                 AND d.dosage > 0
                 AND d.visitDate BETWEEN ? AND ?"; // Add condition for visitDate between startDate and endDate

    // Prepare the SQL statement
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param('sss', $currentSettings['mat_id'], $startDate, $endDate); // Bind visitDate conditions
    $stmt2->execute();
    $stmt2->bind_result($num_rows);
    $stmt2->fetch();
    $stmt2->close();

    // Calculate the missed days (new_num_rows)
    $endDateObj = new DateTime($endDate);
    $new_num_rows = $endDateObj->format('j') - $num_rows;

    }
    
    // Fetch and calculate the earliest missed appointment from multiple TCA dates
    $appointmentInfo = [
        'date' => null,
        'days' => 0,
        'isMissed' => false,
        'message' => 'NO APPOINTMENT DATE. Refer to Clinician',
        'referTo' => 'Clinician',
        'appointmentType' => 'Clinician'
    ];

    if ($userId) {
        // Fetch all appointment dates
        $appointmentQuery = "SELECT
                next_appointment,
                psycho_social_tca,
                psychiatric_tca,
                nursing_tca,
                nutrition_tca,
                laboratory_tca,
                records_tca
            FROM patients
            WHERE mat_id = ?";
        $appointmentStmt = $conn->prepare($appointmentQuery);
        $appointmentStmt->bind_param('s', $userId);
        $appointmentStmt->execute();
        $appointmentResult = $appointmentStmt->get_result();

        if ($appointmentResult->num_rows > 0) {
            $appointmentRow = $appointmentResult->fetch_assoc();

            // Define appointment types with their fields and referral roles
            $appointmentTypes = [
                'Clinician' => [
                    'field' => 'next_appointment',
                    'referTo' => 'Clinician'
                ],
                'Psychosocial' => [
                    'field' => 'psycho_social_tca',
                    'referTo' => 'Psychologist'
                ],
                'Psychiatric' => [
                    'field' => 'psychiatric_tca',
                    'referTo' => 'Psychiatrist'
                ],
                'Nursing' => [
                    'field' => 'nursing_tca',
                    'referTo' => 'Nurse'
                ],
                'Nutritional' => [
                    'field' => 'nutrition_tca',
                    'referTo' => 'Nutritionist'
                ],
                'Laboratory' => [
                    'field' => 'laboratory_tca',
                    'referTo' => 'Laboratory'
                ],
                'Records' => [
                    'field' => 'records_tca',
                    'referTo' => 'Records'
                ]
            ];

            $currentDate = new DateTime();
            $earliestDate = null;
            $earliestType = null;
            $earliestReferTo = null;

            // Check each appointment type for the earliest date
            foreach ($appointmentTypes as $type => $info) {
                $dateField = $appointmentRow[$info['field']];

                if ($dateField) {
                    try {
                        $appointmentDate = new DateTime($dateField);

                        // Only consider appointments for active, LTFU, and defaulted statuses
                        if (isset($currentSettings['current_status']) &&
                            in_array($currentSettings['current_status'], ['Active', 'LTFU', 'Defaulted'])) {

                            if ($earliestDate === null || $appointmentDate < $earliestDate) {
                                $earliestDate = $appointmentDate;
                                $earliestType = $type;
                                $earliestReferTo = $info['referTo'];
                            }
                        }
                    } catch (Exception $e) {
                        // Invalid date format, skip
                        continue;
                    }
                }
            }

            if ($earliestDate) {
                $interval = $currentDate->diff($earliestDate);
                $daysToAppointment = $interval->days;
                $isMissed = ($currentDate > $earliestDate);

                $appointmentInfo['date'] = $earliestDate->format('Y-m-d');
                $appointmentInfo['days'] = $daysToAppointment;
                $appointmentInfo['isMissed'] = $isMissed;
                $appointmentInfo['appointmentType'] = $earliestType;
                $appointmentInfo['referTo'] = $earliestReferTo;

                if ($isMissed) {
                    $appointmentInfo['message'] = "Missed {$earliestType} appointment on " . $earliestDate->format('Y-m-d') . " (Refer to {$earliestReferTo})";
                } else {
                    $appointmentInfo['message'] = "{$daysToAppointment} days to {$earliestType} appointment ({$earliestReferTo})";
                }
            } else {
                $appointmentInfo['message'] = 'NO APPOINTMENT DATE. Refer to Clinician';
            }
        }
        $appointmentStmt->close();

        // Set variables for use in the rest of the script
        $appointmentDate = $appointmentInfo['date'] ?? 'NO APPOINTMENT DATE. Refer to Clinician';
        $daysToAppointment = $appointmentInfo['days'];
        $isMissed = $appointmentInfo['isMissed'];
        $daysToAppointmentDisplay = $appointmentInfo['message'];
    }

// Fetch photo from the mat_id and photos table based on mat_id
if (isset($_GET['mat_id'])) {
    $patientsId = $_GET['mat_id'];

    // Fetch patient details from the database based on the ID
    $sql = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($sql);
    // Assuming mat_id can be a string, keeping 's' if it's alphanumeric, but the previous bind was 'i'. Reverting to 's' as mat_id is often a code.
    $stmt->bind_param('s', $patientsId);
    $stmt->execute();
    $result = $stmt->get_result();

    $patients = $result->fetch_assoc();

    if (!$patients) {
        die("Patient not found");
    }
} else {
    die("Invalid request. Please provide a patient ID.");
}

// Fetch photo details from the database based on the MAT ID
$sql_photo = "SELECT image FROM photos WHERE mat_id = ? ORDER BY visitDate DESC LIMIT 1";
$stmt_photo = $conn->prepare($sql_photo);
$stmt_photo->bind_param('s', $patients['mat_id']);
$stmt_photo->execute();
$result_photo = $stmt_photo->get_result();
$photo = $result_photo->fetch_assoc();
$stmt_photo->close(); // Close photo statement

// Check if photo exists in the file system
$photoPath = '';
if ($photo && !empty($photo['image'])) {
    $photoPath = '../clientPhotos/' . $photo['image'];

    // Check if the file actually exists
    if (!file_exists($photoPath)) {
        $photoPath = ''; // Reset if file doesn't exist
    }
}

// Fetch the last visit date for the specific mat_id
$lastVisitQuery = "SELECT dispDate FROM pharmacy WHERE mat_id = ? ORDER BY dispDate DESC LIMIT 1";
$lastVisitStmt = $conn->prepare($lastVisitQuery);
$lastVisitStmt->bind_param('s', $userId);
$lastVisitStmt->execute();
$lastVisitResult = $lastVisitStmt->get_result();

if ($lastVisitResult->num_rows > 0) {
    $lastVisitRow = $lastVisitResult->fetch_assoc();
    $lastvisitDate = $lastVisitRow['dispDate'];
}
$lastVisitStmt->close();


// ***************************************************************
// NEW: Fetch other prescriptions (from other_prescriptions table)
// ***************************************************************

$mat_id = $currentSettings['mat_id'] ?? $userId;
$groupedPrescriptions = []; // Stores unique prescriptions
$drugDetailsForDispensing = []; // Stores flattened drug rows for the table

if ($mat_id) {
    // 1. Fetch main prescription records
    $mainPrescriptionsQuery = "
        SELECT
            prescription_id,
            prescription_date,
            prescriber_name,
            prescr_status
        FROM other_prescriptions
        WHERE mat_id = ?
        AND prescr_status IN ('submitted')
        ORDER BY prescription_date DESC
    ";

    $mainStmt = $conn->prepare($mainPrescriptionsQuery);
    if ($mainStmt) {
        $mainStmt->bind_param('s', $mat_id);
        $mainStmt->execute();
        $mainResult = $mainStmt->get_result();

        // Populate the groupedPrescriptions array
        while ($row = $mainResult->fetch_assoc()) {
            $prescription_id = $row['prescription_id'];
            $groupedPrescriptions[$prescription_id] = $row;
            $groupedPrescriptions[$prescription_id]['drugs'] = [];
        }
        $mainStmt->close();
    }

    // 2. If prescriptions were found, fetch the associated drugs
    if (!empty($groupedPrescriptions)) {
        // Create a string of placeholders for the IN clause (e.g., '?,?,?')
        $placeholders = implode(',', array_fill(0, count($groupedPrescriptions), '?'));
        $prescriptionIds = array_keys($groupedPrescriptions);

        $drugsQuery = "
            SELECT
                *
            FROM prescription_drugs
            WHERE prescription_id IN ({$placeholders})
            ORDER BY prescription_id, drug_name
        ";

        $drugsStmt = $conn->prepare($drugsQuery);
        if ($drugsStmt) {
            // Bind the prescription IDs dynamically (all are strings 's')
            $types = str_repeat('s', count($prescriptionIds));
            $drugsStmt->bind_param($types, ...$prescriptionIds);
            $drugsStmt->execute();
            $drugsResult = $drugsStmt->get_result();

            while ($drugRow = $drugsResult->fetch_assoc()) {
                $prescription_id = $drugRow['prescription_id'];

                // Prepare for the dispensing table (simplified structure)
                $drugDetailsForDispensing[] = [
                    'id' => $drugRow['id'], // Assuming 'id' is the primary key in prescription_drugs
                    'prescription_id' => $prescription_id,
                    'drugName' => $drugRow['drug_name'],
                    'dosage' => $drugRow['dosing'], // Using 'dosing' for the quantity per administration
                    'routetype' => $drugRow['frequency'], // Using 'frequency' for routetype context
                    'durationUnit' => 'Days', // Placeholder, using days from the table structure
                    'duration' => $drugRow['days'],
                    'totalDose' => $drugRow['total_dosage'],
                    'prescr_status' => $groupedPrescriptions[$prescription_id]['prescr_status']
                ];
            }
            $drugsStmt->close();
        }
    }

    // Count for the stat box
    $prescriptionCount = count($groupedPrescriptions);
    $otherPrescriptions = $drugDetailsForDispensing; // Use the flattened list for the table loop
} else {
    $prescriptionCount = 0;
}
// ***************************************************************
// END NEW: Fetch other prescriptions
// ***************************************************************

// Fetch the logged-in user's name from tblusers
$pharm_office_name = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $pharm_office_name = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// Fetch status names from the status table for the dropdown
$statusOptions = '';
$statusQuery = "SELECT status_id, status_name FROM status";
$statusResult = $conn->query($statusQuery);

if ($statusResult->num_rows > 0) {
    while ($statusRow = $statusResult->fetch_assoc()) {
        $statusName = $statusRow['status_name'];
        $selected = (isset($currentSettings['current_status']) && $statusName == $currentSettings['current_status']) ? 'selected' : '';
        $statusOptions .= "<option value='" . htmlspecialchars($statusName) . "' $selected>" . htmlspecialchars($statusName) . "</option>";
    }
} else {
    $statusOptions = "<option value=''>No status found</option>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacy DAR</title>
    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">-->
    <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
    <style>
        /* ... (Your existing CSS styles) ... */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background-color: #f5f7fa; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h2 { color: #2C3162; margin: 20px 0; text-align: center; }
        .stats-container { display: flex; justify-content: space-between; background-color: #deffee; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-item { text-align: center; padding: 10px; border-radius: 6px; flex: 1; margin: 0 10px; }
        .stat-days { background-color: #deffee; color: green; border: 1px solid #a3d9b1; }
        .stat-missed { background-color: #fff9c4; color: #d32f2f; border: 1px solid #ffd54f; }
        .stat-appointment { background-color: #e3f2fd; color: #1976d2; border: 1px solid #90caf9; }
        .stat-prescription { background-color: #FF7575; color: #FFFFFF; border: 1px solid #000000; }
        .stat-prescription a { color: #FFFFFF; text-decoration: none; }
        .stat-visit { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        .stat-days-next { background-color: #fff3e0; color: #ef6c00; border: 1px solid #ffb74d; }
        .stat-photo { background-color: #e8f5e9; color: #388e3c; border: 1px solid #a5d6a7; }
        .stat-value { font-size: 20px; font-weight: bold; display: block; }
        .stat-label { font-size: 14px; margin-top: 5px; }
        .form-container { display: grid; grid-template-columns: repeat(4, 1fr) 200px; gap: 20px; background-color: #deffee; height: 600px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #2C3162; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .readonly-input { background-color: #FFFF94; cursor: not-allowed; }
        .photo-container { grid-column: 5; grid-row: 1 / span 100; display: flex; flex-direction: column; align-items: center; border: 2px dotted #2C3162; padding: 10px; border-radius: 8px; background-color: white; height: fit-content; }
        .photo-container img { max-width: 180px; max-height: 180px; margin-bottom: 10px; }
        /*.photo-container p { text-align: center; color: #777; }*/
        .submit-btn { background-color: #82b543; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; margin-top: 10px; transition: background-color 0.3s; }
        .submit-btn:hover { background-color: orange; color: black; }
        .form-container {
            /* Change grid-template-columns to fit 4 columns for inputs + 1 for photo */
            display: grid;
            grid-template-columns: repeat(4, 1fr) 200px;
            gap: 20px;
            background-color: #66ccff;
            /* Remove fixed height to let content define height */
            /* height: 600px; */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* Define the area for the input columns (1-4) and the photo column (5) */
        .form-group-column {
            grid-column: span 1; /* Default for the four input columns */
        }
        .photo-container {
            grid-column: 5;
            grid-row: 1 / span 100; /* Stays in the fifth column for all rows */
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 2px dotted #2C3162;
            padding: 10px;
            border-radius: 8px;
            background-color: white;
            height: fit-content;
        }
        /* NEW: Define the full-width area for the prescriptions table and button */
        .full-width-section {
            grid-column: 1 / 5; /* Spans columns 1, 2, 3, and 4 (excluding the photo column) */
            margin-top: 10px;
        }
        /* Repositioning the prescriptions-container and submit-btn */
        .prescriptions-container {
            margin-bottom: 20px; /* Space between table and button */
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .prescriptions-table { width: 100%; border-collapse: collapse; }
        .prescriptions-table th, .prescriptions-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .prescriptions-table th { background-color: #f2f2f2; font-weight: bold; }
        .prescriptions-table input[type="number"] { width: 80px; padding: 5px; }
        .prescriptions-table input[type="checkbox"] { transform: scale(1.2); }
        .custom-alert { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: yellow; color: red; border: 2px solid red; padding: 20px; width: 300px; text-align: center; z-index: 1000; border-radius: 8px; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .custom-alert button { margin-top: 10px; padding: 8px 16px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .custom-alert button:hover { background-color: darkred; }
        .missed-appointment { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Controlled Drugs Daily Activity Register</h2>

        <div class="stats-container">
            <div class="stat-item stat-days">
                <span class="stat-value"><?php echo $num_rows; ?></span>
                <span class="stat-label">Days Dispensed</span>
            </div>
            <div class="stat-item stat-missed">

                <a href='../pharmacy/view-missed.php?mat_id=<?php echo htmlspecialchars($currentSettings['mat_id'] ?? $userId); ?>'><span class="stat-value"><?php echo $new_num_rows; ?></span></a>
                <span class="stat-label">Days Missed</span>
            </div>

            <div class="stat-item stat-appointment">
                <span class="stat-value">
                    <?php
                    if ($isMissed && !empty($appointmentInfo['date'])) {
                        echo "<span style='color: red; font-size: 14px;'>" .
                             htmlspecialchars($appointmentInfo['appointmentType']) . "<br>" .
                             htmlspecialchars($appointmentInfo['date']) . "</span>";
                    } elseif (!empty($appointmentInfo['date'])) {
                        echo htmlspecialchars($appointmentInfo['date']);
                    } else {
                        echo "No Appointment";
                    }
                    ?>
                </span>
                <span class="stat-label"><?php echo htmlspecialchars($appointmentInfo['message']); ?></span>
            </div>

            <!--*******************************************
            diplay other prescriptions here
            *******************************************-->

            <div class="stat-item stat-prescription">
                <span class="stat-value">
                    <?php echo $prescriptionCount; ?>
                </span>

                <?php if ($prescriptionCount > 0): ?>
                    <?php
                        // Get the ID of the first unique prescription for the 'View details' link
                        $firstPrescriptionId = array_key_first($groupedPrescriptions);
                    ?>
                    <a href="view_prescription_details.php?id=<?php echo htmlspecialchars($firstPrescriptionId); ?>" class="btn btn-info btn-sm">found view details</a>
                <?php else: ?>
                    <span class="stat-label">0 Prescription found</span>
                <?php endif; ?>
            </div>

            <!--*******************************************
            End of diplay of other prescriptions here
            *******************************************-->

            <div class="stat-item stat-visit">
                <span class="stat-value"><?php echo htmlspecialchars($lastvisitDate); ?></span>
                <span class="stat-label">Last disp Date</span>
            </div>
            <div class="stat-item stat-days-next">
                <span class="stat-value">
                    <?php
                    if ($isMissed) {
                        echo "<span style='color: red; font-size: 16px;'>MISSED<br>Refer to " .
                             htmlspecialchars($appointmentInfo['referTo']) . "</span>";
                    } else {
                        echo $daysToAppointment;
                    }
                    ?>
                </span>
                <span class="stat-label">
                    <?php
                    if ($isMissed) {
                        echo htmlspecialchars($appointmentInfo['appointmentType']) . " Appointment";
                    } else {
                        echo "Days To Next Appointment";
                    }
                    ?>
                </span>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <a href="../photos/photo_capture_dispensing.php?p_id=<?php echo htmlspecialchars($currentSettings['p_id']); ?>">Capture Photo</a>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <a href="../photos/photo_capture_dispensing.php?p_id=<?php echo htmlspecialchars($currentSettings['p_id']); ?>&action=update" class="update-link">Update Photo</a>
            </div>
        </div>

        <form id="dispenseForm" action="dispensingData_process.php" method="post" onsubmit="return validateForm()">
        <div class="form-container">

            <div class="form-group-column">
                <div class="form-group">
                    <label for="visitDate">Visit Date</label>
                    <input type="date" name="visitDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="mat_id">MAT ID</label>
                    <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="mat_number">MAT Number</label>
                    <input type="text" name="mat_number" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_number']) ? htmlspecialchars($currentSettings['mat_number']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="clientName">Client Name</label>
                    <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="nickName">Nick Name</label>
                    <input type="text" name="nickName" class="readonly-input" value="<?php echo isset($currentSettings['nickName']) ? htmlspecialchars($currentSettings['nickName']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="text" name="age" class="readonly-input" readonly value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="sex">Gender</label>
                    <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="p_address">Residence</label>
                    <input type="text" name="p_address" class="readonly-input" value="<?php echo isset($currentSettings['p_address']) ? htmlspecialchars($currentSettings['p_address']) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="cso">CSO</label>
                    <input type="text" name="cso" class="readonly-input" readonly value="<?php echo isset($currentSettings['cso']) ? htmlspecialchars($currentSettings['cso']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="drugname">Drug</label>
                    <input type="text" name="drugname" class="readonly-input" readonly value="<?php echo isset($currentSettings['drugname']) ? htmlspecialchars($currentSettings['drugname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="dosage">Dosage</label>
                    <input type="text" name="dosage" class="readonly-input" readonly value="<?php echo isset($currentSettings['dosage']) ? htmlspecialchars($currentSettings['dosage']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="reasons">Dose Adjustments Reasons</label>
                    <input type="text" name="reasons" class="readonly-input" readonly value="<?php echo isset($currentSettings['reasons']) ? htmlspecialchars($currentSettings['reasons']) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="current_status">Current Status</label>current stus
                    <select id="current_status" name="current_status">
                        <?php echo $statusOptions; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pharm_officer_name">Dispensing Officer Name</label>
                    <input type="text" name="pharm_officer_name" class="readonly-input" value="<?php echo htmlspecialchars($pharm_office_name); ?>">
                </div>
                <input type="hidden" name="daysToNextAppointment" value="<?php echo $daysToAppointment; ?>">
                <input type="hidden" name="isMissed" value="<?php echo $isMissed ? 'true' : 'false'; ?>">
                <input type="hidden" name="appointmentType" value="<?php echo htmlspecialchars($appointmentInfo['appointmentType']); ?>">
                <input type="hidden" name="referTo" value="<?php echo htmlspecialchars($appointmentInfo['referTo']); ?>">
            </div>

            <div class="photo-container">
                <?php if ($photoPath && file_exists($photoPath)): ?>
                    <img src="<?php echo $photoPath; ?>" alt="Patient Photo">
                <?php else: ?>
                    <div class="photo-placeholder">
                        <span>No photo available</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="full-width-section">
                <?php if (!empty($otherPrescriptions)): ?>
                <div class="prescriptions-container">
                    <h3>Other Prescriptions (<?php echo $prescriptionCount; ?> Pending)</h3>
                    <table class="prescriptions-table">
                        <thead>
                            <tr>
                                <th>Prescription ID</th>
                                <th>Drug Name</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Days</th>
                                <th>Total Dose</th>
                                <th>Status</th>
                                <th>Quantity</th>
                                <th>Dispense</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $prev_prescription_id = '';
                                foreach ($otherPrescriptions as $index => $drug):
                                    $current_prescription_id = htmlspecialchars($drug['prescription_id']);
                                    $is_new_prescription = ($current_prescription_id !== $prev_prescription_id);
                                    $prev_prescription_id = $current_prescription_id;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($is_new_prescription): ?>
                                        <a href="view_prescription_details.php?id=<?php echo $current_prescription_id; ?>" class="btn btn-sm btn-info" style="font-size: 10px;"><?php echo $current_prescription_id; ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($drug['drugName']); ?></td>
                                <td><?php echo htmlspecialchars($drug['dosage']); ?></td>
                                <td><?php echo htmlspecialchars($drug['routetype']); ?></td>
                                <td><?php echo htmlspecialchars($drug['duration']); ?></td>
                                <td><?php echo htmlspecialchars($drug['totalDose']); ?></td>
                                <td><?php echo htmlspecialchars($drug['prescr_status']); ?></td>
                                <td><input type="number" name="quantity[<?php echo $drug['id']; ?>]" min="0" value="0"></td>
                                <td>
                                    <input type="checkbox" name="dispense[<?php echo $drug['id']; ?>]" value="<?php echo $current_prescription_id; ?>">
                                    <input type="hidden" name="prescription_drug_id[]" value="<?php echo $drug['id']; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <button type="submit" onClick="dispense()" class="submit-btn">Dispense</button>

                <script>
                    function dispense(){	   //alert(99);
                       const xhr = new XMLHttpRequest();
                        xhr.open("GET", "http://192.168.10.6/masterflexapi/pumpapi.php?action=raw&ml=10", true);

                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) {
                                    //console.log("Response:", xhr.responseText);
                                    alert(xhr.responseText);
                                } else {
                                    //console.error("Error:", xhr.status, xhr.responseText);
                                }
                            }
                        };

                        xhr.send();
                   }

                </script>
            </div>
            </div>
    </form>
    </div>

    <div id="customAlert" class="custom-alert">
        <p>CANNOT Dispense unless the patient status is "Active".</p>
        <button onclick="closeAlert()">OK</button>
    </div>

    <script>
        function validateForm() {
            const currentStatus = document.getElementById('current_status').value;
            if (currentStatus !== 'Active') {
                document.getElementById('customAlert').style.display = 'block';
                return false;
            }
            return true;
        }

        function closeAlert() {
            document.getElementById('customAlert').style.display = 'none';
        }
    </script>
</body>
</html>