<?php
echo "Testing COM3 access as Administrator...\n";

// Test 1: Basic file open
$handle = @fopen("\\\\.\\COM3", "r+b");
if ($handle) {
    echo "? SUCCESS: COM3 opened!\n";

    // Configure port
    exec('mode COM3: BAUD=9600 PARITY=E DATA=7 STOP=1', $output, $returnCode);
    echo "Mode command result: $returnCode\n";

    // Test communication
    $wakeup = "\r\n";
    fwrite($handle, $wakeup);
    echo "Sent wake-up command\n";

    // Wait and read
    sleep(1);
    $response = fread($handle, 256);
    if ($response) {
        echo "Pump response: " . bin2hex($response) . "\n";
    } else {
        echo "No response (may be normal)\n";
    }

    fclose($handle);
} else {
    echo "? FAILED: " . error_get_last()['message'] . "\n";
    echo "Even as Administrator - check if pump is connected and COM3 exists\n";
}

// Test 2: Check available COM ports
echo "\nAvailable COM ports:\n";
exec('mode COM', $comOutput);
foreach ($comOutput as $line) {
    if (strpos($line, 'COM') !== false) {
        echo $line . "\n";
    }
}
?>