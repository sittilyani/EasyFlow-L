<?php
// xampp/www/iorpms/pump/test_pump.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include pump manager
require_once __DIR__ . '/pump_manager.php';

$pumpManager = PumpManager::getInstance();
$status = $pumpManager->getStatus();

?>
<!DOCTYPE html>
<html>
<head>
    <title>MasterPlex Pump Test - IORPMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .section {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .section h2 {
            margin-top: 0;
            color: #007bff;
        }
        button {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        button:hover {
            background: #0056b3;
        }
        button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .success {
            color: #28a745;
            font-weight: bold;
            background: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
            background: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #17a2b8;
            background: #d1ecf1;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .log {
            background: #343a40;
            color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            margin: 10px 0;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .status-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .status-item .label {
            font-weight: bold;
            color: #495057;
        }
        .status-item .value {
            color: #212529;
        }
        input[type="number"] {
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>MasterPlex Pump Test Interface</h1>
    <p>IORPMS Dispensing System - Testing FT232R USB Pump</p>

    <!-- Current Status Section -->
    <div class="section">
        <h2>Current Status</h2>
        <div class="status-grid">
            <div class="status-item">
                <div class="label">Mode</div>
                <div class="value"><?php echo $status['simulation_mode'] ? 'Simulation' : 'Live'; ?></div>
            </div>
            <div class="status-item">
                <div class="label">Initialized</div>
                <div class="value"><?php echo $status['initialized'] ? 'Yes' : 'No'; ?></div>
            </div>
            <div class="status-item">
                <div class="label">Last Activity</div>
                <div class="value"><?php echo $status['last_activity']; ?></div>
            </div>
            <div class="status-item">
                <div class="label">Inactive Time</div>
                <div class="value"><?php echo $status['inactive_seconds']; ?> seconds</div>
            </div>
        </div>

        <button onclick="refreshStatus()">Refresh Status</button>
        <button onclick="toggleSimulation()">Toggle Simulation Mode</button>
    </div>

    <!-- Connection Test -->
    <div class="section">
        <h2>Connection Test</h2>
        <p>Test the connection to the MasterPlex pump:</p>
        <button id="btnConnect" onclick="testConnection()">Test Connection</button>
        <div id="connectResult"></div>
    </div>

    <!-- Wakeup Test -->
    <div class="section">
        <h2>Wakeup Test</h2>
        <p>Send wakeup command to the pump:</p>
        <button id="btnWakeup" onclick="testWakeup()">Test Wakeup</button>
        <div id="wakeupResult"></div>
    </div>

    <!-- Dispense Test -->
    <div class="section">
        <h2>Dispense Test</h2>
        <p>Test dispensing with specified amount:</p>
        <label>Amount (ml): </label>
        <input type="number" id="amount" value="5" min="0.1" max="100" step="0.1">
        <button id="btnDispense" onclick="testDispense()">Test Dispense</button>
        <div id="dispenseResult"></div>
    </div>

    <!-- System Info -->
    <div class="section">
        <h2>System Information</h2>
        <div class="status-grid">
            <div class="status-item">
                <div class="label">PHP Version</div>
                <div class="value"><?php echo phpversion(); ?></div>
            </div>
            <div class="status-item">
                <div class="label">Operating System</div>
                <div class="value"><?php echo PHP_OS; ?></div>
            </div>
            <div class="status-item">
                <div class="label">Server Software</div>
                <div class="value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
            </div>
            <div class="status-item">
                <div class="label">Document Root</div>
                <div class="value"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></div>
            </div>
        </div>

        <h3>Detected Serial Ports:</h3>
        <div class="log">
            <?php
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                echo "Windows System\n";
                echo "Common COM ports: COM1-COM8\n";
                echo "FT232R usually appears as COM3 or COM4\n";
            } else {
                // Linux/Unix
                echo "Unix/Linux System\n";
                if (is_dir('/dev')) {
                    $devices = glob('/dev/tty*');
                    echo "Available tty devices:\n";
                    foreach ($devices as $device) {
                        echo "  $device\n";
                    }

                    if (is_dir('/dev/serial/by-id')) {
                        $ftdi = glob('/dev/serial/by-id/*FTDI*');
                        $ft232 = glob('/dev/serial/by-id/*FT232*');

                        if (!empty($ftdi)) {
                            echo "\nFTDI devices:\n";
                            foreach ($ftdi as $device) {
                                echo "  $device\n";
                            }
                        }

                        if (!empty($ft232)) {
                            echo "\nFT232 devices:\n";
                            foreach ($ft232 as $device) {
                                echo "  $device\n";
                            }
                        }
                    }
                }
            }
            ?>
        </div>
    </div>

    <!-- Live Log -->
    <div class="section">
        <h2>Live Log</h2>
        <button onclick="clearLog()">Clear Log</button>
        <div id="liveLog" class="log">Log will appear here...</div>
    </div>
</div>

<script>
let logEntries = [];

function addLog(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const entry = `[${timestamp}] ${message}`;
    logEntries.push({message: entry, type: type});

    // Update display
    const logDiv = document.getElementById('liveLog');
    let html = '';
    for (let i = Math.max(0, logEntries.length - 20); i < logEntries.length; i++) {
        const entry = logEntries[i];
        const cssClass = entry.type === 'error' ? 'error' :
                        entry.type === 'success' ? 'success' : 'info';
        html += `<div class="${cssClass}">${entry.message}</div>`;
    }
    logDiv.innerHTML = html;
    logDiv.scrollTop = logDiv.scrollHeight;
}

function clearLog() {
    logEntries = [];
    document.getElementById('liveLog').innerHTML = 'Log cleared';
}

function refreshStatus() {
    addLog('Refreshing status...');
    fetch('test_ajax.php?action=status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addLog('Status refreshed successfully', 'success');
                // You could update the status display here
            }
        })
        .catch(error => {
            addLog('Failed to refresh status: ' + error, 'error');
        });
}

function toggleSimulation() {
    addLog('Toggling simulation mode...');
    fetch('test_ajax.php?action=toggle_simulation')
        .then(response => response.json())
        .then(data => {
            addLog(data.message, data.success ? 'success' : 'error');
            setTimeout(refreshStatus, 1000);
        })
        .catch(error => {
            addLog('Request failed: ' + error, 'error');
        });
}

function testConnection() {
    const btn = document.getElementById('btnConnect');
    const resultDiv = document.getElementById('connectResult');

    btn.disabled = true;
    resultDiv.innerHTML = '<div class="info">Testing connection...</div>';
    addLog('Testing pump connection...');

    fetch('test_ajax.php?action=connection')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="success">? ' + data.message + '</div>';
                addLog('Connection test successful: ' + data.message, 'success');
            } else {
                resultDiv.innerHTML = '<div class="error">? ' + data.message + '</div>';
                addLog('Connection test failed: ' + data.message, 'error');
            }
            btn.disabled = false;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">Request failed: ' + error + '</div>';
            addLog('Request failed: ' + error, 'error');
            btn.disabled = false;
        });
}

function testWakeup() {
    const btn = document.getElementById('btnWakeup');
    const resultDiv = document.getElementById('wakeupResult');

    btn.disabled = true;
    resultDiv.innerHTML = '<div class="info">Sending wakeup command...</div>';
    addLog('Sending wakeup command to pump...');

    fetch('test_ajax.php?action=wakeup')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="success">? ' + data.message + '</div>';
                addLog('Wakeup successful: ' + data.message, 'success');
            } else {
                resultDiv.innerHTML = '<div class="error">? ' + data.message + '</div>';
                addLog('Wakeup failed: ' + data.message, 'error');
            }
            btn.disabled = false;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">Request failed: ' + error + '</div>';
            addLog('Request failed: ' + error, 'error');
            btn.disabled = false;
        });
}

function testDispense() {
    const amount = document.getElementById('amount').value;
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount (0.1 - 100 ml)');
        return;
    }

    const btn = document.getElementById('btnDispense');
    const resultDiv = document.getElementById('dispenseResult');

    btn.disabled = true;
    resultDiv.innerHTML = '<div class="info">Dispensing ' + amount + 'ml...</div>';
    addLog('Starting dispense of ' + amount + 'ml...');

    fetch('test_ajax.php?action=dispense&amount=' + amount)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="success">? ' + data.message + '</div>';
                addLog('Dispense successful: ' + data.message, 'success');
            } else {
                resultDiv.innerHTML = '<div class="error">? ' + data.message + '</div>';
                addLog('Dispense failed: ' + data.message, 'error');
            }
            btn.disabled = false;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">Request failed: ' + error + '</div>';
            addLog('Request failed: ' + error, 'error');
            btn.disabled = false;
        });
}

// Initial log
addLog('Pump test interface loaded');
addLog('System: <?php echo PHP_OS; ?>');
addLog('PHP: <?php echo phpversion(); ?>');
addLog('Mode: <?php echo $status['simulation_mode'] ? "Simulation" : "Live"; ?>');
</script>
</body>
</html>