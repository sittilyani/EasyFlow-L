<?php
require_once 'vendor/autoload.php';

use PhpSerial\PhpSerial;

echo "=== Testing Serial Port Communication ===\n\n";

$serial = new PhpSerial();

try {
    // Configure to match COM3's actual settings
    $serial->deviceSet("COM3");
    $serial->confBaudRate(9600);
    $serial->confParity("even");        // Changed from "none" to "even"
    $serial->confCharacterLength(7);    // Changed from 8 to 7
    $serial->confStopBits(1);
    $serial->confFlowControl("none");

    echo "Configuration set successfully!\n";

    if ($serial->deviceOpen('r+b')) {
        echo "? Serial port opened successfully!\n\n";

        // Send a test command (adjust based on your pump's protocol)
        echo "Sending command...\n";
        $serial->sendMessage("\r\n");
        sleep(1);

        // Read response
        $response = $serial->readPort();
        echo "Response: " . ($response ?: "No response (this is normal if pump needs specific commands)") . "\n\n";

        $serial->deviceClose();
        echo "? Serial port closed successfully!\n";

    } else {
        echo "? Failed to open serial port\n";
    }

} catch (Exception $e) {
    echo "? Error: " . $e->getMessage() . "\n";
}
?>