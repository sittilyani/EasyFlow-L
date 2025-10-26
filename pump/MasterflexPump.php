<?php
require_once 'vendor/autoload.php'; // Include Composer autoloader
use PhpSerial;

// Masterflex MMDC03 Pump Configuration
define('PUMP_ENABLED', true); // Set to true for live hardware
define('PUMP_MODEL', 'Masterflex MMDC03');
define('PUMP_SERIAL_PORT', 'COM1'); // Update to your port (e.g., COM3, /dev/ttyUSB0)
define('PUMP_BAUD_RATE', 9600);

// Safety limits
define('MAX_DAILY_DOSAGE_MG', 120); // Maximum daily methadone dosage in mg
define('METHADONE_CONCENTRATION', 10); // mg per ml for methadone

class MasterflexPump {
    private $serial;
    private $connected = false;

    /**
     * Connect to the pump (real or simulated)
     * @return bool True on success, false on failure
     */
    public function connect() {
        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Masterflex MMDC03 connected successfully");
            $this->connected = true;
            return true;
        }

        try {
            $this->serial = new PhpSerial();
            $this->serial->deviceSet(PUMP_SERIAL_PORT);
            $this->serial->confBaudRate(PUMP_BAUD_RATE);
            $this->serial->confParity("none");
            $this->serial->confCharacterLength(8);
            $this->serial->confStopBits(1);
            $this->serial->deviceOpen();

            error_log("PUMP LIVE: Connected to " . PUMP_SERIAL_PORT . " at " . PUMP_BAUD_RATE . " baud");

            // Send initialization command (adjust per manual)
            $this->sendCommand("STOP\r\n");
            $response = $this->readResponse();
            if (strpos($response, 'ACK') === false) { // Adjust based on manual
                error_log("PUMP LIVE: Connection failed, response: $response");
                $this->serial->deviceClose();
                return false;
            }

            $this->connected = true;
            return true;
        } catch (Exception $e) {
            error_log("PUMP LIVE: Connection error: " . $e->getMessage());
            $this->serial->deviceClose();
            $this->connected = false;
            return false;
        }
    }

    /**
     * Dispense the specified amount in milliliters
     * @param float $amount_ml Amount to dispense in ml
     * @return bool True on success, false on failure
     */
    public function dispense($amount_ml) {
        if (!$this->connected) {
            error_log("PUMP ERROR: Not connected");
            return false;
        }

        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Dispensing " . number_format($amount_ml, 2) . " ml on " . PUMP_SERIAL_PORT);
            sleep(3); // Simulate pump delay
            $command = "VOL " . number_format($amount_ml, 2) . "\r\n";
            error_log("PUMP SIMULATION: Would send command: " . trim($command));
            return true;
        }

        try {
            error_log("PUMP LIVE: Dispensing " . number_format($amount_ml, 2) . " ml on " . PUMP_SERIAL_PORT);

            // Send volume command (adjust per manual)
            $this->sendCommand("VOL:ML:" . number_format($amount_ml, 2) . "\r\n");
            $this->sendCommand("RUN 1\r\n"); // Start dispensing

            // Wait for completion (adjust based on flow rate or poll status)
            sleep(5 + ceil($amount_ml * 2)); // 5s base + 2s per ml

            // Stop pump
            $this->sendCommand("RUN 0\r\n");

            // Check response
            $response = $this->readResponse();
            if (strpos($response, 'OK') === false) { // Adjust based on manual
                error_log("PUMP LIVE: Dispense error, response: $response");
                return false;
            }

            error_log("PUMP LIVE: Dispense completed successfully");
            return true;
        } catch (Exception $e) {
            error_log("PUMP LIVE: Dispense error: " . $e->getMessage());
            $this->sendCommand("RUN 0\r\n"); // Ensure pump stops
            return false;
        }
    }

    /**
     * Disconnect from the pump
     */
    public function disconnect() {
        if (PUMP_ENABLED && $this->connected) {
            $this->sendCommand("STOP\r\n");
            $this->serial->deviceClose();
            error_log("PUMP LIVE: Disconnected from " . PUMP_SERIAL_PORT);
            $this->serial = null;
            $this->connected = false;
        } else {
            error_log("PUMP SIMULATION: Masterflex pump disconnected");
        }
    }

    /**
     * Check if pump is ready
     * @return bool True if ready, false otherwise
     */
    public function isReady() {
        if (!PUMP_ENABLED) {
            return true; // Always ready in simulation
        }
        // Add status polling if supported (e.g., "STATUS?\r\n")
        return $this->connected;
    }

    /**
     * Convert dosage from mg to ml for methadone
     * @param float $dosage_mg Dosage in milligrams
     * @return float Dosage in milliliters
     */
    public static function dosageToMl($dosage_mg) {
        return $dosage_mg / METHADONE_CONCENTRATION;
    }

    /**
     * Send a command to the pump
     * @param string $command Command to send
     */
    private function sendCommand($command) {
        if (!PUMP_ENABLED) {
            error_log("PUMP SIMULATION: Would send: " . trim($command));
            return;
        }
        $this->serial->sendMessage($command);
        error_log("PUMP LIVE: Sent command: " . trim($command));
    }

    /**
     * Read response from the pump
     * @return string Response from pump
     */
    private function readResponse() {
        if (!PUMP_ENABLED) {
            return "ACK"; // Simulated response
        }
        $response = $this->serial->readPort();
        return $response ?: "NO_RESPONSE";
    }
}

?>