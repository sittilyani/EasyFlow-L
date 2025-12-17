<?php
// pump/simple_pump_wrapper.php

class SimplePumpWrapper {
    private $pythonPath;
    private $scriptPath;
    private $port = 'COM20';

    public function __construct($port = null) {
        // Set port
        if ($port) {
            $this->port = $port;
        }

        // Set script path - in same pump folder
        $this->scriptPath = __DIR__ . '/pump_controller.py';

        if (!file_exists($this->scriptPath)) {
            throw new Exception("Python script not found at: " . $this->scriptPath);
        }

        // Detect Python
        $this->detectPython();

        // Update COM port in Python script
        $this->updateComPort();
    }

    private function detectPython() {
        // Try Laragon Python first
        $laragonPython = 'C:\laragon\bin\python\python-3.13\python.exe';

        if (file_exists($laragonPython)) {
            $this->pythonPath = $laragonPython;
            error_log("PUMP: Using Laragon Python: $laragonPython");
            return;
        }

        // Try common Python commands
        $commands = ['python', 'py', 'python3', 'python.exe'];

        foreach ($commands as $cmd) {
            exec("$cmd --version 2>&1", $output, $return);
            if ($return === 0) {
                $this->pythonPath = $cmd;
                error_log("PUMP: Using Python: $cmd");
                return;
            }
        }

        throw new Exception("Python not found. Install Python or check Laragon Python path.");
    }

    private function updateComPort() {
        // Read and update Python script with correct COM port
        $content = file_get_contents($this->scriptPath);

        // Update COM_PORT definition
        $newContent = preg_replace(
            '/COM_PORT\s*=\s*["\'][^"\']*["\']/',
            'COM_PORT = "' . $this->port . '"',
            $content
        );

        if ($newContent !== $content) {
            file_put_contents($this->scriptPath, $newContent);
            error_log("PUMP: Updated Python script to use {$this->port}");
        }
    }

    public function execute($action, $param = '') {
        $cmd = escapeshellarg($this->pythonPath) . ' ' .
               escapeshellarg($this->scriptPath) . ' ' .
               escapeshellarg($action);

        if ($param !== '') {
            $cmd .= ' ' . escapeshellarg($param);
        }

        $cmd .= ' 2>&1';

        error_log("PUMP CMD: $cmd");

        exec($cmd, $output, $returnCode);

        $fullOutput = implode("\n", $output);

        // Extract JSON from output
        $jsonData = $this->extractJson($fullOutput);

        if ($jsonData !== null) {
            $jsonData['raw_output'] = $output;
            $jsonData['return_code'] = $returnCode;
            return $jsonData;
        }

        return [
            'success' => ($returnCode === 0),
            'message' => $fullOutput,
            'raw_output' => $output,
            'return_code' => $returnCode
        ];
    }

    private function extractJson($output) {
        // Find JSON in output
        $jsonStart = strrpos($output, '{');
        $jsonEnd = strrpos($output, '}');

        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonStr = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);

            // Clean the JSON string
            $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);

            $data = json_decode($jsonStr, true);
            if ($data !== null) {
                return $data;
            }
        }

        return null;
    }

    public function wakeup() {
        error_log("PUMP: Sending wakeup command...");
        $result = $this->execute('wakeup');

        if ($result['success']) {
            error_log("PUMP: Wakeup successful");
        } else {
            error_log("PUMP: Wakeup failed: " . ($result['message'] ?? 'No message'));
        }

        return $result;
    }

    public function dispense($amount_ml) {
        error_log("PUMP: Dispensing {$amount_ml}ml...");
        $result = $this->execute('dispense', $amount_ml);

        if ($result['success']) {
            error_log("PUMP: Dispense successful");
        } else {
            error_log("PUMP: Dispense failed: " . ($result['message'] ?? 'No message'));
        }

        return $result;
    }

    public function test() {
        return $this->execute('test');
    }

    public function status() {
        return $this->execute('status');
    }

    public function getInfo() {
        return [
            'python_path' => $this->pythonPath,
            'script_path' => $this->scriptPath,
            'port' => $this->port,
            'script_exists' => file_exists($this->scriptPath)
        ];
    }
}

// Direct test
if (isset($_GET['direct'])) {
    header('Content-Type: text/plain');

    try {
        $pump = new SimplePumpWrapper();

        echo "=== Pump System Info ===\n\n";
        $info = $pump->getInfo();
        foreach ($info as $key => $value) {
            echo "$key: $value\n";
        }

        echo "\n=== Testing Connection ===\n";
        $result = $pump->test();
        print_r($result);

    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>