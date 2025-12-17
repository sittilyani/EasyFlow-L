<?php
// pump/python_pump_wrapper.php

class PythonPumpWrapper {
    private $pythonPath;
    private $scriptPath;
    private $port = 'COM3';
    
    public function __construct($port = null) {
        // Detect Python path
        $this->detectPython();
        
        // Set script path
        $this->scriptPath = realpath(__DIR__ . '/../../pump_controller.py');
        
        if ($port) {
            $this->port = $port;
        }
        
        // Update COM port in Python script if needed
        $this->updateComPort();
    }
    
    private function detectPython() {
        // Try different Python commands
        $commands = [
            'python',      // Windows
            'py',          // Windows alternative
            'python3',     // Unix/Linux
            'C:\laragon\bin\python\python-3.13\python.exe'  // Your Laragon Python
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
        // Read Python script
        $content = file_get_contents($this->scriptPath);
        
        // Update COM port if different
        if (!preg_match('/COM_PORT\s*=\s*["\']' . preg_quote($this->port, '/') . '["\']/', $content)) {
            $content = preg_replace(
                '/COM_PORT\s*=\s*["\'][^"\']*["\']/',
                'COM_PORT = "' . $this->port . '"',
                $content
            );
            
            file_put_contents($this->scriptPath, $content);
            error_log("PYTHON: Updated COM port to {$this->port}");
        }
    }
    
    private function runPython($args) {
        $command = escapeshellcmd($this->pythonPath) . ' ' . 
                  escapeshellarg($this->scriptPath) . ' ' . 
                  $args . ' 2>&1';
        
        error_log("PYTHON CMD: $command");
        
        exec($command, $output, $returnCode);
        
        $response = implode("\n", $output);
        error_log("PYTHON RESPONSE: " . substr($response, 0, 500));
        
        // Try to parse JSON from last line (where our script outputs JSON)
        $jsonStart = strrpos($response, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($response, $jsonStart);
            $data = json_decode($jsonStr, true);
            
            if ($data !== null) {
                return $data;
            }
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
}

// Test interface
if (isset($_GET['test'])) {
    header('Content-Type: text/plain');
    
    try {
        $pump = new PythonPumpWrapper();
        
        echo "=== Python Pump Wrapper Test ===\n";
        echo "Python Path: " . $pump->pythonPath . "\n";
        echo "Script Path: " . $pump->scriptPath . "\n\n";
        
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
        echo "ERROR: " . $e->getMessage();
    }
}
?>