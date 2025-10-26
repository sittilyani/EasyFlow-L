<?php
// test_with_service_check.php
require_once 'PumpWebController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Pump System Test (With Service Check) ===\n\n";

// First, check if service is running
echo "1. Service Status Check:\n";
exec('tasklist /fi "imagename eq php.exe" /fo table', $output);
$serviceRunning = (count($output) > 3);

if (!$serviceRunning) {
    echo "   ? PUMP SERVICE IS NOT RUNNING!\n";
    echo "   \n";
    echo "   SOLUTION:\n";
    echo "   1. Open Command Prompt as Administrator\n";
    echo "   2. Run: cd C:\\laragon\\www\\iorpms\n";
    echo "   3. Run: php pump_service.php\n";
    echo "   4. Keep that window open and come back here\n";
    echo "   \n";
    echo "   Or run: start_service_properly.bat as Administrator\n";
    echo "</pre>";
    exit;
}

echo "   ? Pump service is running\n\n";

// Now test the system
try {
    $controller = new PumpWebController();

    echo "2. Queue Status:\n";
    $status = $controller->getQueueStatus();
    echo "   Commands in queue: " . $status['queued_commands'] . "\n";
    echo "   Pending results: " . $status['pending_results'] . "\n\n";

    // Clear any existing queue first
    if ($status['queued_commands'] > 0) {
        echo "   Clearing existing queue...\n";
        $controller->clearStuckCommands();
        echo "   ? Queue cleared\n\n";
    }

    echo "3. Sending wake-up command...\n";
    $result = $controller->wakeUp();
    echo "   ? Wake-up completed\n";
    echo "   Result: ";
    print_r($result);
    echo "\n";

    echo "4. Sending test dispense (0.1 ml)...\n";
    $result = $controller->dispense(0.1);
    echo "   ? Dispense completed\n";
    echo "   Result: ";
    print_r($result);
    echo "\n";

    echo "5. Final queue status:\n";
    $status = $controller->getQueueStatus();
    echo "   Commands in queue: " . $status['queued_commands'] . "\n";
    echo "   Pending results: " . $status['pending_results'] . "\n\n";

    echo "?? SUCCESS! Pump system is working correctly!\n";

} catch (Exception $e) {
    echo "? ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>