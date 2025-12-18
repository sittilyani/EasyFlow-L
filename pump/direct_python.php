<?php
// pump/direct_python.php

header('Content-Type: text/plain');

// Your Python script location
$pythonScript = realpath(__DIR__ . '/../pump/pump_controller.py');

if (!$pythonScript || !file_exists($pythonScript)) {
    echo "ERROR: Python script not found!\n";
    echo "Expected: C:\laragon\www\iorpms\pump_controller.py\n";
    echo "Current dir: " . __DIR__ . "\n";
    echo "Script path: " . $pythonScript . "\n";
    exit;
}

// Your Python executable
$pythonExe = 'C:\laragon\bin\python\python-3.13\python.exe';

if (!file_exists($pythonExe)) {
    // Try to find Python
    exec('python --version 2>&1', $output, $return);
    if ($return === 0) {
        $pythonExe = 'python';
    } else {
        echo "ERROR: Python not found!\n";
        echo "Please make sure Python is installed and in PATH\n";
        exit;
    }
}

// Get action
$action = $_GET['action'] ?? 'test';
$amount = $_GET['amount'] ?? '5';

// Build command
$cmd = escapeshellarg($pythonExe) . ' ' .
       escapeshellarg($pythonScript) . ' ' .
       escapeshellarg($action);

if ($action === 'dispense') {
    $cmd .= ' ' . escapeshellarg($amount);
}

// Add 2>&1 to capture all output
$cmd .= ' 2>&1';

echo "Command: $cmd\n";
echo "---\n\n";

// Execute command
exec($cmd, $output, $returnCode);

// Display output
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n---\n";
echo "Return code: $returnCode\n";
?>