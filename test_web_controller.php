<?php
require_once 'PumpWebController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Web Controller Test ===\n";

try {
    $controller = new PumpWebController();

    echo "Queue status: ";
    print_r($controller->getQueueStatus());

    echo "Waking up pump...\n";
    $result = $controller->queueWakeUp();
    echo "Wake-up result: ";
    print_r($result);

    echo "Dispensing 0.1 ml...\n";
    $result = $controller->queueDispense(0.1);
    echo "Dispense result: ";
    print_r($result);

    echo "? All tests completed!\n";

} catch (Exception $e) {
    echo "? Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>