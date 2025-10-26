<?php
// test_web_controller_simple.php - Run in browser
require_once 'PumpWebController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Testing Web Controller ===\n\n";

try {
    $controller = new PumpWebController();

    echo "1. Queue Status:\n";
    $status = $controller->getQueueStatus();
    echo "   Commands in queue: " . $status['queued_commands'] . "\n";
    echo "   Pending results: " . $status['pending_results'] . "\n\n";

    echo "2. Sending wake-up command...\n";
    $result = $controller->wakeUp();
    echo "   ? Command sent to service\n";
    echo "   Result: ";
    print_r($result);
    echo "\n";

    echo "3. Sending test dispense (0.1 ml)...\n";
    $result = $controller->dispense(0.1);
    echo "   ? Dispense command sent\n";
    echo "   Result: ";
    print_r($result);
    echo "\n";

    echo "4. Final queue status:\n";
    $status = $controller->getQueueStatus();
    echo "   Commands in queue: " . $status['queued_commands'] . "\n";
    echo "   Pending results: " . $status['pending_results'] . "\n\n";

    echo "?? SUCCESS! Web controller is communicating with pump service!\n";

} catch (Exception $e) {
    echo "? ERROR: " . $e->getMessage() . "\n";
    echo "This might mean:\n";
    echo "- Pump service is not running\n";
    echo "- Service can't access COM3\n";
    echo "- Command timeout occurred\n";
}

echo "</pre>";
?>