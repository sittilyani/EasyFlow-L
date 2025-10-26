<?php
require_once 'vendor/autoload.php';

use PhpSerial\PhpSerial;

echo "=== Testing Serial Port Communication ===\n\n";

// Try different COM ports
$ports_to_try = ['COM1', 'COM3', 'COM4', 'COM5', 'COM6'];

foreach ($ports_to_try as $port) {
    echo "Trying $port...\n";

    $serial = new PhpSerial();

    try {
        $serial->deviceSet($port);
        $serial->confBaudRate(9600); // Check your pump's manual for correct baud rate
        $serial->confParity("none");
        $serial->confCharacterLength(8);
        $serial->confStopBits(1);
        $serial->confFlowControl("none");

        if ($serial->deviceOpen('r+b')) {
            echo "? Successfully opened $port!\n";

            // Try to send a simple command
            $serial->sendMessage("\r\n"); // Send carriage return
            sleep(1);

            $response = $serial->readPort();
            echo "Response: " . ($response ?: "No response") . "\n";

            $serial->deviceClose();
            echo "Port closed.\n\n";
            break; // Stop if we found a working port
        }
    } catch (Exception $e) {
        echo "? Failed: " . $e->getMessage() . "\n\n";
    }
}
?>