<?php
session_start();
include 'includes/config.php';

// Safety limits
define('MAX_DAILY_DOSAGE_MG', 120);
define('METHADONE_CONCENTRATION', 10);

$routineErrors = [];
$successMessages = [];

$conn->begin_transaction();

function validateDosageSafety($dosage_mg, $mat_id, $conn) {
    // Check maximum dosage limit
    if ($dosage_mg > MAX_DAILY_DOSAGE_MG) {
        throw new Exception("Dosage exceeds maximum safe limit of " . MAX_DAILY_DOSAGE_MG . " mg");
    }

    // Check for duplicate dispensing on same day
    $check_query = "SELECT COUNT(*) as count FROM pharmacy WHERE mat_id = ? AND visitDate = CURDATE()";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('s', $mat_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();

    if ($row['count'] > 0) {
        throw new Exception("Patient has already been dispensed today");
    }

    return true;
}

$visitDate = $_POST['visitDate'] ?? '';
$DaysToNextAppointment = $_POST['daysToNextAppointment'] ?? 0;
$isMissed = ($_POST['isMissed'] ?? 'false') === 'true';
$mat_number = $_POST['mat_number'] ?? '';
$clientName = $_POST['clientName'] ?? '';
$nickName = $_POST['nickName'] ?? '';
$age = $_POST['age'] ?? '';
$sex = $_POST['sex'] ?? '';
$p_address = $_POST['p_address'] ?? '';
$cso = $_POST['cso'] ?? '';
$drugname = $_POST['drugname'] ?? '';
$dosage = (float)($_POST['dosage'] ?? 0);
$reasons = $_POST['reasons'] ?? '';
$current_status = $_POST['current_status'] ?? '';
$pharm_officer_name = $_POST['pharm_officer_name'] ?? '';
$mat_id = $_POST['mat_id'] ?? '';
$pump_device = isset($_POST['pump_device']) ? (int)$_POST['pump_device'] : null;

$pump_port = null;

try {
    // 1. Restrict submission if `current_status` is not "Active"
    if ($current_status !== "Active") {
        $routineErrors[] = "Routine Dispensing Failed: Client status is not 'Active'.";
    }

    // 2. Duplicate Entry Check
    $checkQuery = "SELECT * FROM pharmacy WHERE mat_id = ? AND visitDate = CURDATE()";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('s', $mat_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $routineErrors[] = "Routine Dispensing Failed: Client with mat_id **$mat_id** already dispensed today.";
    }
    $checkStmt->close();

    // 3. Missed Appointment Check
    if ($isMissed || $DaysToNextAppointment == 0) {
        $routineErrors[] = "Routine Dispensing Failed: Client has a **Missed Appointment** or **No appointment date**. Kindly refer to the clinician.";
    }

    // 4. Dosage Validation
    if ($dosage <= 0) {
        $routineErrors[] = "Routine Dispensing Failed: Can't dispense 0 or negative doses for **$drugname**.";
    }

    // 5. Device pump quantity check
    $pumpQuery = "SELECT
        pd.label,
        pd.port,
        (
            (SELECT new_milligrams FROM pump_reservoir_history WHERE pump_id = pd.id AND `topup_to` IS NULL ORDER BY created_at DESC) -
            (SELECT COALESCE(SUM(dosage), 0) FROM pharmacy WHERE pump_id = pd.id AND dispDate >= (SELECT `topup_from` FROM pump_reservoir_history WHERE pump_id = pd.id AND `topup_to` IS NULL ORDER BY created_at DESC))
        ) AS rem
        FROM pump_devices pd WHERE pd.id = ?";
    $pumpStmt = $conn->prepare($pumpQuery);
    $pumpStmt->bind_param('i', $pump_device);
    $pumpStmt->execute();
    $pumpResult = $pumpStmt->get_result();
    $pumpRow = $pumpResult->fetch_assoc();
    $remainingQuantity = $pumpRow['rem'] ?? 0;
    $pumpLabel = $pumpRow['label'];
    $pump_port = $pumpRow['port'];

    if ($remainingQuantity <= $dosage) {
        $routineErrors[] = "Routine Dispensing Failed: **$drugname** pump **$pumpLabel ($pump_port)** has only **$remainingQuantity** remaining (Required at least: " . ($dosage + 10) . ").";
    }

    // 6. Stock Validation
    $stockQuery = "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
    $stockStmt = $conn->prepare($stockQuery);
    $stockStmt->bind_param('s', $drugname);
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();
    $currentStock = 0;
    if ($stockResult->num_rows > 0) {
        $stockRow = $stockResult->fetch_assoc();
        $currentStock = (float)$stockRow['total_qty'];
    }
    $stockStmt->close();

    if ($currentStock < $dosage) {
        $routineErrors[] = "Routine Dispensing Failed: **$drugname** is **OUT OF STOCK** (Current: $currentStock, Required: $dosage).";
    }

    // Additional safety validation
    if (empty($routineErrors)) {
        validateDosageSafety($dosage, $mat_id, $conn);
    }

    if (!empty($routineErrors)) {
        throw new Exception(implode(', ', $routineErrors));
    }

    // Executing pump command

    $ml = (($dosage / 5) + 5) * 400;
    $pump_cmd = "/1m50h10j4V1600L400z{$ml}D{$ml}R";
    $command = "pumpAPI.exe $pump_port 9600 raw $pump_cmd";

    $output = [];
    $return_var = 0;

    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        throw new Exception("Pump call failed with result code $return_var:\n" . implode('\n', $output));
    }

    $insertQuery = "
        INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name, pump_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
    ";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('ssssssssssdsssi', $visitDate, $mat_id, $mat_number, $clientName, $nickName, $age, $sex, $p_address, $cso, $drugname, $dosage, $reasons, $current_status, $pharm_officer_name, $pump_device);

    if (!$stmt->execute()) {
        throw new Exception("Database error inserting routine record: " . $stmt->error);
    }

    // Update stock quantity
    $updateStockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
    $updateStockStmt = $conn->prepare($updateStockQuery);
    $updateStockStmt->bind_param('ds', $dosage, $drugname);
    $updateStockStmt->execute();
    $updateStockStmt->close();

    // Update the patient's current status to "Active"
    $updateStatusQuery = "UPDATE patients SET current_status = 'Active' WHERE mat_id = ?";
    $updateStatusStmt = $conn->prepare($updateStatusQuery);
    $updateStatusStmt->bind_param('s', $mat_id);
    $updateStatusStmt->execute();
    $updateStatusStmt->close();

    $successMessages[] = "Routine Drug ($drugname) dispensed successfully! (Dosage: $dosage mg)";

    $conn->commit();
    $routineDispenseSuccess = true;

    $stmt->close();


    $_SESSION['successMessages'] = $successMessages;
    header("Location: pharmacy/dispensing_pump.php");
    exit;
} catch(Exception $e) {
    $conn->rollback();
    $routineErrors[] = "Routine Dispensing Failed (DB/System Error): " . $e->getMessage();

    $_SESSION['errorMessages'] = $routineErrors;
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit;
}





