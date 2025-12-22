<?php
session_start();
include 'includes/config.php';

// In your dispensing.php � replace the whole PumpServiceIntegration class
// In dispensing.php

// In dispensing.php

require_once __DIR__ . '/pump/simple_pump_wrapper.php';

class PumpServiceIntegration {
    private static $pumpWrapper = null;
    private static $initialized = false;

    private static function init() {
        if (!self::$initialized) {
            try {
                self::$pumpWrapper = new SimplePumpWrapper('COM20');
                self::$initialized = true;
                error_log("DISPENSING: Pump wrapper initialized for COM20");
            } catch (Exception $e) {
                error_log("DISPENSING: Failed to initialize pump: " . $e->getMessage());
                self::$pumpWrapper = null;
            }
        }
        return self::$pumpWrapper;
    }

    public static function wakeup() {
        error_log("DISPENSING: Starting pump wakeup sequence...");

        // Add initial delay for USB
        usleep(1500000); // 1.5 seconds

        $wrapper = self::init();
        if (!$wrapper) {
            error_log("DISPENSING: No pump wrapper available");
            return false;
        }

        // Try wakeup with retries
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            error_log("DISPENSING: Wakeup attempt $attempt");

            $result = $wrapper->wakeup();

            if ($result['success']) {
                error_log("DISPENSING: Wakeup successful on attempt $attempt");

                // Extra delay to ensure pump is ready
                sleep(1);
                return true;
            }

            if ($attempt < 3) {
                $delay = $attempt * 3; // 3, 6 seconds
                error_log("DISPENSING: Wakeup failed, retrying in {$delay} seconds...");
                sleep($delay);
            }
        }

        error_log("DISPENSING: All wakeup attempts failed");
        return false;
    }

    public static function dispense($amount_ml) {
        error_log("DISPENSING: Starting dispense of {$amount_ml}ml");

        $wrapper = self::init();
        if (!$wrapper) {
            error_log("DISPENSING: No pump wrapper available");
            return false;
        }

        // First, ensure pump is awake
        if (!self::wakeup()) {
            error_log("DISPENSING: Cannot dispense - wakeup failed");
            return false;
        }

        error_log("DISPENSING: Pump awake, sending dispense command...");

        // Send dispense command
        $result = $wrapper->dispense($amount_ml);

        if ($result['success']) {
            error_log("DISPENSING: Dispense command accepted");

            // Wait for actual dispensing
            $dispenseTime = $amount_ml * 2; // 2 seconds per ml
            error_log("DISPENSING: Waiting {$dispenseTime} seconds for dispensing...");
            sleep($dispenseTime);

            error_log("DISPENSING: Dispense completed successfully");
            return true;
        }

        error_log("DISPENSING: Dispense command failed");
        return false;
    }

    public static function test() {
        $wrapper = self::init();
        if ($wrapper) {
            return $wrapper->test();
        }
        return ['success' => false, 'message' => 'Pump not initialized'];
    }
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