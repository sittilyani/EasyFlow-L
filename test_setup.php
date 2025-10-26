<?php
// test_setup.php - Run this in your browser to verify setup
echo "<pre>";
echo "=== Pump System Setup Test ===\n\n";

// Test 1: Check current directory
echo "1. Current Directory: " . __DIR__ . "\n";

// Test 2: Check if required files exist
$requiredFiles = [
    'pump_service.php',
    'PumpWebController.php',
    'includes/config.php'
];

echo "\n2. Required Files Check:\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ? $file exists\n";
    } else {
        echo "   ? $file MISSING\n";
    }
}

// Test 3: Check PHP path for batch files
echo "\n3. PHP Path for Batch Files:\n";
$phpBinary = PHP_BINARY;
echo "   PHP executable: $phpBinary\n";

// Test 4: Check if we can create queue files
echo "\n4. File Permissions Test:\n";
try {
    file_put_contents('test_queue.json', json_encode(['test' => true]));
    echo "   ? Can create queue files\n";
    unlink('test_queue.json');
} catch (Exception $e) {
    echo "   ? Cannot create queue files: " . $e->getMessage() . "\n";
}

// Test 5: Suggest batch file content
echo "\n5. Suggested Batch File Content:\n";
echo "   Use this in start_pump_service.bat:\n";
echo "   @echo off\n";
echo "   cd /d " . __DIR__ . "\n";
echo "   \"$phpBinary\" pump_service.php\n";
echo "   pause\n";

echo "\n? Setup test completed!\n";
echo "</pre>";
?>