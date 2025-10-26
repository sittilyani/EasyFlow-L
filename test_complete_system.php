<?php
// test_complete_system.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Complete Pump System Test ===\n\n";

// Test 1: Basic PHP info
echo "1. PHP Information:\n";
echo "   Version: " . PHP_VERSION . "\n";
echo "   Running as: " . (strpos(PHP_BINARY, 'httpd') !== false ? 'Web Server' : 'CLI') . "\n";
echo "   SAPI: " . php_sapi_name() . "\n\n";

// Test 2: File existence
echo "2. Required Files:\n";
$files = [
    'pump_service.php',
    'PumpWebController.php',
    'pump_commands.json',
    'pump_results.json'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ? $file\n";
    } else {
        echo "   ? $file (will be created automatically)\n";
    }
}

// Test 3: COM port access (only works in CLI with admin rights)
echo "\n3. COM Port Access:\n";
if (php_sapi_name() === 'cli') {
    $port = 'COM3';
    $handle = @fopen("\\\\.\\{$port}", "r+b");

    if ($handle) {
        echo "   ? COM3 accessible (Admin rights confirmed!)\n";

        // Test communication
        fwrite($handle, "\r\n");
        sleep(1);

        $response = fread($handle, 256);
        if ($response) {
            echo "   ? Pump responded: " . bin2hex($response) . "\n";
        } else {
            echo "   ? No response (may be normal)\n";
        }

        fclose($handle);
    } else {
        $error = error_get_last();
        echo "   ? COM3 access denied: " . $error['message'] . "\n";
        echo "   ? Run with Administrator privileges!\n";
    }
} else {
    echo "   ? Run via batch file for COM port test\n";
}

// Test 4: Queue system
echo "\n4. Queue System:\n";
$webController = new PumpWebController();
$status = $webController->getQueueStatus();
echo "   Queued commands: " . $status['queued_commands'] . "\n";
echo "   Pending results: " . $status['pending_results'] . "\n";

echo "\n? System test completed!\n";
echo "</pre>";
?>