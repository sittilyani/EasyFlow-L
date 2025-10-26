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
define('PUMP_ENABLED', true); // Set to true for testing with pump
define('PUMP_MODEL', 'Masterflex MMDC03');
define('PUMP_SERIAL_PORT', 'COM1');
define('PUMP_BAUD_RATE', 9600);

// Safety limits
define('MAX_DAILY_DOSAGE_MG', 120);
define('METHADONE_CONCENTRATION', 10);

// In the MasterflexPump class, update connect() and dispense() for real serial:

    class MasterflexPump {
        private $serial;

        public function connect() {
            if (!PUMP_ENABLED) {
                error_log("PUMP SIMULATION: Masterflex MMDC03 connected successfully");
                return true;
            }

            // Real serial connection (requires php_serial or exec to stty/minicom)
            // Option 1: Use PHP's built-in (if compiled with --enable-serial, rare)
            // Or better: Use exec() to interact via command line (cross-platform-ish)

            // Example using exec (test on your OS):
            $port = PUMP_SERIAL_PORT; // 'COM1' on Win, '/dev/ttyUSB0' on Linux
            $baud = PUMP_BAUD_RATE;

            // Set serial params (Windows example; adjust for Linux/Mac with stty)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("mode $port: BAUD=$baud PARITY=N DATA=8 STOP=1");
            } else {
                exec("stty -F $port $baud cs8 -parenb -cstopb");
            }

            // Open port (simulate with file or use a lib like php-serial.class.php)
            // For now, assume a simple exec-based send/receive
            $this->serial = $port;
            error_log("PUMP LIVE: Connected to $port at $baud baud");

            // Send init command
            $this->sendCommand("STOP\r\n"); // Reset pump

            // Read response (e.g., expect ACK)
            $response = $this->readResponse();
            if (strpos($response, 'ACK') === false) { // Adjust based on manual
                return false;
            }

            return true;
        }

        public function dispense($amount_ml) {
            if (!PUMP_ENABLED) {
                // ... existing simulation ...
                return true;
            }

            error_log("PUMP LIVE: Dispensing " . number_format($amount_ml, 2) . " ml on " . PUMP_SERIAL_PORT);

            // Send real commands (update based on manual)
            $this->sendCommand("VOL:ML:" . number_format($amount_ml, 2) . "\r\n");
            $this->sendCommand("RUN 1\r\n"); // Start dispense

            // Wait for completion (poll status or fixed delay)
            sleep(5 + ($amount_ml * 2)); // Rough estimate; better to poll

            $this->sendCommand("RUN 0\r\n"); // Stop

            $response = $this->readResponse();
            if (strpos($response, 'OK') === false) { // Adjust per manual
                error_log("PUMP LIVE: Error response: $response");
                return false;
            }

            error_log("PUMP LIVE: Dispense complete");
            return true;
        }

        private function sendCommand($cmd) {
            // Example: exec("echo '$cmd' > $this->serial");
            // On Windows: exec("echo $cmd > $port");
            // Implement based on OS; test with a terminal first (e.g., PuTTY to send VOL command manually)
            exec("echo '" . addslashes($cmd) . "' > " . $this->serial); // Basic; escape properly
            error_log("PUMP LIVE: Sent: $cmd");
        }

        private function readResponse() {
            // Example: exec("cat $this->serial"); or timeout read
            // Placeholder: return simulated response for now
            sleep(1);
            return "ACK"; // Replace with real read
        }

        public function disconnect() {
            if (PUMP_ENABLED) {
                $this->sendCommand("STOP\r\n");
                // Close port
                error_log("PUMP LIVE: Disconnected");
            } else {
                error_log("PUMP SIMULATION: Masterflex pump disconnected");
            }
        }

        public function isReady() {
            // Poll status command, e.g., send "STATUS?\r\n" and check
            return PUMP_ENABLED ? true : true; // Stub; implement
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