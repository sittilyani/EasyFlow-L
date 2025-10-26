<?php
// check_service_activity.php - Run in browser
echo "<pre>";
echo "=== Checking Pump Service Activity ===\n\n";

// Check if service files exist
$logExists = file_exists('pump_service.log');
$commandsExist = file_exists('pump_commands.json');
$resultsExist = file_exists('pump_results.json');

echo "Service Files:\n";
echo "pump_service.log: " . ($logExists ? '? EXISTS' : '? MISSING') . "\n";
echo "pump_commands.json: " . ($commandsExist ? '? EXISTS' : '? MISSING') . "\n";
echo "pump_results.json: " . ($resultsExist ? '? EXISTS' : '? MISSING') . "\n\n";

if ($logExists) {
    echo "Last log entries:\n";
    $logContent = file_get_contents('pump_service.log');
    $lines = array_slice(explode("\n", $logContent), -10); // Last 10 lines
    foreach ($lines as $line) {
        if (trim($line)) echo "  " . $line . "\n";
    }
}

echo "\n</pre>";
?>