<?php
// test_windows_controller.php
require_once 'PumpWindowsController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Testing Windows Command Controller ===\n\n";

try {
    $controller = new PumpWindowsController();

    echo "1. Testing connection...\n";
    $result = $controller->testConnection();
    print_r($result);
    echo "\n";

    if ($result['connected']) {
        echo "2. Sending wake-up...\n";
        $result = $controller->wakeUp();
        echo "   ? Wake-up sent\n\n";

        echo "3. Testing dispense (0.1 ml)...\n";
        $result = $controller->dispense(0.1);
        echo "   ? Dispense completed\n";
        echo "   Method: " . $result['method_used'] . "\n";
        echo "   Amount: " . $result['dispensed_ml'] . " ml\n\n";

        echo "?? SUCCESS! Using Windows commands instead of PHP fopen()\n";
    } else {
        echo "? Cannot connect via Windows commands\n";
    }

} catch (Exception $e) {
    echo "? ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>