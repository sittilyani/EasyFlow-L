<?php
$action = $_GET['action'] ?? 'test';
$amount = $_GET['amount'] ?? 0;

$python_script = 'C:/laragon/www/iorpms/pump/pump_controller.py';
$python_exe = 'C:/laragon/bin/python/python-3.13/python.exe';

$commands = [
    'diagnostic' => 'diagnostic',
    'test_ascii' => 'test ascii',
    'test_modbus' => 'test modbus',
    'wakeup' => 'wakeup',
    'start' => 'start',
    'stop' => 'stop',
    'emergency_stop' => 'emergency_stop',
    'status' => 'status',
    'version' => 'version',
    'settings' => 'settings',
    'dispense' => "dispense $amount"
];

if (isset($commands[$action])) {
    $cmd = escapeshellarg($python_exe) . ' ' .
           escapeshellarg($python_script) . ' ' .
           $commands[$action] . ' 2>&1';

    exec($cmd, $output, $return_code);

    echo '<pre>';
    echo "Command: $cmd\n";
    echo "Return: $return_code\n\n";
    echo "Output:\n" . implode("\n", $output);
    echo '</pre>';
} else {
    echo '<p class="error">Invalid action</p>';
}
?>