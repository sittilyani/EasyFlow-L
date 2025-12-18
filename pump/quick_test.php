<?php
// Quick command-line style test
echo "<pre>";

// Test Python directly
echo "=== Direct Python Test ===\n";
$cmd = 'C:\laragon\bin\python\python-3.13\python.exe ' .
       escapeshellarg(__DIR__ . '/pump_controller.py') . ' test 2>&1';
exec($cmd, $output, $return);
echo "Command: $cmd\n";
echo "Return: $return\n";
echo "Output:\n" . implode("\n", $output) . "\n\n";

echo "=== PHP Wrapper Test ===\n";
require_once 'simple_pump_wrapper.php';

try {
    $pump = new SimplePumpWrapper('COM20');
    $info = $pump->getInfo();

    echo "System Info:\n";
    foreach ($info as $k => $v) {
        echo "  $k: $v\n";
    }

    echo "\nTesting connection...\n";
    $result = $pump->test();
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "Message: " . ($result['message'] ?? 'N/A') . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>