<?php
session_start();
include '../includes/config.php';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}
$conn->set_charset('utf8mb4');

$mat_id = isset($_POST['mat_id']) ? $_POST['mat_id'] : null;

// --- Initialize Result Arrays ---
$routineDispenseSuccess = false;
$otherPrescriptionsSuccess = false;
$errorMessages = [];
$successMessages = [];

// --- Helper function to display messages and redirect ---
function displayMessagesAndRedirect($conn, $successes, $errors, $mat_id) {
    echo "<html><head><title>Dispensing Results</title>";
    echo "<style>
        .success { background-color: #DDFCAF; color: green; font-size: 18px; padding: 15px; margin-bottom: 10px; text-align: center; border-radius: 5px; }
        .error { background-color: #EDFEB0; color: red; padding: 10px; margin-bottom: 10px; border-radius: 5px; font-weight: bold; }
    </style>";
    echo "</head><body>";

    // Display Successes
    foreach ($successes as $message) {
        echo "<div class='success'>$message</div>";
    }

    // Display Errors
    foreach ($errors as $error) {
        echo "<div class='error'>$error</div>";
    }

    // Redirect back to the main dispensing page
    echo "<script>
        setTimeout(function(){
            window.location.href = 'dispensing.php';
        }, 6000);
    </script>";
    echo "</body></html>";
    exit();
}

// Pump Service Configuration
define('PUMP_ENABLED', true);
define('PUMP_SERVICE_PATH', __DIR__ . '/pump_service.php');

// Safety limits
define('MAX_DAILY_DOSAGE_MG', 120);
define('METHADONE_CONCENTRATION', 10);

// Simplified Pump Service Integration
class PumpServiceIntegration {

    public static function addCommand($type, $amount_ml = null) {
        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Command '$type' added" . ($amount_ml ? " for {$amount_ml}ml" : ""));
            return 'simulated_' . uniqid();
        }

        // Include pump service functions
        if (file_exists(PUMP_SERVICE_PATH)) {
            require_once PUMP_SERVICE_PATH;
            return addPumpCommand($type, $amount_ml);
        } else {
            error_log("Pump service file not found: " . PUMP_SERVICE_PATH);
            return false;
        }
    }

    public static function dispense($amount_ml) {
        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Dispensing " . number_format($amount_ml, 2) . " ml");
            sleep(2); // Simulate dispensing time
            return true;
        }

        $commandId = self::addCommand('dispense', $amount_ml);
        if (!$commandId) {
            return false;
        }

        // Wait for command to be processed (simplified - in production, you might want to check results)
        sleep(5 + ($amount_ml * 2));
        return true;
    }

    public static function wakeup() {
        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Pump awakened");
            return true;
        }

        $commandId = self::addCommand('wakeup');
        return (bool)$commandId;
    }
}

// Safety validation functions
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

function dosageToMl($dosage_mg) {
    return $dosage_mg / METHADONE_CONCENTRATION;
}

// Debug: Check what data is being posted
error_log("Dispensing process started. POST data: " . print_r($_POST, true));

// ==============================================================================
// 1. ROUTINE DISPENSING LOGIC (Part 1)
// ==============================================================================

if ($mat_id && isset($_POST['drugname']) && !empty($_POST['drugname'])) {
    error_log("Processing routine dispensing for MAT ID: $mat_id");

    $routineErrors = [];
    $conn->begin_transaction();

    try {
        // Capture form data
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

        // 5. Stock Validation
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

        // If no errors, proceed with insertion and update
        if (empty($routineErrors)) {
            $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param('ssssssssssdsss', $visitDate, $mat_id, $mat_number, $clientName, $nickName, $age, $sex, $p_address, $cso, $drugname, $dosage, $reasons, $current_status, $pharm_officer_name);

            if ($stmt->execute()) {
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

                // If this is methadone and dosage > 0, trigger pump
                if (strtolower($drugname) === 'methadone' && $dosage > 0) {
                    error_log("Methadone dispensing detected. Dosage: {$dosage}mg");

                    // Convert dosage to ml for the pump
                    $dosage_ml = dosageToMl($dosage);
                    error_log("Converted dosage: {$dosage_ml}ml");

                    // Wake up pump first
                    if (PumpServiceIntegration::wakeup()) {
                        error_log("Pump wakeup command sent");

                        // Then dispense
                        if (PumpServiceIntegration::dispense($dosage_ml)) {
                            error_log("PUMP: Successfully dispensed $dosage mg ($dosage_ml ml) for MAT ID: $mat_id");
                            $successMessages[] = "Routine Drug ($drugname) dispensed successfully! (Dosage: $dosage mg) - Pump operation completed.";
                        } else {
                            throw new Exception("Pump dispensing failed for {$dosage_ml}ml");
                        }
                    } else {
                        throw new Exception("Failed to wake up pump");
                    }
                } else {
                    $successMessages[] = "Routine Drug ($drugname) dispensed successfully! (Dosage: $dosage mg)";
                }

                $conn->commit();
                $routineDispenseSuccess = true;
                error_log("Routine dispensing completed successfully for MAT ID: $mat_id");
            } else {
                throw new Exception("Database error inserting routine record: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $conn->rollback();
            error_log("Routine dispensing errors: " . implode(', ', $routineErrors));
        }
    } catch (Exception $e) {
        $conn->rollback();
        $routineErrors[] = "Routine Dispensing Failed (DB/System Error): " . htmlspecialchars($e->getMessage());
        error_log("Routine dispensing exception: " . $e->getMessage());
    }

    $errorMessages = array_merge($errorMessages, $routineErrors);
}

// ==============================================================================
// 2. OTHER PRESCRIPTIONS DISPENSING LOGIC (Part 2)
// ==============================================================================

if (isset($_POST['dispense']) && is_array($_POST['dispense']) && isset($_POST['quantity']) && is_array($_POST['quantity'])) {
    error_log("Processing other prescriptions dispensing");

    $prescriptionsToProcess = [];
    $otherPrescriptionErrors = [];
    $dispensedCount = 0;

    // Filter submitted data
    foreach ($_POST['dispense'] as $drug_id_from_form => $prescription_id) {
        $dispense_id = (int)$drug_id_from_form;
        $quantity = (float)($_POST['quantity'][$dispense_id] ?? 0);

        if ($quantity > 0) {
            $prescriptionsToProcess[$prescription_id][] = [
                'drug_id' => $dispense_id,
                'quantity' => $quantity,
            ];
            $dispensedCount++;
        }
    }

    error_log("Found $dispensedCount drugs to dispense from other prescriptions");

    // Process the dispensing
    if ($dispensedCount > 0) {
        $conn->begin_transaction();

        try {
            $stmt_fetch_drug = $conn->prepare("SELECT total_dosage, total_dispensed, drug_name FROM prescription_drugs WHERE id = ?");
            $stmt_update_drug = $conn->prepare("UPDATE prescription_drugs SET total_dispensed = total_dispensed + ?, remaining_balance = total_dosage - total_dispensed - ?, prescr_status = ? WHERE id = ?");
            $stmt_update_stock = $conn->prepare("UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
            $stmt_update_prescr = $conn->prepare("UPDATE other_prescriptions SET prescr_status = ? WHERE prescription_id = ?");

            $uniquePrescriptionsDispensed = 0;

            foreach ($prescriptionsToProcess as $prescription_id => $drugs) {
                $isPrescriptionComplete = true;

                foreach ($drugs as $drug) {
                    $drug_id = $drug['drug_id'];
                    $dispensed_amount = $drug['quantity'];

                    // Fetch current state
                    $stmt_fetch_drug->bind_param("i", $drug_id);
                    $stmt_fetch_drug->execute();
                    $result_fetch = $stmt_fetch_drug->get_result();
                    $current_drug_data = $result_fetch->fetch_assoc();

                    if (!$current_drug_data) {
                        throw new Exception("Drug ID $drug_id not found in prescription_drugs.");
                    }

                    $total_dosage = (float)$current_drug_data['total_dosage'];
                    $current_dispensed = (float)$current_drug_data['total_dispensed'];
                    $drug_name = $current_drug_data['drug_name'];

                    $remaining_before = $total_dosage - $current_dispensed;

                    // Validation
                    if ($dispensed_amount > $remaining_before) {
                        throw new Exception("Dispensed amount ($dispensed_amount) for **$drug_name** cannot be greater than the remaining balance ($remaining_before).");
                    }

                    // Update prescription_drugs
                    $new_remaining = $remaining_before - $dispensed_amount;
                    $drug_status = ($new_remaining <= 0) ? 'dispensed and closed' : 'partially dispensed';

                    $stmt_update_drug->bind_param("ddsi", $dispensed_amount, $dispensed_amount, $drug_status, $drug_id);
                    $stmt_update_drug->execute();

                    if ($new_remaining > 0) {
                        $isPrescriptionComplete = false;
                    }

                    // Update Stock
                    $stmt_update_stock->bind_param("ds", $dispensed_amount, $drug_name);
                    $stmt_update_stock->execute();
                }

                // Update other_prescriptions status
                $new_prescr_status = $isPrescriptionComplete ? 'dispensed' : 'partially dispensed';
                $stmt_update_prescr->bind_param("ss", $new_prescr_status, $prescription_id);
                $stmt_update_prescr->execute();

                $uniquePrescriptionsDispensed++;
            }

            $conn->commit();
            $otherPrescriptionsSuccess = true;
            $successMessages[] = "Other Prescriptions Dispensing Successful! **$uniquePrescriptionsDispensed** unique prescriptions updated with **$dispensedCount** drug items dispensed.";
            error_log("Other prescriptions dispensing completed successfully");

        } catch (Exception $e) {
            $conn->rollback();
            $otherPrescriptionErrors[] = "Other Prescriptions Dispensing Failed (Error in dispensing process): " . htmlspecialchars($e->getMessage());
            error_log("Other prescriptions dispensing error: " . $e->getMessage());
        } finally {
            $stmt_fetch_drug->close();
            $stmt_update_drug->close();
            $stmt_update_stock->close();
            $stmt_update_prescr->close();
        }
    }

    $errorMessages = array_merge($errorMessages, $otherPrescriptionErrors);
}

// ==============================================================================
// 3. FINAL MESSAGE DISPLAY AND REDIRECT
// ==============================================================================

if (empty($errorMessages) && empty($successMessages)) {
    $errorMessages[] = "No routine drug data submitted or no 'Other Prescriptions' selected for dispensing. Nothing was processed.";
    error_log("No data processed - empty submission");
}

error_log("Final results - Successes: " . count($successMessages) . ", Errors: " . count($errorMessages));

// Display collected messages and redirect
displayMessagesAndRedirect($conn, $successMessages, $errorMessages, $mat_id);

$conn->close();
?>