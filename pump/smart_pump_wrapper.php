<?php
// pump/smart_pump_wrapper.php - Adapts based on discovered protocol

class SmartPumpWrapper {
    private $pythonExe;
    private $port = 'COM20';
    private $protocol = 'unknown';

    public function __construct($port = null) {
        if ($port) {
            $this->port = $port;
        }

        $this->pythonExe = 'C:\laragon\bin\python\python-3.13\python.exe';

        // Try to auto-discover protocol
        $this->discoverProtocol();
    }

    private function discoverProtocol() {
        // Try to detect which protocol works
        $testScript = $this->createTestScript();
        $tempFile = tempnam(sys_get_temp_dir(), 'discover_') . '.py';
        file_put_contents($tempFile, $testScript);

        $cmd = escapeshellarg($this->pythonExe) . ' ' . escapeshellarg($tempFile) . ' 2>&1';
        exec($cmd, $output, $return);

        $response = implode("\n", $output);

        // Analyze response to determine protocol
        if (strpos($response, 'MASTERPLEX') !== false) {
            $this->protocol = 'masterplex';
        } elseif (strpos($response, 'READY') !== false) {
            $this->protocol = 'simple';
        } elseif (strpos($response, 'OK') !== false) {
            $this->protocol = 'ok_based';
        } else {
            $this->protocol = 'timed'; // Fallback to timed run/stop
        }

        unlink($tempFile);
        error_log("PUMP: Detected protocol: {$this->protocol}");
    }

    private function createTestScript() {
        return <<<PYTHON
import serial
import time

port = "{$this->port}"
baud = 9600

try:
    ser = serial.Serial(port, baud, timeout=2)

    # Try different protocols
    tests = [
        (b'\\x05\\r\\n', "enquiry"),
        (b'REMOTE\\r\\n', "remote"),
        (b'STATUS\\r\\n', "status"),
        (b'ID\\r\\n', "id"),
    ]

    for cmd, name in tests:
        ser.reset_input_buffer()
        ser.write(cmd)
        ser.flush()
        time.sleep(1)

        if ser.in_waiting > 0:
            response = ser.read(ser.in_waiting)
            print(f"{name}:{response.decode('ascii', errors='ignore').strip()}")
            break

    ser.close()

except Exception as e:
    print(f"error:{e}")

PYTHON;
    }

    public function execute($action, $param = '') {
        $script = $this->createActionScript($action, $param);
        $tempFile = tempnam(sys_get_temp_dir(), 'pump_') . '.py';
        file_put_contents($tempFile, $script);

        $cmd = escapeshellarg($this->pythonExe) . ' ' . escapeshellarg($tempFile) . ' 2>&1';
        exec($cmd, $output, $return);

        unlink($tempFile);

        $response = implode("\n", $output);
        return $this->parseResponse($response, $action);
    }

    private function createActionScript($action, $param) {
        $ml = floatval($param);

        switch ($this->protocol) {
            case 'masterplex':
                return $this->masterplexScript($action, $ml);
            case 'simple':
                return $this->simpleScript($action, $ml);
            case 'ok_based':
                return $this->okBasedScript($action, $ml);
            default:
                return $this->timedScript($action, $ml);
        }
    }

    private function masterplexScript($action, $ml) {
        $cmd = '';

        if ($action === 'wakeup') {
            $cmd = "REMOTE\\r\\n";
        } elseif ($action === 'dispense') {
            $cmd = sprintf("D%.2fML\\r\\n", $ml);
        }

        return $this->baseScript($cmd);
    }

    private function timedScript($action, $ml) {
        // Fallback: timed RUN/STOP
        $script = <<<PYTHON
import serial
import time
import json

port = "{$this->port}"
baud = 9600

try:
    ser = serial.Serial(port, baud, timeout=5)
    time.sleep(1)

    if "{$action}" == "wakeup":
        ser.write(b'\\r\\n')
        ser.flush()
        time.sleep(2)
        result = {"success": True, "message": "Wakeup attempted"}

    elif "{$action}" == "dispense" and {$ml} > 0:
        # Clear any existing commands
        ser.reset_input_buffer()

        # Send RUN
        ser.write(b'RUN\\r\\n')
        ser.flush()

        # Run for calculated time
        run_time = {$ml} * 2  # 2 seconds per ml
        print(f"Running for {run_time} seconds")
        time.sleep(run_time)

        # Send STOP
        ser.write(b'STOP\\r\\n')
        ser.flush()
        time.sleep(1)

        result = {"success": True, "message": f"Dispensed {$ml} ml via timed run"}

    else:
        result = {"success": False, "message": "Invalid action"}

    ser.close()

except Exception as e:
    result = {"success": False, "message": f"Error: {str(e)}"}

print("\\n" + json.dumps(result))

PYTHON;

        return $script;
    }

    private function baseScript($command) {
        return <<<PYTHON
import serial
import time
import json

port = "{$this->port}"
baud = 9600

try:
    ser = serial.Serial(port, baud, timeout=5)
    time.sleep(1)

    ser.write(b'{$command}')
    ser.flush()

    # Wait for response
    time.sleep(2)

    response = b""
    while ser.in_waiting > 0:
        response += ser.read(ser.in_waiting)
        time.sleep(0.1)

    ser.close()

    if response:
        result = {"success": True, "message": f"Command sent, response: {response.decode('ascii', errors='ignore')}"}
    else:
        result = {"success": True, "message": "Command sent (no response)"}

except Exception as e:
    result = {"success": False, "message": f"Error: {str(e)}"}

print("\\n" + json.dumps(result))

PYTHON;
    }

    private function parseResponse($response, $action) {
        // Extract JSON from response
        $jsonStart = strrpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($jsonStr, true);
            if ($data) {
                $data['protocol'] = $this->protocol;
                $data['raw_output'] = $response;
                return $data;
            }
        }

        return [
            'success' => strpos($response, 'success') !== false || strpos($response, 'True') !== false,
            'message' => $response,
            'protocol' => $this->protocol
        ];
    }

    public function wakeup() {
        return $this->execute('wakeup');
    }

    public function dispense($ml) {
        return $this->execute('dispense', $ml);
    }

    public function getInfo() {
        return [
            'port' => $this->port,
            'protocol' => $this->protocol,
            'python' => $this->pythonExe
        ];
    }
}