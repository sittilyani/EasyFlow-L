<?php
require_once '../pump/MasterflexPump.php';

$pump = new MasterflexPump();

try {
    if ($pump->connect()) {
        echo "Connected to pump successfully.\n";

        // Test with a small amount (e.g., 1 mg = 0.1 ml for methadone)
        $test_dosage_mg = 1.0;
        $test_dosage_ml = MasterflexPump::dosageToMl($test_dosage_mg);

        if ($pump->isReady()) {
            if ($pump->dispense($test_dosage_ml)) {
                echo "Successfully dispensed $test_dosage_mg mg ($test_dosage_ml ml).\n";
            } else {
                echo "Dispense failed.\n";
            }
        } else {
            echo "Pump not ready.\n";
        }

        $pump->disconnect();
        echo "Disconnected from pump.\n";
    } else {
        echo "Failed to connect to pump.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>