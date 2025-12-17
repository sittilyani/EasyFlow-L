<?php
// pump/pump_python_wrapper.php

class PythonPumpWrapper {
        private $pythonPath;
        private $scriptPath;
        private $port = 'COM3';

        public function __construct($port = null) {
                // Set script path - YOUR SCRIPT IS IN ROOT DIRECTORY
                $this->scriptPath = realpath(__DIR__ . '/../pump_controller.py');

                if (!$this->scriptPath || !file_exists($this->scriptPath)) {
                        // Try alternative locations
                        $possiblePaths = [
                                __DIR__ . '/../pump_controller.py',          // Root directory
                                __DIR__ . '/pump_controller.py',             // Pump directory
                                'C:/laragon/www/iorpms/pump_controller.py',  // Full path
                                realpath(__DIR__ . '/../../pump_controller.py')
                        ];

                        foreach ($possiblePaths as $path) {
                                if (file_exists($path)) {
                                        $this->scriptPath = $path;
                                        break;
                                }
                        }

                        if (!$this->scriptPath || !file_exists($this->scriptPath)) {
                                throw new Exception("Python script not found. Looking for: pump_controller.py");
                        }
                }

                // Detect Python path
                $this->detectPython();

                if ($port) {
                        $this->port = $port;
                }

                // Update COM port in Python script if needed
                $this->updateComPort();
        }

        private function detectPython() {
                // Try your specific Laragon Python first
                $laragonPython = 'C:\laragon\bin\python\python-3.13\python.exe';

                if (file_exists($laragonPython)) {
                        $this->pythonPath = $laragonPython;
                        error_log("PYTHON: Using Laragon Python at: $laragonPython");
                        return true;
                }

                // Try different Python commands
                $commands = [
                        'python',      // Windows
                        'py',          // Windows alternative
                        'python3',     // Unix/Linux
                        'python.exe'   // Windows executable
                ];

                foreach ($commands as $cmd) {
                        exec("$cmd --version 2>&1", $output, $return);
                        if ($return === 0) {
                                $this->pythonPath = $cmd;
                                error_log("PYTHON: Using $cmd");
                                return true;
                        }
                }

                throw new Exception("Python not found. Please install Python or update the path.");
        }

        private function updateComPort() {
                if (!file_exists($this->scriptPath)) {
                        error_log("PYTHON: Script not found at: " . $this->scriptPath);
                        return;
                }

                // Read Python script
                $content = file_get_contents($this->scriptPath);

                // Update COM port if different
                $currentPort = $this->extractCurrentPort($content);

                if ($currentPort !== $this->port) {
                        $content = preg_replace(
                                '/COM_PORT\s*=\s*["\'][^"\']*["\']/',
                                'COM_PORT = "' . $this->port . '"',
                                $content
                        );

                        file_put_contents($this->scriptPath, $content);
                        error_log("PYTHON: Updated COM port from {$currentPort} to {$this->port}");
                }
        }

        private function extractCurrentPort($content) {
                if (preg_match('/COM_PORT\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
                        return $matches[1];
                }
                return 'COM20'; // Default from your script
        }

        private function runPython($args) {
                $command = escapeshellarg($this->pythonPath) . ' ' .
                                    escapeshellarg($this->scriptPath) . ' ' .
                                    $args . ' 2>&1';

                error_log("PYTHON CMD: $command");

                exec($command, $output, $returnCode);

                $response = implode("\n", $output);
                error_log("PYTHON OUTPUT (first 500 chars): " . substr($response, 0, 500));

                // Try to parse JSON from response
                $jsonData = $this->extractJson($response);

                if ($jsonData !== null) {
                        return $jsonData;
                }

                // Fallback: check if it looks successful
                $success = ($returnCode === 0);

                return [
                        'success' => $success,
                        'message' => $response,
                        'raw_output' => $output,
                        'return_code' => $returnCode
                ];
        }

        private function extractJson($response) {
                // Look for JSON object in response
                $jsonStart = strrpos($response, '{');
                $jsonEnd = strrpos($response, '}');

                if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
                        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);

                        // Try to clean up the JSON string
                        $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);

                        $data = json_decode($jsonStr, true);

                        if ($data !== null) {
                                return $data;
                        }

                        // If JSON decode failed, try to fix common issues
                        $jsonStr = str_replace(["\r", "\n"], '', $jsonStr);
                        $jsonStr = preg_replace('/,(\s*[}\]])/', '$1', $jsonStr); // Remove trailing commas

                        $data = json_decode($jsonStr, true);
                        if ($data !== null) {
                                return $data;
                        }
                }

                return null;
        }

        public function wakeup() {
                error_log("PUMP: Sending wakeup command via Python");
                $result = $this->runPython('wakeup');

                if ($result['success']) {
                        error_log("PUMP: Wakeup successful via Python");
                } else {
                        error_log("PUMP: Wakeup failed via Python: " . ($result['message'] ?? 'No message'));
                }

                return $result;
        }

        public function dispense($amount_ml) {
                error_log("PUMP: Sending dispense command for {$amount_ml}ml via Python");
                $result = $this->runPython("dispense {$amount_ml}");

                if ($result['success']) {
                        error_log("PUMP: Dispense successful via Python");
                } else {
                        error_log("PUMP: Dispense failed via Python: " . ($result['message'] ?? 'No message'));
                }

                return $result;
        }

        public function testConnection() {
                return $this->runPython('test');
        }

        public function debug() {
                return $this->runPython('debug');
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

// Simple test interface
if (isset($_GET['test'])) {
        header('Content-Type: text/plain');

        try {
                $pump = new PythonPumpWrapper();

                echo "=== Python Pump Wrapper Test ===\n\n";

                $info = $pump->getInfo();
                echo "Python Path: " . $info['python_path'] . "\n";
                echo "Script Path: " . $info['script_path'] . "\n";
                echo "Script Exists: " . ($info['script_exists'] ? 'YES' : 'NO') . "\n";
                echo "Port: " . $info['port'] . "\n\n";

                if (!$info['script_exists']) {
                        echo "ERROR: Python script not found!\n";
                        echo "Please make sure pump_controller.py is in:\n";
                        echo "C:\\laragon\\www\\iorpms\\pump_controller.py\n";
                        exit;
                }

                echo "=== Test Connection ===\n";
                $result = $pump->testConnection();
                print_r($result);

                echo "\n=== Test Wakeup ===\n";
                $result = $pump->wakeup();
                print_r($result);

                echo "\n=== Test Dispense ===\n";
                $result = $pump->dispense(5);
                print_r($result);

        } catch (Exception $e) {
                echo "ERROR: " . $e->getMessage() . "\n";
                echo "Stack trace:\n" . $e->getTraceAsString();
        }
}
?>