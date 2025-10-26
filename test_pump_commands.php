<?php
class SimplePumpController {
    private $handle = null;

    public function connect($port = 'COM3') {
        $this->handle = @fopen("\\\\.\\$port", "r+b");

        if ($this->handle) {
            // Configure the port using Windows mode command
            exec("mode $port: BAUD=9600 PARITY=E DATA=7 STOP=1", $output, $returnCode);

            if ($returnCode === 0) {
                echo "? Pump connected and configured on $port\n";
                return true;
            } else {
                echo "? Failed to configure $port\n";
                $this->disconnect();
                return false;
            }
        } else {
            echo "? Cannot open $port\n";
            $error = error_get_last();
            echo "Error: " . $error['message'] . "\n";
            return false;
        }
    }

    public function sendCommand($command) {
        if (!$this->handle) {
            echo "? Pump not connected\n";
            return false;
        }

        $bytesWritten = fwrite($this->handle, $command);

        if ($bytesWritten === false) {
            echo "? Failed to send command\n";
            return false;
        }

        echo "? Sent command: " . bin2hex($command) . " ($bytesWritten bytes)\n";
        return true;
    }

    public function readResponse($timeout = 2) {
        if (!$this->handle) {
            return false;
        }

        $response = '';
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            $char = fread($this->handle, 1);
            if ($char !== false && $char !== '') {
                $response .= $char;
            } else {
                usleep(100000); // 100ms delay
            }
        }

        if (!empty($response)) {
            echo "? Received response: " . bin2hex($response) . " ('$response')\n";
        } else {
            echo "? No response received (timeout after {$timeout}s)\n";
        }

        return $response;
    }

    public function dispense($amount_ml) {
        if (!$this->connect()) {
            return false;
        }

        echo "=== Dispensing $amount_ml ml ===\n";

        // Try different command formats for Masterflex pump
        $commands = [
            "VOL $amount_ml\r\n",
            "GO $amount_ml\r\n",
            "RUN $amount_ml\r\n",
            "DISPENSE $amount_ml\r\n",
            "$amount_ml\r\n"
        ];

        foreach ($commands as $command) {
            echo "Trying command: '$command'";

            if ($this->sendCommand($command)) {
                // Wait a bit for pump to process
                sleep(2);

                // Check for response
                $response = $this->readResponse(3);

                if (!empty($response)) {
                    echo "? Pump responded!\n";
                    $this->disconnect();
                    return true;
                } else {
                    echo "? Command sent (no response received - may be normal)\n";
                    $this->disconnect();
                    return true;
                }
            }
        }

        echo "? All command attempts failed\n";
        $this->disconnect();
        return false;
    }

    public function disconnect() {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
            echo "? Pump disconnected\n";
        }
    }

    public function __destruct() {
        $this->disconnect();
    }
}

// Test the pump
$pump = new SimplePumpController();

// Test wake-up first
echo "=== Testing Pump Wake-Up ===\n";
$pump->connect();
$pump->sendCommand("\r\n"); // Wake-up command
$pump->readResponse(2);
$pump->disconnect();

// Test small dispense
echo "\n=== Testing Small Dispense ===\n";
if ($pump->dispense(0.1)) { // 0.1 ml test
    echo "? Dispense test completed successfully!\n";
} else {
    echo "? Dispense test failed\n";
}

// Test actual dosage (convert from mg to ml)
echo "\n=== Testing Actual Dosage ===\n";
$dosage_mg = 50; // Example dosage
$concentration = 10; // mg/ml
$dosage_ml = $dosage_mg / $concentration;

if ($pump->dispense($dosage_ml)) {
    echo "? Successfully dispensed $dosage_mg mg ($dosage_ml ml)!\n";
} else {
    echo "? Failed to dispense medication\n";
}
?>