<?php

// Masterflex MMDC03 Pump Configuration
define('PUMP_ENABLED', false); // Set to true for live hardware, false for simulation
define('PUMP_MODEL', 'Masterflex MMDC03');
define('PUMP_SERIAL_PORT', 'COM1'); // Adjust for your system (e.g., /dev/ttyUSB0 on Linux)
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
            $port = PUMP_SERIAL_PORT;
            $baud = PUMP_BAUD_RATE;

            // Configure serial port based on OS
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("mode $port: BAUD=$baud PARITY=N DATA=8 STOP=1");
            } else {
                exec("stty -F $port $baud cs8 -parenb -cstopb");
            }

            // Store port for communication
            $this->serial = $port;
            error_log("PUMP LIVE: Attempting to connect to $port at $baud baud");

            // Send initialization command (adjust per manual)
            $this->sendCommand("STOP\r\n");

            // Read response (expecting ACK or similar)
            $response = $this->readResponse();
            if (strpos($response, 'ACK') === false) { // Adjust based on actual pump response
                error_log("PUMP LIVE: Connection failed, invalid response: $response");
                return false;
            }

            $this->connected = true;
            error_log("PUMP LIVE: Connected successfully");
            return true;
        } catch (Exception $e) {
            error_log("PUMP LIVE: Connection error: " . $e->getMessage());
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

            // Wait for completion (adjust delay based on flow rate or poll status)
            sleep(5 + ceil($amount_ml * 2)); // Rough estimate: 5s base + 2s per ml

            // Stop pump
            $this->sendCommand("RUN 0\r\n");

            // Check response
            $response = $this->readResponse();
            if (strpos($response, 'OK') === false) { // Adjust based on actual pump response
                error_log("PUMP LIVE: Dispense error, response: $response");
                return false;
            }

            error_log("PUMP LIVE: Dispense completed successfully");
            return true;
        } catch (Exception $e) {
            error_log("PUMP LIVE: Dispense error: " . $e->getMessage());
            $this->sendCommand("RUN 0\r\n"); // Ensure pump stops on error
            return false;
        }
    }

    /**
     * Disconnect from the pump
     */
    public function disconnect() {
        if (PUMP_ENABLED && $this->connected) {
            $this->sendCommand("STOP\r\n");
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
        // Add status polling if supported (e.g., send "STATUS?\r\n")
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
            return;
        }
        // Basic serial write using exec; replace with php-serial.class.php for better control
        $cmd = addslashes($command);
        exec("echo '$cmd' > " . $this->serial);
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
        // Placeholder: Implement actual serial read
        // Example: exec("timeout 1 cat " . $this->serial); for Linux
        // For now, return dummy response
        sleep(1);
        return "ACK"; // Adjust based on manual
    }
}

?>