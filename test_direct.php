<?php
// Simple COM port test
$port = 'COM3';
$handle = @fopen("\\\\.\\COM3", "r+b");

if ($handle) {
    echo "SUCCESS: COM3 opened successfully!\n";
    fclose($handle);
} else {
    echo "ERROR: Cannot open COM3\n";
    $error = error_get_last();
    echo "Error details: " . $error['message'] . "\n";
}
?>