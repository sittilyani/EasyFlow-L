<?php
// Configure COM port using Windows mode command
exec('mode COM3: BAUD=9600 PARITY=E DATA=7 STOP=1', $output, $returnCode);

if ($returnCode === 0) {
    echo "COM3 configured successfully via mode command\n";

    // Now try to open - use full namespace
    $serial = new \PhpSerial\PhpSerial();
    $serial->deviceSet("COM3");

    if ($serial->deviceOpen()) {
        echo "SUCCESS: Port opened after mode configuration!\n";
        $serial->deviceClose();
    } else {
        echo "FAILED: Could not open port even after mode configuration\n";
    }
} else {
    echo "Failed to configure COM3 via mode command\n";
    print_r($output);
}
?>