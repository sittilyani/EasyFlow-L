<?php
// verify_service_running.php
echo "<pre>";
echo "=== Verifying Pump Service Status ===\n\n";

// Check 1: PHP processes
echo "1. Checking PHP processes:\n";
exec('tasklist /fi "imagename eq php.exe" /fo table', $output);
if (count($output) > 3) {
    echo "   ? PHP processes found:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
} else {
    echo "   ? No PHP processes found - service NOT running\n";
}

echo "\n2. Checking service files:\n";
$files = [
    'pump_service.log' => 'Service Log',
    'pump_service.lock' => 'Service Lock'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "   ? $desc: EXISTS (Modified: $modified)\n";

        if ($file === 'pump_service.log') {
            echo "   Recent log entries:\n";
            $log = file_get_contents($file);
            $lines = array_slice(explode("\n", $log), -5);
            foreach ($lines as $line) {
                if (trim($line)) echo "     $line\n";
            }
        }
    } else {
        echo "   ? $desc: MISSING\n";
    }
}

echo "\n3. Checking queue status:\n";
if (file_exists('pump_commands.json')) {
    $queue = json_decode(file_get_contents('pump_commands.json'), true) ?? [];
    echo "   Commands in queue: " . count($queue) . "\n";

    if (file_exists('pump_results.json')) {
        $results = json_decode(file_get_contents('pump_results.json'), true) ?? [];
        echo "   Pending results: " . count($results) . "\n";
    }
}

echo "\n</pre>";
?>