<?php
session_start();
include "../includes/config.php";

// Pump configuration (you'll need to adjust these based on your pump)
define('PUMP_ENABLED', true); // Set to false for testing without pump
define('PUMP_SERIAL_PORT', '/dev/ttyUSB0'); // Adjust for your system
define('PUMP_BAUD_RATE', 9600);
define('PUMP_COMMAND_DELAY', 1000000); // 1 second delay between commands

// Pump command class
class MethadonePump {
    private $serial;
    private $isConnected = false;

    public function connect() {
        if (!PUMP_ENABLED) {
            return true; // Simulate success when pump is disabled
        }

        try {
            // This is a generic example - you'll need to adjust for your specific pump
            $this->serial = dio_open(PUMP_SERIAL_PORT, O_RDWR | O_NOCTTY | O_NONBLOCK);
            dio_tcsetattr($this->serial, array(
                'baud' => PUMP_BAUD_RATE,
                'bits' => 8,
                'stop' => 1,
                'parity' => 0
            ));
            $this->isConnected = true;
            return true;
        } catch (Exception $e) {
            error_log("Pump connection failed: " . $e->getMessage());
            return false;
        }
    }

    public function dispense($amount_ml) {
        if (!PUMP_ENABLED) {
            error_log("SIMULATION: Dispensing $amount_ml ml of methadone");
            return true; // Simulate success
        }

        if (!$this->isConnected) {
            throw new Exception("Pump not connected");
        }

        try {
            // Example command format - ADJUST THIS FOR YOUR PUMP
            $command = "DISPENSE:" . number_format($amount_ml, 2) . "ML\r\n";

            // Send command to pump
            dio_write($this->serial, $command);
            usleep(PUMP_COMMAND_DELAY);

            // Read response (adjust based on your pump's protocol)
            $response = dio_read($this->serial, 1024);

            // Check if dispense was successful
            if (strpos($response, 'SUCCESS') !== false || strpos($response, 'OK') !== false) {
                error_log("Pump dispensed $amount_ml ml successfully");
                return true;
            } else {
                error_log("Pump dispense failed. Response: " . $response);
                return false;
            }
        } catch (Exception $e) {
            error_log("Pump dispense error: " . $e->getMessage());
            return false;
        }
    }

    public function disconnect() {
        if ($this->isConnected && PUMP_ENABLED) {
            dio_close($this->serial);
            $this->isConnected = false;
        }
    }

    public function isReady() {
        if (!PUMP_ENABLED) return true;

        // Check pump status
        try {
            dio_write($this->serial, "STATUS\r\n");
            usleep(PUMP_COMMAND_DELAY);
            $response = dio_read($this->serial, 1024);
            return (strpos($response, 'READY') !== false);
        } catch (Exception $e) {
            return false;
        }
    }
}

// Function to convert dosage to ml (adjust conversion factor as needed)
function dosageToMl($dosage_mg) {
    // Example: if methadone is 10mg/ml, then 50mg = 5ml
    $concentration_mg_per_ml = 10; // Adjust this based on your methadone concentration
    return $dosage_mg / $concentration_mg_per_ml;
}

// Main dispensing process with pump integration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->begin_transaction();

        // Your existing dispensing logic here
        $mat_id = $_POST['mat_id'];
        $visitDate = $_POST['visitDate'];
        $dosage_mg = $_POST['dosage']; // Assuming this is in mg
        $pharm_officer_name = $_POST['pharm_officer_name'];

        // Convert dosage to ml for the pump
        $dosage_ml = dosageToMl($dosage_mg);

        // Initialize pump
        $pump = new MethadonePump();

        if (!$pump->connect()) {
            throw new Exception("Failed to connect to methadone pump");
        }

        if (!$pump->isReady()) {
            throw new Exception("Methadone pump is not ready");
        }

        // Dispense methadone using pump
        if (!$pump->dispense($dosage_ml)) {
            throw new Exception("Pump dispense failed");
        }

        // If pump dispense successful, save to database
        $query = "INSERT INTO pharmacy (mat_id, visitDate, dosage, pharm_officer_name, dispDate)
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssds', $mat_id, $visitDate, $dosage_mg, $pharm_officer_name);

        if (!$stmt->execute()) {
            throw new Exception("Failed to save dispensing record");
        }

        $stmt->close();

        // Handle other prescriptions if any
        if (isset($_POST['dispense']) && is_array($_POST['dispense'])) {
            foreach ($_POST['dispense'] as $drug_id => $prescription_id) {
                $quantity = $_POST['quantity'][$drug_id] ?? 0;

                if ($quantity > 0) {
                    // Save other drug dispensing record
                    $other_query = "INSERT INTO other_drugs_dispensed (prescription_drug_id, quantity, dispensed_by, dispense_date)
                                   VALUES (?, ?, ?, NOW())";
                    $other_stmt = $conn->prepare($other_query);
                    $other_stmt->bind_param('ids', $drug_id, $quantity, $pharm_officer_name);
                    $other_stmt->execute();
                    $other_stmt->close();
                }
            }
        }

        $conn->commit();
        $pump->disconnect();

        // Log successful transaction
        error_log("Dispensing successful: $dosage_mg mg ($dosage_ml ml) for MAT ID: $mat_id");

        // Redirect with success message
        $_SESSION['success_message'] = "Methadone dispensed successfully: $dosage_mg mg";
        header("Location: dispensingData.php?mat_id=" . urlencode($mat_id));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        if (isset($pump)) {
            $pump->disconnect();
        }

        error_log("Dispensing error: " . $e->getMessage());
        $_SESSION['error_message'] = "Dispensing failed: " . $e->getMessage();
        header("Location: dispensingData.php?mat_id=" . urlencode($mat_id));
        exit();
    }
}
?>