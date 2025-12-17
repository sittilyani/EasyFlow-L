<?php
// pump/manual_tester.php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Pump Command Tester</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        input, button { font-family: monospace; padding: 10px; }
        pre { background: #000; color: #0f0; padding: 10px; }
    </style>
</head>
<body>
    <h2>Manual Pump Command Tester - COM20</h2>

    <form method="POST">
        <input type="text" name="command" value="REMOTE" size="30" placeholder="Enter command (e.g., REMOTE, RUN, STOP)">
        <button type="submit">Send Command</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['command'])) {
        $command = $_POST['command'];

        // Create Python script
        $pythonScript = <<<PYTHON
import serial
import time
import sys

port = "COM20"
baud = 9600
command = "{$command}"

try:
    ser = serial.Serial(port, baud, timeout=3)
    print(f"Opened {port}")

    # Add carriage return if not present
    if not command.endswith('\\r\\n'):
        command += '\\r\\n'

    print(f"Sending: {command.encode().hex()}")
    ser.write(command.encode())
    ser.flush()

    # Wait for response
    time.sleep(2)

    response = b""
    while ser.in_waiting > 0:
        chunk = ser.read(ser.in_waiting)
        response += chunk
        time.sleep(0.1)

    if response:
        print(f"Response hex: {response.hex()}")
        try:
            print(f"Response text: '{response.decode('ascii', errors='ignore').strip()}'")
        except:
            print("Could not decode as text")
    else:
        print("No response")

    ser.close()

except Exception as e:
    print(f"Error: {e}")

PYTHON;

        $tempFile = tempnam(sys_get_temp_dir(), 'manual_') . '.py';
        file_put_contents($tempFile, $pythonScript);

        $pythonExe = 'C:\laragon\bin\python\python-3.13\python.exe';
        $cmd = escapeshellarg($pythonExe) . ' ' . escapeshellarg($tempFile) . ' 2>&1';

        echo "<h3>Result:</h3>";
        echo "<pre>";
        exec($cmd, $output, $return);
        foreach ($output as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";

        unlink($tempFile);
    }
    ?>

    <hr>
    <h3>Common MasterPlex Commands to Try:</h3>
    <ul>
        <li><code>REMOTE</code> - Enter remote control mode</li>
        <li><code>LOCAL</code> - Return to local control</li>
        <li><code>RUN</code> - Start pumping</li>
        <li><code>STOP</code> - Stop pumping</li>
        <li><code>STATUS</code> - Get pump status</li>
        <li><code>D5.00ML</code> - Dispense 5.00 ml</li>
        <li><code>VOL5.00</code> - Set volume to 5.00 ml</li>
        <li><code>RATE5.0</code> - Set rate to 5.0 ml/min</li>
        <li><code>ID</code> - Get pump ID</li>
        <li><code>VERSION</code> - Get firmware version</li>
    </ul>
</body>
</html>