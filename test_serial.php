<?php
require_once 'vendor/autoload.php';
use Gregwar\Serial\PhpSerial;

echo "Serial library installed successfully!\n";
$serial = new PhpSerial();
echo "Serial class loaded: " . (class_exists('Gregwar\\Serial\\PhpSerial') ? 'Yes' : 'No') . "\n";
?>