<?php
// check_service_status.php
echo "<pre>";
echo "=== Pump Service Status Check ===\n\n";

// Check if service files exist
$files = [
    'pump_service.log' => 'Service Log',
    'pump_commands.json' => 'Command Queue',
    'pump_results.json' => 'Results',
    'pump_service.lock' => 'Service Lock'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "? $description: EXISTS (Size: {$size} bytes, Modified: $modified)\n";

        if ($file === 'pump_commands.json') {
            $commands = json_decode(file_get_contents($file), true) ?? [];
            echo "   Commands in queue: " . count($commands) . "\n";
            foreach ($commands as $id => $cmd) {
                echo "   - [$id] {$cmd['action']} - {$cmd['description']}\n";
            }
        }

        if ($file === 'pump_service.log') {
            echo "   Last log entries:\n";
            $log = file_get_contents($file);
            $lines = array_slice(explode("\n", $log), -10);
            foreach ($lines as $line) {
                if (trim($line)) echo "     $line\n";
            }
        }

    } else {
        echo "? $description: MISSING\n";
    }
    echo "\n";
}

// Check if PHP processes are running
echo "PHP Processes:\n";
exec('tasklist /fi "imagename eq php.exe" /fo table', $output);
if (count($output) > 3) {
    foreach ($output as $line) {
        echo "   $line\n";
    }
} else {
    echo "   No PHP processes found - service is NOT running!\n";
}

echo "</pre>";
?>