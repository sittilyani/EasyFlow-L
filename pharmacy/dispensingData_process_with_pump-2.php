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
    echo "<html><head><title>Dispensing Results</title></head><body>";

    // Display Successes
    foreach ($successes as $message) {
        echo "<div style='background-color: #DDFCAF; color: green; font-size: 18px; padding: 15px; margin-bottom: 10px; text-align: center; border-radius: 5px;'>$message</div>";
    }

    // Display Errors
    foreach ($errors as $error) {
        echo "<div style='background-color: #EDFEB0; color: red; padding: 10px; margin-bottom: 10px; border-radius: 5px; font-weight: bold;'>$error</div>";
    }

    // Redirect back to the main dispensing page
    echo "<script>setTimeout(function(){ window.location.href = 'dispensing.php'; }, 6000);</script>";
    echo "</body></html>";
    exit();
}

// Masterflex MMDC03 Pump Configuration - SIMULATION MODE
define('PUMP_ENABLED', false); // Set to false for testing without pump
define('PUMP_MODEL', 'Masterflex MMDC03');
define('PUMP_SERIAL_PORT', 'COM1');
define('PUMP_BAUD_RATE', 9600);

// Safety limits
define('MAX_DAILY_DOSAGE_MG', 120);
define('METHADONE_CONCENTRATION', 10);

// Pump Simulation Class
class MasterflexPump {
    public function connect() {
        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Masterflex MMDC03 connected successfully");
            return true;
        }

        // For now, just simulate connection
        error_log("PUMP SIMULATION: Would connect to " . PUMP_SERIAL_PORT);
        return true;
    }

    public function dispense($amount_ml) {
        error_log("PUMP SIMULATION: Dispensing " . number_format($amount_ml, 2) . " ml on " . PUMP_SERIAL_PORT);

        // Simulate pump operation delay
        sleep(3);

        // Log the command that would be sent
        $command = "VOL " . number_format($amount_ml, 2) . "\r\n";
        error_log("WOULD SEND COMMAND: " . trim($command));

        return true; // Always succeed in simulation
    }

    public function disconnect() {
        error_log("PUMP SIMULATION: Masterflex pump disconnected");
    }

    public function isReady() {
        return true;
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

// ==============================================================================
// 1. ROUTINE DISPENSING LOGIC (Part 1)
// Executed only if mat_id and drugname are set (assumes primary MAT drug)
// ==============================================================================

if ($mat_id && isset($_POST['drugname']) && !empty($_POST['drugname'])) {

    $routineErrors = []; // Local error store for this transaction
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE); // Start transaction for Part 1

    try {
        // Capture form data and sanitize
        $visitDate = $_POST['visitDate'];
        $DaysToNextAppointment = $_POST['daysToNextAppointment'];
        $isMissed = $_POST['isMissed'] === 'true';
        $mat_number = $_POST['mat_number'];
        $clientName = $_POST['clientName'];
        $nickName = $_POST['nickName'];
        $age = $_POST['age'];
        $sex = $_POST['sex'];
        $p_address = $_POST['p_address'];
        $cso = $_POST['cso'];
        $drugname = $_POST['drugname'];
        $dosage = (float)$_POST['dosage'];
        $reasons = $_POST['reasons'];
        $current_status = $_POST['current_status'];
        $pharm_officer_name = $_POST['pharm_officer_name'];

        // 1. Restrict submission if `current_status` is not "Active"
        if ($current_status !== "Active") {
            $routineErrors[] = "Routine Dispensing Failed: Client status is not 'Active'.";
        }

        // 2. Duplicate Entry Check (Dispensing today)
        $checkQuery = "SELECT * FROM pharmacy WHERE mat_id = ? AND visitDate = CURDATE()";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('s', $mat_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $routineErrors[] = "Routine Dispensing Failed: Client with mat_id **$mat_id** already dispensed today.";
        }
        $checkStmt->close();

        // 3. Missed Appointment Check (Now a warning, but still blocks dispensing if logic is strict)
        if ($isMissed || $DaysToNextAppointment == 0) {
            // Note: The original code used this as a hard block. Keeping it as a block.
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

        // Additional safety validation for pump-eligible drugs
        validateDosageSafety($dosage, $mat_id, $conn);

        // If no errors, proceed with insertion and update
        if (empty($routineErrors)) {
            $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param('ssssssssssssss', $visitDate, $mat_id, $mat_number, $clientName, $nickName, $age, $sex, $p_address, $cso, $drugname, $dosage, $reasons, $current_status, $pharm_officer_name);

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

                // If this is methadone and dosage > 0, trigger pump (inside transaction for rollback on failure)
                if (strtolower($drugname) === 'methadone' && $dosage > 0) {
                    $pump = new MasterflexPump();

                    if ($pump->connect()) {
                        // Convert dosage to ml for the pump
                        $dosage_ml = dosageToMl($dosage);

                        if ($pump->isReady()) {
                            if ($pump->dispense($dosage_ml)) {
                                error_log("PUMP SIMULATION: Successfully dispensed $dosage mg ($dosage_ml ml) for MAT ID: $mat_id");
                                $successMessages[] = "Routine Drug ($drugname) dispensed successfully! (Dosage: $dosage) - Pump simulation completed.";
                            } else {
                                throw new Exception("Pump dispensing failed");
                            }
                        } else {
                            throw new Exception("Pump is not ready");
                        }
                        $pump->disconnect();
                    } else {
                        throw new Exception("Failed to connect to pump");
                    }
                } else {
                    $successMessages[] = "Routine Drug ($drugname) dispensed successfully! (Dosage: $dosage)";
                }

                $conn->commit();
                $routineDispenseSuccess = true;
            } else {
                throw new Exception("Database error inserting routine record: " . $stmt->error);
            }
            $stmt->close();
        } else {
             // Rollback if there were logical errors
             $conn->rollback();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $routineErrors[] = "Routine Dispensing Failed (DB/System Error): " . htmlspecialchars($e->getMessage());
    }

    // Append routine errors to the main error list
    $errorMessages = array_merge($errorMessages, $routineErrors);
}


// ==============================================================================
// 2. OTHER PRESCRIPTIONS DISPENSING LOGIC (Part 2)
// Executed only if 'dispense' and 'quantity' arrays are submitted
// ==============================================================================

if (isset($_POST['dispense']) && is_array($_POST['dispense']) && isset($_POST['quantity']) && is_array($_POST['quantity'])) {

    $prescriptionsToProcess = []; // Stores unique prescription IDs that had at least one drug dispensed
    $otherPrescriptionErrors = [];
    $dispensedCount = 0;

    // Filter submitted data to only include items marked for dispensing
    foreach ($_POST['dispense'] as $drug_id_from_form => $prescription_id) {
        $dispense_id = (int)$drug_id_from_form; // This is the ID from 'prescription_drugs' table
        $quantity = (float)($_POST['quantity'][$dispense_id] ?? 0);

        // Only process if quantity is greater than zero and drug was checked/present
        if ($quantity > 0) {
            $prescriptionsToProcess[$prescription_id][] = [
                'drug_id' => $dispense_id,
                'quantity' => $quantity,
                // Fetch existing total_dosage from DB for validation and remaining balance calculation
            ];
            $dispensedCount++;
        }
    }

    // Process the dispensing in a single transaction for atomicity
    if ($dispensedCount > 0) {
        $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

        try {
            // Prepare statements outside the loop
            $stmt_fetch_drug = $conn->prepare("SELECT total_dosage, total_dispensed, drug_name FROM prescription_drugs WHERE id = ?");
            $stmt_update_drug = $conn->prepare("UPDATE prescription_drugs SET total_dispensed = total_dispensed + ?, remaining_balance = total_dosage - total_dispensed - ?, prescr_status = ? WHERE id = ?");
            $stmt_update_stock = $conn->prepare("UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
            $stmt_update_prescr = $conn->prepare("UPDATE other_prescriptions SET prescr_status = ? WHERE prescription_id = ?");

            $uniquePrescriptionsDispensed = 0;

            foreach ($prescriptionsToProcess as $prescription_id => $drugs) {
                $isPrescriptionComplete = true; // Assume complete until proven otherwise

                foreach ($drugs as $drug) {
                    $drug_id = $drug['drug_id'];
                    $dispensed_amount = $drug['quantity'];

                    // A. Fetch current state (total_dosage, total_dispensed, drug_name)
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

                    // B. Validation
                    if ($dispensed_amount > $remaining_before) {
                        throw new Exception("Dispensed amount ($dispensed_amount) for **$drug_name** cannot be greater than the remaining balance ($remaining_before).");
                    }

                    // C. Determine new status and update prescription_drugs
                    $new_remaining = $remaining_before - $dispensed_amount;
                    $drug_status = ($new_remaining <= 0) ? 'dispensed and closed' : 'partially dispensed';

                    $stmt_update_drug->bind_param("ddsi", $dispensed_amount, $dispensed_amount, $drug_status, $drug_id);
                    $stmt_update_drug->execute();

                    if ($new_remaining > 0) {
                        $isPrescriptionComplete = false; // Mark prescription as partially complete
                    }

                    // D. Update Stock
                    $stmt_update_stock->bind_param("ds", $dispensed_amount, $drug_name);
                    $stmt_update_stock->execute();
                }

                // E. Update other_prescriptions status after processing all drugs for this prescription
                $new_prescr_status = $isPrescriptionComplete ? 'dispensed' : 'partially dispensed';
                $stmt_update_prescr->bind_param("ss", $new_prescr_status, $prescription_id);
                $stmt_update_prescr->execute();

                $uniquePrescriptionsDispensed++;
            }

            // All successful: Commit the transaction
            $conn->commit();
            $otherPrescriptionsSuccess = true;
            $successMessages[] = "Other Prescriptions Dispensing Successful! **$uniquePrescriptionsDispensed** unique prescriptions updated with **$dispensedCount** drug items dispensed.";

        } catch (Exception $e) {
            $conn->rollback();
            $otherPrescriptionErrors[] = "Other Prescriptions Dispensing Failed (Error in dispensing process): " . htmlspecialchars($e->getMessage());
        } finally {
            // Close prepared statements for Part 2
            $stmt_fetch_drug->close();
            $stmt_update_drug->close();
            $stmt_update_stock->close();
            $stmt_update_prescr->close();
        }
    }

    // Append other prescription errors to the main error list
    $errorMessages = array_merge($errorMessages, $otherPrescriptionErrors);
}


// ==============================================================================
// 3. FINAL MESSAGE DISPLAY AND REDIRECT
// ==============================================================================

if (empty($errorMessages) && empty($successMessages)) {
    // This happens if the form was submitted but nothing was selected/required
    $errorMessages[] = "No routine drug data submitted or no 'Other Prescriptions' selected for dispensing. Nothing was processed.";
}

// Display collected messages (successes and errors) and redirect
displayMessagesAndRedirect($conn, $successMessages, $errorMessages, $mat_id);

$conn->close();
?>