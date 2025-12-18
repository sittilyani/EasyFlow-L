<?php
// pump/find_protocol.php
header('Content-Type: text/plain');

// Create a test script to discover protocol
$testScript = <<<'PYTHON'
import serial
import time

port = "COM20"
baud = 9600

try:
    ser = serial.Serial(port, baud, timeout=2)
    print(f"Opened {port}")

    # Test different commands
    tests = [
        ("Carriage return", b'\\r\\n'),
        ("ENQ", b'\\x05'),
        ("REMOTE", b'REMOTE\\r\\n'),
        ("LOCAL", b'LOCAL\\r\\n'),
        ("STATUS", b'STATUS\\r\\n'),
        ("ID", b'ID\\r\\n'),
        ("READY", b'READY\\r\\n'),
        ("RUN", b'RUN\\r\\n'),
        ("STOP", b'STOP\\r\\n'),
        ("D1.00ML", b'D1.00ML\\r\\n'),
        ("VOL1.00", b'VOL1.00\\r\\n'),
        ("GO", b'GO\\r\\n'),
    ]

    for desc, cmd in tests:
        print(f"\\n--- Testing: {desc} ---")
        print(f"Command: {cmd.hex()}")

        ser.reset_input_buffer()
        ser.write(cmd)
        ser.flush()

        time.sleep(1)

        if ser.in_waiting > 0:
            response = ser.read(ser.in_waiting)
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

$tempFile = tempnam(sys_get_temp_dir(), 'protocol_') . '.py';
file_put_contents($tempFile, $testScript);

$pythonExe = 'C:\laragon\bin\python\python-3.13\python.exe';
$cmd = escapeshellarg($pythonExe) . ' ' . escapeshellarg($tempFile) . ' 2>&1';

echo "Running protocol discovery...\n";
echo "Command: $cmd\n\n";

exec($cmd, $output, $return);

foreach ($output as $line) {
    echo $line . "\n";
}

unlink($tempFile);
?>