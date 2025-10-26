<?php
class PumpWindowsController {

    public function wakeUp() {
        $this->configurePort();

        // Send carriage return via echo
        exec('echo. > COM3', $output, $returnCode);

        if ($returnCode === 0) {
            return ['wakeup_sent' => true, 'method' => 'echo'];
        } else {
            throw new Exception("Wake-up failed");
        }
    }

    public function dispense($amount_ml) {
        $this->configurePort();

        $formattedAmount = number_format($amount_ml, 2);
        $command = "VOL {$formattedAmount}";

        // Try different methods
        $methods = [
            ['type' => 'echo', 'cmd' => "echo {$command} > COM3"],
            ['type' => 'copy', 'cmd' => "echo {$command} | copy /b con COM3"],
            ['type' => 'powershell', 'cmd' => "powershell \"\\\"{$command}\\\" | Out-File -FilePath COM3 -Encoding ASCII\""]
        ];

        foreach ($methods as $method) {
            exec($method['cmd'], $output, $returnCode);

            if ($returnCode === 0) {
                // Wait for dispense
                $waitTime = max(3, ceil($amount_ml));
                sleep($waitTime);

                return [
                    'dispensed_ml' => $amount_ml,
                    'method_used' => $method['type'],
                    'wait_time' => $waitTime
                ];
            }
        }

        throw new Exception("All dispense methods failed");
    }

    private function configurePort() {
        // Configure COM3 using mode command
        $modeCommand = 'mode COM3: BAUD=9600 PARITY=E DATA=7 STOP=1';
        exec($modeCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Failed to configure COM3");
        }

        // Small delay
        usleep(500000);
    }

    public function testConnection() {
        try {
            $this->configurePort();
            exec('echo ? > COM3', $output, $returnCode);

            return [
                'connected' => $returnCode === 0,
                'method' => 'echo_command'
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>