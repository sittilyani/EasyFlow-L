<?php
// pump/debug.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Pump Debug Information</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
<h1>Pump System Debug Information</h1>";

// Check if pump files exist
$files = [
    'pump_manager.php',
    'pump_service.php',
    'serial_communication.php',
    'test_ajax.php'
];

echo "<div class='section'><h2>File Check</h2>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p class='success'>? $file exists (" . filesize($path) . " bytes)</p>";
    } else {
        echo "<p class='error'>? $file NOT FOUND at $path</p>";
    }
}
echo "</div>";

// Check PHP configuration
echo "<div class='section'><h2>PHP Configuration</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PHP OS: " . PHP_OS . "</p>";
echo "<p>PHP Include Path: " . get_include_path() . "</p>";

// Check extensions
$extensions = ['dio', 'json', 'mysqli'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>? $ext extension loaded</p>";
    } else {
        echo "<p class='error'>? $ext extension NOT loaded</p>";
    }
}
echo "</div>";

// Check serial port access
echo "<div class='section'><h2>Serial Port Detection</h2>";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "<p>Windows System Detected</p>";

    // Try to detect COM ports
    exec('wmic path Win32_SerialPort get DeviceID,Description', $output, $return);
    if ($return === 0 && !empty($output)) {
        echo "<pre>Detected Serial Ports:\n";
        foreach ($output as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='error'>Could not detect serial ports via WMIC</p>";

        // Try alternative method
        echo "<p>Trying alternative detection...</p>";
        for ($i = 1; $i <= 8; $i++) {
            $port = "COM$i";
            $handle = @fopen($port, 'r');
            if ($handle !== false) {
                echo "<p class='success'>? $port is accessible</p>";
                fclose($handle);
            } else {
                echo "<p>$port not accessible</p>";
            }
        }
    }
} else {
    echo "<p>Unix/Linux System Detected</p>";

    if (is_dir('/dev')) {
        $devices = glob('/dev/tty*');
        echo "<pre>Available tty devices:\n";
        foreach ($devices as $device) {
            echo htmlspecialchars($device) . "\n";
        }
        echo "</pre>";
    }
}
echo "</div>";

// Test JSON encoding
echo "<div class='section'><h2>JSON Test</h2>";
$testData = ['test' => 'value', 'number' => 123];
$json = json_encode($testData);
if ($json !== false) {
    echo "<p class='success'>? JSON encoding works: $json</p>";
} else {
    echo "<p class='error'>? JSON encoding failed: " . json_last_error_msg() . "</p>";
}
echo "</div>";

// Test include path
echo "<div class='section'><h2>Include Path Test</h2>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Real path: " . realpath(__DIR__) . "</p>";

// Try to include pump_manager
try {
    require_once __DIR__ . '/pump_manager.php';
    echo "<p class='success'>? pump_manager.php included successfully</p>";

    // Test instantiation
    $pumpManager = PumpManager::getInstance();
    echo "<p class='success'>? PumpManager instantiated</p>";

    // Get status
    $status = $pumpManager->getStatus();
    echo "<pre>Pump Status:\n" . print_r($status, true) . "</pre>";

} catch (Exception $e) {
    echo "<p class='error'>? Error including pump_manager.php: " . $e->getMessage() . "</p>";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

echo "</body></html>";
?>