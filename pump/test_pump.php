<?php
require_once 'MasterflexPump.php';

$pump = new MasterflexPump();

try {
    echo "Attempting to connect to pump on " . PUMP_SERIAL_PORT . "...\n";
    if ($pump->connect()) {
        echo "Connected to pump successfully.\n";

        // Test with a small amount (1 mg = 0.1 ml for methadone)
        $test_dosage_mg = 1.0;
        $test_dosage_ml = MasterflexPump::dosageToMl($test_dosage_mg);
        echo "Preparing to dispense $test_dosage_mg mg ($test_dosage_ml ml)...\n";

        if ($pump->isReady()) {
            if ($pump->dispense($test_dosage_ml)) {
                echo "Successfully dispensed $test_dosage_mg mg ($test_dosage_ml ml).\n";
            } else {
                echo "Dispense failed. Check error log for details.\n";
            }
        } else {
            echo "Pump not ready. Check connection and pump status.\n";
        }

        $pump->disconnect();
        echo "Disconnected from pump.\n";
    } else {
        echo "Failed to connect to pump. Check error log for details.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $pump->disconnect();
}
?>