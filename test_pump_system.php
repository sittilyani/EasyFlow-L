<?php
require_once 'PumpWebController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Pump System Test ===\n";

try {
    $controller = new PumpWebController();

    echo "1. Checking queue status:\n";
    print_r($controller->getQueueStatus());

    echo "\n2. Testing pump connection...\n";
    $result = $controller->testConnection();
    echo "Connection test result:\n";
    print_r($result);

    echo "\n3. Waking up pump...\n";
    $result = $controller->wakeUp();
    echo "Wake-up result:\n";
    print_r($result);

    echo "\n4. Dispensing 0.1 ml...\n";
    $result = $controller->dispense(0.1);
    echo "Dispense result:\n";
    print_r($result);

    echo "\n5. Final queue status:\n";
    print_r($controller->getQueueStatus());

    echo "\n? All tests completed successfully!\n";

} catch (Exception $e) {
    echo "? Error: " . $e->getMessage() . "\n";
    echo "Make sure pump_service.php is running as Administrator!\n";
}

echo "</pre>";
?>