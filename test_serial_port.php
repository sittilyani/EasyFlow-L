<?php
require_once 'vendor/autoload.php';
use Gregwar\Serial\PhpSerial;

echo "Serial library installed successfully!\n";
echo "Serial class loaded: " . (class_exists('Gregwar\\Serial\\PhpSerial') ? 'Yes' : 'No') . "\n";

try {
    $serial = new PhpSerial();
    $serial->deviceSet('COM3'); // Replace with your actual port
    $serial->confBaudRate(9600);
    $serial->confParity('none');
    $serial->confCharacterLength(8);
    $serial->confStopBits(1);
    if ($serial->deviceOpen()) {
        echo "Serial port COM3 opened successfully!\n";
        $serial->deviceClose();
        echo "Serial port closed.\n";
    } else {
        echo "Failed to open serial port COM3.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>