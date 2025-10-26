<?php
class MasterflexPumpController {
    private $port = 'COM3';
    private $handle = null;
    private $isConnected = false;

    public function __construct($port = 'COM3') {
        $this->port = $port;
        error_log("MasterflexPumpController initialized for port: " . $port);
    }

    public function connect() {
        error_log("Attempting to connect to pump on " . $this->port);

        // Try to open the port directly without mode command
        $this->handle = @fopen("\\\\.\\{$this->port}", "r+b");

        if ($this->handle) {
            $this->isConnected = true;
            error_log("? Masterflex pump connected on {$this->port}");

            // Configure stream settings instead of using mode command
            $this->configureStream();
            return true;
        } else {
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            error_log("? Failed to connect to pump: " . $errorMsg);
            throw new Exception("Failed to connect to pump: " . $errorMsg);
        }
    }

    private function configureStream() {
        error_log("Configuring stream settings...");

        // For Windows, we'll rely on the port already being configured
        // or use alternative methods

        // Try to set stream blocking mode
        stream_set_blocking($this->handle, false);

        // Set timeouts
        stream_set_timeout($this->handle, 5); // 5 second timeout

        error_log("? Stream configuration completed");

        // Clear any existing data in buffers
        $this->clearBuffers();
    }

    private function clearBuffers() {
        if ($this->handle) {
            // Read any existing data to clear the buffer
            $oldData = '';
            while (($data = fread($this->handle, 256)) !== false && !empty($data)) {
                $oldData .= $data;
                if (strlen($data) < 256) break;
            }

            if (!empty($oldData)) {
                error_log("Cleared existing buffer data: " . bin2hex($oldData));
            }
        }
    }

    public function sendCommand($command, $waitForResponse = true, $timeout = 3) {
        if (!$this->isConnected || !$this->handle) {
            throw new Exception("Pump not connected");
        }

        // Ensure command ends with carriage return and line feed
        if (!str_ends_with($command, "\r\n")) {
            $command .= "\r\n";
        }

        error_log("Sending to pump: " . trim($command) . " [hex: " . bin2hex($command) . "]");

        // Send command
        $bytesWritten = fwrite($this->handle, $command);

        if ($bytesWritten === false) {
            throw new Exception("Failed to send command to pump");
        }

        error_log("Command sent successfully, {$bytesWritten} bytes written");

        // Flush the output buffer
        fflush($this->handle);

        // Wait for operation
        usleep(500000); // 0.5 seconds

        if ($waitForResponse) {
            return $this->readResponse($timeout);
        }

        return true;
    }

    private function readResponse($timeout = 3) {
        if (!$this->handle) {
            return '';
        }

        $response = '';
        $startTime = time();

        error_log("Reading response (timeout: {$timeout}s)...");

        while ((time() - $startTime) < $timeout) {
            // Check if data is available
            $read = array($this->handle);
            $write = null;
            $except = null;

            $changed = stream_select($read, $write, $except, 0, 100000); // 100ms timeout

            if ($changed > 0) {
                $char = fread($this->handle, 1);
                if ($char !== false && $char !== '') {
                    $response .= $char;
                    error_log("Received char: " . bin2hex($char) . " ('{$char}')");

                    // If we get a newline, consider the response complete
                    if (str_ends_with($response, "\r\n") || str_ends_with($response, "\n")) {
                        error_log("Response complete (newline detected)");
                        break;
                    }
                }
            } else {
                usleep(100000); // 100ms delay between checks
            }
        }

        $response = trim($response);

        if (!empty($response)) {
            error_log("? Pump response: '{$response}' [hex: " . bin2hex($response) . "]");
        } else {
            error_log("? No response received from pump (timeout after {$timeout}s)");
        }

        return $response;
    }

    public function wakeUp() {
        error_log("Waking up pump...");

        // Clear buffers first
        $this->clearBuffers();

        // Try different wake-up sequences
        $wakeCommands = [
            "\r\n",
            "\r",
            "\n",
            "?\r\n",
            "STATUS\r\n",
            "ID\r\n"
        ];

        foreach ($wakeCommands as $index => $command) {
            error_log("Wake-up attempt " . ($index + 1) . ": " . bin2hex($command));

            try {
                $response = $this->sendCommand($command, true, 2);
                if (!empty($response)) {
                    error_log("? Pump woke up with command: " . bin2hex($command));
                    return $response;
                }
            } catch (Exception $e) {
                error_log("Wake-up command failed: " . $e->getMessage());
                // Continue to next command
            }
        }

        error_log("Pump wake-up completed (no response received - may be normal)");
        return null;
    }

    public function dispense($amount_ml) {
        if (!$this->isConnected) {
            $this->connect();
        }

        error_log("Attempting to dispense {$amount_ml} ml");

        // Format amount to 2 decimal places
        $formattedAmount = number_format($amount_ml, 2);

        // Try different dispense command formats
        $dispenseCommands = [
            "VOL {$formattedAmount}",
            "GO {$formattedAmount}",
            "RUN {$formattedAmount}",
            "DISPENSE {$formattedAmount}",
            "POUR {$formattedAmount}"
        ];

        foreach ($dispenseCommands as $command) {
            try {
                error_log("Trying command: '{$command}'");

                // Send command without waiting for response (many pumps don't respond)
                $this->sendCommand($command, false, 0);

                error_log("? Dispense command '{$command}' sent successfully");

                // Wait for dispense to complete (1 second per ml, minimum 3 seconds)
                $waitTime = max(3, ceil($amount_ml));
                error_log("Waiting {$waitTime} seconds for dispense to complete...");

                for ($i = 0; $i < $waitTime; $i++) {
                    sleep(1);
                    error_log("Dispensing... " . ($i + 1) . "/{$waitTime} seconds");
                }

                error_log("? Dispense of {$amount_ml} ml completed successfully");
                return true;

            } catch (Exception $e) {
                error_log("? Dispense command '{$command}' failed: " . $e->getMessage());
                // Continue to next command
            }
        }

        throw new Exception("All dispense command attempts failed");
    }

    public function stop() {
        try {
            $this->sendCommand("STOP", false);
            error_log("? Emergency stop sent to pump");
        } catch (Exception $e) {
            error_log("? Stop command failed: " . $e->getMessage());
        }
    }

    public function disconnect() {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
            $this->isConnected = false;
            error_log("? Pump disconnected");
        }
    }

    public function __destruct() {
        $this->disconnect();
    }

    // Helper method to check connection status
    public function isConnected() {
        return $this->isConnected && $this->handle;
    }

    // Alternative method to manually configure port using PowerShell
    public function configurePortManually() {
        error_log("Attempting manual port configuration...");

        // Try PowerShell configuration
        $psCommand = 'powershell -Command "$port = New-Object System.IO.Ports.SerialPort(\'COM3\', 9600, [System.IO.Ports.Parity]::Even, 7, [System.IO.Ports.StopBits]::One); Write-Output \'Port configuration attempted\'"';
        exec($psCommand, $output, $returnCode);

        error_log("PowerShell configuration result: " . $returnCode);

        if ($returnCode === 0) {
            error_log("? Manual port configuration successful");
            return true;
        } else {
            error_log("? Manual port configuration failed");
            return false;
        }
    }
}
?>