<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Simple Pump Test (Fixed) ===\n";

try {
    // Include controller
    require_once 'MasterflexPumpController.php';

    // Create pump instance
    $pump = new MasterflexPumpController('COM3');
    echo "? Controller created\n";

    // Try manual configuration first
    echo "Attempting manual port configuration...\n";
    if ($pump->configurePortManually()) {
        echo "? Manual configuration successful\n";
    } else {
        echo "? Manual configuration not available, using direct access\n";
    }

    // Connect
    echo "Connecting to pump...\n";
    if ($pump->connect()) {
        echo "? Connected to pump\n";

        // Wake up
        echo "Waking up pump...\n";
        $response = $pump->wakeUp();
        echo "Wake-up response: " . ($response ?: 'None') . "\n";

        // Test tiny dispense
        echo "Testing 0.1 ml dispense...\n";
        if ($pump->dispense(0.1)) {
            echo "? 0.1 ml dispense successful!\n";
        }

        // Disconnect
        $pump->disconnect();
        echo "? Disconnected\n";

    } else {
        echo "? Failed to connect\n";
    }

} catch (Exception $e) {
    echo "? ERROR: " . $e->getMessage() . "\n";
    echo "In file: " . $e->getFile() . " on line: " . $e->getLine() . "\n";
}

echo "=== Test Complete ===\n";
echo "</pre>";
?>