<?php
echo "<pre>";

// Direct serial test
echo "=== Direct Serial Test ===\n";

// Try different baud rates
$bauds = [19200, 9600, 38400, 57600, 115200];

foreach ($bauds as $baud) {
    echo "\nTrying COM20 at {$baud} baud...\n";

    $cmd = 'C:\laragon\bin\python\python-3.13\python.exe -c "'
        . "import serial, time;"
        . "ser = serial.Serial('COM20', {$baud}, timeout=2);"
        . "ser.write(b\'RDY?\\\\r\\\\n\');"
        . "time.sleep(1);"
        . "response = ser.read(100);"
        . "print(f\'Response: {repr(response)}\');"
        . "ser.close();"
        . '"';

    exec($cmd, $output, $return);
    echo implode("\n", $output) . "\n";
}

echo "</pre>";
?>