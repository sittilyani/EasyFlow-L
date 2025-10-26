<?php
// debug_queue.php - Check queue files
echo "<pre>";
echo "=== Debug Queue Files ===\n\n";

// Check commands
if (file_exists('pump_commands.json')) {
    $commands = json_decode(file_get_contents('pump_commands.json'), true);
    echo "Commands in queue: " . (is_array($commands) ? count($commands) : 'Invalid JSON') . "\n";
    if (is_array($commands) && !empty($commands)) {
        foreach ($commands as $id => $cmd) {
            echo "  [$id] {$cmd['action']} - {$cmd['description']}\n";
        }
    }
} else {
    echo "No commands file\n";
}

echo "\n";

// Check results
if (file_exists('pump_results.json')) {
    $results = json_decode(file_get_contents('pump_results.json'), true);
    echo "Pending results: " . (is_array($results) ? count($results) : 'Invalid JSON') . "\n";
    if (is_array($results) && !empty($results)) {
        foreach ($results as $id => $result) {
            echo "  [$id] {$result['status']}\n";
        }
    }
} else {
    echo "No results file\n";
}

echo "\n</pre>";
?>