<?php
require_once 'vendor/autoload.php';

use PhpSerial\PhpSerial;

echo "Serial library installed successfully!\n";

$serial = new PhpSerial();
echo "Serial class loaded: " . (class_exists('PhpSerial\\PhpSerial') ? 'Yes' : 'No') . "\n";

// Test configuration
try {
    $serial->deviceSet("COM3"); // Change to your MasterFlex pump COM port
    $serial->confBaudRate(9600);
    $serial->confParity("none");
    $serial->confCharacterLength(8);
    $serial->confStopBits(1);
    $serial->confFlowControl("none");

    echo "Configuration set successfully!\n";

    $serial->deviceOpen();
    echo "Serial port opened successfully!\n";

    // Test sending a command
    $serial->sendMessage("test\r\n");
    echo "Message sent successfully!\n";

    // Try to read response
    $read = $serial->readPort();
    echo "Response: " . ($read ?: "No response") . "\n";

    $serial->deviceClose();
    echo "Serial port closed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>