<?php
require_once 'vendor/autoload.php';
use PhpSerial\PhpSerial;

echo "=== Pump Wake-Up Test ===\n\n";

$serial = new PhpSerial();
$serial->deviceSet("COM3");
$serial->confBaudRate(9600);
$serial->confParity("even");
$serial->confCharacterLength(7);
$serial->confStopBits(1);
$serial->confFlowControl("none");

if ($serial->deviceOpen('r+b')) {
    echo "? Port opened\n\n";

    // Send multiple carriage returns to wake up pump
    echo "Sending wake-up sequence (Press Ctrl+C to stop)...\n";

    for ($i = 0; $i < 20; $i++) {
        echo "Attempt " . ($i + 1) . ": ";
        $serial->sendMessage("\r\n");
        usleep(500000);

        $data = $serial->readPort(256);
        if ($data && strlen($data) > 0) {
            echo "? RECEIVED DATA!\n";
            echo "  Raw: " . $data . "\n";
            echo "  Hex: " . bin2hex($data) . "\n";
            echo "  Length: " . strlen($data) . " bytes\n";
            break;
        } else {
            echo "No response\n";
        }
    }

    $serial->deviceClose();
    echo "\n? Test complete\n";
}
?>