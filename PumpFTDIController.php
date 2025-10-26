<?php
class PumpFTDIController {

    public function testConnection() {
        // Test using Windows native commands first
        $methods = [
            'mode_command' => $this->testWithMode(),
            'powershell' => $this->testWithPowerShell(),
            'direct_echo' => $this->testWithEcho()
        ];

        return $methods;
    }

    private function testWithMode() {
        exec('mode COM3', $output, $returnCode);
        return [
            'success' => $returnCode === 0,
            'output' => $output,
            'return_code' => $returnCode
        ];
    }

    private function testWithPowerShell() {
        $psCommand = 'powershell -Command "[System.IO.Ports.SerialPort]::getportnames()"';
        exec($psCommand, $output, $returnCode);

        $com3Found = false;
        foreach ($output as $line) {
            if (trim($line) === 'COM3') {
                $com3Found = true;
                break;
            }
        }

        return [
            'success' => $com3Found,
            'output' => $output,
            'com3_found' => $com3Found
        ];
    }

    private function testWithEcho() {
        $testFile = 'test_com3.txt';
        exec('echo TEST > COM3 2>&1', $output, $returnCode);

        return [
            'success' => $returnCode === 0,
            'output' => $output,
            'return_code' => $returnCode
        ];
    }

    public function sendCommand($command) {
        // Ensure command ends with CR+LF
        if (!str_ends_with($command, "\r\n")) {
            $command .= "\r\n";
        }

        // Create a temporary file with the command
        $tempFile = tempnam(sys_get_temp_dir(), 'pump_');
        file_put_contents($tempFile, $command);

        // Use copy command to send to COM port
        $copyCommand = "copy /b \"{$tempFile}\" COM3";
        exec($copyCommand, $output, $returnCode);

        // Clean up
        unlink($tempFile);

        if ($returnCode === 0) {
            return ['sent' => true, 'command' => $command];
        } else {
            throw new Exception("Failed to send command: " . implode(', ', $output));
        }
    }

    public function dispense($amount_ml) {
        $formattedAmount = number_format($amount_ml, 2);

        // Try different command formats
        $commands = [
            "VOL {$formattedAmount}",
            "GO {$formattedAmount}",
            "RUN {$formattedAmount}",
            "DISPENSE {$formattedAmount}"
        ];

        foreach ($commands as $command) {
            try {
                $this->sendCommand($command);

                // Wait for operation
                $waitTime = max(3, ceil($amount_ml));
                sleep($waitTime);

                return [
                    'success' => true,
                    'amount_ml' => $amount_ml,
                    'command_used' => $command,
                    'wait_time' => $waitTime
                ];
            } catch (Exception $e) {
                // Try next command
                continue;
            }
        }

        throw new Exception("All command attempts failed");
    }
}
?>