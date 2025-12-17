<?php
// pump_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? $_POST['action'] ?? 'test';
$amount = $_GET['amount'] ?? $_POST['amount'] ?? 0;
$command = $_GET['command'] ?? $_POST['command'] ?? '';
$rate = $_GET['rate'] ?? $_POST['rate'] ?? 0;
$param = $_GET['param'] ?? $_POST['param'] ?? '';

// Paths - UPDATE THESE FOR YOUR SYSTEM
$python_exe = 'C:/laragon/bin/python/python-3.13/python.exe';
$python_script = __DIR__ . '/pump_controller.py';

// Verify Python script exists
if (!file_exists($python_script)) {
    echo json_encode([
        'success' => false,
        'message' => 'Python script not found: ' . $python_script
    ]);
    exit;
}

// Build command based on action
$cmd = '';
switch ($action) {
    case 'test':
        $cmd = escapeshellarg($python_exe) . ' ' .
               escapeshellarg($python_script) . ' test 2>&1';
        break;

    case 'init':
    case 'wakeup':
        $cmd = escapeshellarg($python_exe) . ' ' .
               escapeshellarg($python_script) . ' init 2>&1';
        break;

    case 'start':
        if ($rate > 0) {
            $cmd = escapeshellarg($python_exe) . ' ' .
                   escapeshellarg($python_script) . ' start --rate ' . escapeshellarg($rate) . ' 2>&1';
        } else {
            $cmd = escapeshellarg($python_exe) . ' ' .
                   escapeshellarg($python_script) . ' start 2>&1';
        }
        break;

    case 'stop':
        $cmd = escapeshellarg($python_exe) . ' ' .
               escapeshellarg($python_script) . ' stop 2>&1';
        break;

    case 'dispense':
        if ($amount > 0) {
            if ($rate > 0) {
                $cmd = escapeshellarg($python_exe) . ' ' .
                       escapeshellarg($python_script) . ' dispense --amount ' . escapeshellarg($amount) .
                       ' --rate ' . escapeshellarg($rate) . ' 2>&1';
            } else {
                $cmd = escapeshellarg($python_exe) . ' ' .
                       escapeshellarg($python_script) . ' dispense --amount ' . escapeshellarg($amount) . ' 2>&1';
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Amount required for dispense'
            ]);
            exit;
        }
        break;

    case 'status':
        $cmd = escapeshellarg($python_exe) . ' ' .
               escapeshellarg($python_script) . ' status 2>&1';
        break;

    case 'command':
    case 'manual':
        if (!empty($command)) {
            $cmd = escapeshellarg($python_exe) . ' ' .
                   escapeshellarg($python_script) . ' command --command ' . escapeshellarg($command) . ' 2>&1';
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Command required'
            ]);
            exit;
        }
        break;

    case 'config':
        if (!empty($param)) {
            $cmd = escapeshellarg($python_exe) . ' ' .
                   escapeshellarg($python_script) . ' config --param ' . escapeshellarg($param) . ' 2>&1';
        } else {
            $cmd = escapeshellarg($python_exe) . ' ' .
                   escapeshellarg($python_script) . ' config 2>&1';
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Unknown action: ' . $action
        ]);
        exit;
}

// Execute command
exec($cmd, $output, $return_code);

// Parse JSON output from Python script
$result = ['success' => false, 'action' => $action];
$raw_output = implode("\n", $output);

try {
    // Look for JSON in output
    foreach ($output as $line) {
        $line = trim($line);
        if ($line && ($line[0] === '{' || $line[0] === '[')) {
            $json_result = json_decode($line, true);
            if ($json_result !== null) {
                $result = array_merge($result, $json_result);
                break;
            }
        }
    }

    // If no JSON found, use raw output
    if (!$result['message']) {
        $result['success'] = $return_code === 0;
        $result['message'] = $raw_output;
    }

} catch (Exception $e) {
    $result['message'] = 'Error parsing output: ' . $e->getMessage();
}

// Add raw output for debugging
$result['raw_output'] = $output;
$result['command'] = $cmd;
$result['return_code'] = $return_code;

echo json_encode($result, JSON_PRETTY_PRINT);
?>