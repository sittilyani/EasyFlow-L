<?php
// Alternative pump control using system commands
function controlPumpViaCommand($amount_ml) {
    $command = "pump-control --dispense " . escapeshellarg($amount_ml);
    $output = [];
    $return_code = 0;

    exec($command, $output, $return_code);

    if ($return_code === 0) {
        return true;
    } else {
        error_log("Pump command failed: " . implode("\n", $output));
        return false;
    }
}