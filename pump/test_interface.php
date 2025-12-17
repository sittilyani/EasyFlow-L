<?php
// pump/test_interface.php
header('Content-Type: text/html; charset=utf-8');

// Try to get current configuration
$config = [];
try {
    $config_json = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/pump_api.php?action=config');
    $config_data = json_decode($config_json, true);
    if ($config_data && isset($config_data['config'])) {
        $config = $config_data['config'];
    }
} catch (Exception $e) {
    $config = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Masterflex Pump Control - COM20</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        h1 i { color: #667eea; }
        .info-box {
            background: linear-gradient(to right, #e3f2fd, #f3e5f5);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #667eea;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .status-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        .status-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .status-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .control-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .control-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .control-group h3 {
            color: #495057;
            margin-top: 0;
            border-bottom: 2px solid #6c757d;
            padding-bottom: 10px;
        }
        button {
            width: 100%;
            padding: 15px;
            margin: 8px 0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        button:active {
            transform: translateY(0);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        .btn-test { background: #17a2b8; color: white; }
        .btn-test:hover { background: #138496; }
        .btn-init { background: #6f42c1; color: white; }
        .btn-init:hover { background: #5a32a3; }
        .btn-start { background: #28a745; color: white; }
        .btn-start:hover { background: #218838; }
        .btn-stop { background: #dc3545; color: white; }
        .btn-stop:hover { background: #c82333; }
        .btn-dispense { background: #fd7e14; color: white; }
        .btn-dispense:hover { background: #e06c10; }
        .btn-command { background: #20c997; color: white; }
        .btn-command:hover { background: #1aa179; }
        .btn-config { background: #6c757d; color: white; }
        .btn-config:hover { background: #545b62; }

        .result-area {
            margin: 30px 0;
            min-height: 100px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #bee5eb;
        }
        .log-container {
            background: #212529;
            color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
        }
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #495057;
        }
        .log-time { color: #6c757d; }
        .log-success { color: #20c997; }
        .log-error { color: #dc3545; }
        .log-info { color: #17a2b8; }
        .log-command { color: #ffc107; }

        .config-form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            margin: 20px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .control-panel { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>
            <i class="fas fa-tint"></i>
            Masterflex Pump Control System
            <span style="font-size: 14px; color: #6c757d; margin-left: auto;">COM20</span>
        </h1>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> System Status</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Port</div>
                    <div class="status-value">COM20</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Tubing</div>
                    <div class="status-value" id="tubingStatus"><?php echo $config['tubing_diameter'] ?? '14.7'; ?> mm</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Default Rate</div>
                    <div class="status-value" id="rateStatus"><?php echo $config['default_rate'] ?? '10'; ?> ml/min</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Direction</div>
                    <div class="status-value" id="dirStatus"><?php echo $config['direction'] ?? 'INF'; ?></div>
                </div>
            </div>
        </div>

        <div class="control-panel">
            <div class="control-group">
                <h3><i class="fas fa-plug"></i> Connection</h3>
                <button class="btn-test" onclick="runTest('test')">
                    <i class="fas fa-wifi"></i> Test Connection
                </button>
                <button class="btn-init" onclick="runTest('init')">
                    <i class="fas fa-power-off"></i> Initialize Pump
                </button>
                <button class="btn-config" onclick="showConfig()">
                    <i class="fas fa-cog"></i> Configuration
                </button>
            </div>

            <div class="control-group">
                <h3><i class="fas fa-play-circle"></i> Basic Control</h3>
                <button class="btn-start" onclick="runTest('start')">
                    <i class="fas fa-play"></i> Start Pump
                </button>
                <button class="btn-stop" onclick="runTest('stop')">
                    <i class="fas fa-stop"></i> Stop Pump
                </button>
                <button class="btn-command" onclick="runCommand('?')">
                    <i class="fas fa-question-circle"></i> Get Status
                </button>
            </div>

            <div class="control-group">
                <h3><i class="fas fa-syringe"></i> Dispensing</h3>
                <button class="btn-dispense" onclick="dispense(1)">
                    <i class="fas fa-tint"></i> 1 ml
                </button>
                <button class="btn-dispense" onclick="dispense(5)">
                    <i class="fas fa-tint"></i> 5 ml
                </button>
                <button class="btn-dispense" onclick="dispense(10)">
                    <i class="fas fa-tint"></i> 10 ml
                </button>
                <button class="btn-dispense" onclick="customDispense()">
                    <i class="fas fa-edit"></i> Custom Volume
                </button>
            </div>

            <div class="control-group">
                <h3><i class="fas fa-terminal"></i> Manual Commands</h3>
                <button class="btn-command" onclick="runCommand('RUN')">
                    <i class="fas fa-play"></i> RUN
                </button>
                <button class="btn-command" onclick="runCommand('STOP')">
                    <i class="fas fa-stop"></i> STOP
                </button>
                <button class="btn-command" onclick="runCommand('VOL?')">
                    <i class="fas fa-ruler"></i> VOL?
                </button>
                <button class="btn-command" onclick="runCommand('RAT?')">
                    <i class="fas fa-tachometer-alt"></i> RAT?
                </button>
            </div>
        </div>

        <div id="configForm" class="config-form" style="display: none;">
            <h3><i class="fas fa-sliders-h"></i> Pump Configuration</h3>
            <form onsubmit="saveConfig(event)">
                <div class="form-group">
                    <label for="tubing_diameter">Tubing Diameter (mm):</label>
                    <select id="tubing_diameter" required>
                        <option value="13">13 mm (Masterflex 13)</option>
                        <option value="14.7" selected>14.7 mm (Masterflex 14)</option>
                        <option value="16">16 mm (Masterflex 16)</option>
                        <option value="19">19 mm (Masterflex 19)</option>
                        <option value="25">25 mm (Masterflex 25)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="default_rate">Default Flow Rate (ml/min):</label>
                    <input type="number" id="default_rate" value="10" min="0.1" max="100" step="0.1" required>
                </div>

                <div class="form-group">
                    <label for="direction">Default Direction:</label>
                    <select id="direction" required>
                        <option value="INF">INFUSE (Pump Out)</option>
                        <option value="WDR">WITHDRAW (Pump In)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="max_volume">Maximum Volume (ml):</label>
                    <input type="number" id="max_volume" value="1000" min="10" max="10000" step="10" required>
                </div>

                <button type="submit" class="btn-config">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
                <button type="button" class="btn-stop" onclick="hideConfig()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>

        <div class="result-area" id="result"></div>

        <h3><i class="fas fa-history"></i> Activity Log</h3>
        <div class="log-container" id="log">
            <div class="log-entry">
                <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                <span class="log-info">System ready</span>
            </div>
        </div>
    </div>

    <script>
    let isProcessing = false;
    let logEntries = [];

    function addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const typeClass = 'log-' + type;

        logEntries.push({
            time: timestamp,
            message: message,
            type: type
        });

        // Keep last 50 entries
        if (logEntries.length > 50) {
            logEntries.shift();
        }

        // Update display
        const logContainer = document.getElementById('log');
        logContainer.innerHTML = '';

        logEntries.forEach(entry => {
            const div = document.createElement('div');
            div.className = 'log-entry';
            div.innerHTML = `
                <span class="log-time">[${entry.time}]</span>
                <span class="log-${entry.type}">${escapeHtml(entry.message)}</span>
            `;
            logContainer.appendChild(div);
        });

        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function disableButtons(disable) {
        document.querySelectorAll('button').forEach(btn => {
            if (!btn.hasAttribute('type') || btn.getAttribute('type') !== 'submit') {
                btn.disabled = disable;
            }
        });
        isProcessing = disable;
    }

    function runTest(action) {
        if (isProcessing) return;

        disableButtons(true);
        addLog(`Starting ${action}...`, 'info');

        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `<div class="info">Running ${action}...</div>`;

        fetch(`pump_api.php?action=${action}`)
            .then(response => response.json())
            .then(data => {
                const success = data.success;

                if (success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4><i class="fas fa-check-circle"></i> Success</h4>
                            <p>${data.message || 'Operation completed successfully'}</p>
                        </div>
                    `;
                    addLog(`${action}: ${data.message}`, 'success');

                    // Update status display if config changed
                    if (data.config) {
                        if (data.config.tubing_diameter) {
                            document.getElementById('tubingStatus').textContent = data.config.tubing_diameter + ' mm';
                        }
                        if (data.config.default_rate) {
                            document.getElementById('rateStatus').textContent = data.config.default_rate + ' ml/min';
                        }
                        if (data.config.direction) {
                            document.getElementById('dirStatus').textContent = data.config.direction;
                        }
                    }
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4><i class="fas fa-exclamation-circle"></i> Error</h4>
                            <p>${data.message || 'Operation failed'}</p>
                        </div>
                    `;
                    addLog(`${action} failed: ${data.message}`, 'error');
                }

                // Show detailed output if available
                if (data.raw_output && data.raw_output.length > 0) {
                    const details = document.createElement('details');
                    details.innerHTML = `
                        <summary>Show Details</summary>
                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
${escapeHtml(data.raw_output.join('\n'))}
                        </pre>
                    `;
                    resultDiv.appendChild(details);
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4><i class="fas fa-exclamation-triangle"></i> Network Error</h4>
                        <p>${error}</p>
                    </div>
                `;
                addLog(`Network error: ${error}`, 'error');
            })
            .finally(() => {
                disableButtons(false);
            });
    }

    function dispense(amount) {
        if (isProcessing) return;

        disableButtons(true);
        addLog(`Dispensing ${amount} ml...`, 'info');

        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `<div class="info">Dispensing ${amount} ml...</div>`;

        fetch(`pump_api.php?action=dispense&amount=${amount}`)
            .then(response => response.json())
            .then(data => {
                const success = data.success;

                if (success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4><i class="fas fa-check-circle"></i> Dispense Complete</h4>
                            <p>${data.message || 'Dispense completed successfully'}</p>
                        </div>
                    `;
                    addLog(`Dispensed ${amount} ml: ${data.message}`, 'success');
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4><i class="fas fa-exclamation-circle"></i> Dispense Failed</h4>
                            <p>${data.message || 'Dispense operation failed'}</p>
                        </div>
                    `;
                    addLog(`Dispense ${amount} ml failed: ${data.message}`, 'error');
                }

                if (data.raw_output && data.raw_output.length > 0) {
                    const details = document.createElement('details');
                    details.innerHTML = `
                        <summary>Show Details</summary>
                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
${escapeHtml(data.raw_output.join('\n'))}
                        </pre>
                    `;
                    resultDiv.appendChild(details);
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4><i class="fas fa-exclamation-triangle"></i> Network Error</h4>
                        <p>${error}</p>
                    </div>
                `;
                addLog(`Network error: ${error}`, 'error');
            })
            .finally(() => {
                disableButtons(false);
            });
    }

    function customDispense() {
        if (isProcessing) return;

        const amount = prompt('Enter volume to dispense (ml):', '10');
        if (!amount || isNaN(amount) || amount <= 0) {
            alert('Please enter a valid amount greater than 0');
            return;
        }

        // Optional: ask for rate
        const rate = prompt('Enter flow rate (ml/min) or leave empty for default:', '');

        if (rate && !isNaN(rate) && rate > 0) {
            // Dispense with custom rate
            if (isProcessing) return;

            disableButtons(true);
            addLog(`Dispensing ${amount} ml at ${rate} ml/min...`, 'info');

            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = `<div class="info">Dispensing ${amount} ml at ${rate} ml/min...</div>`;

            fetch(`pump_api.php?action=dispense&amount=${amount}&rate=${rate}`)
                .then(response => response.json())
                .then(data => handleDispenseResponse(data, amount))
                .catch(error => handleDispenseError(error))
                .finally(() => disableButtons(false));
        } else {
            // Dispense with default rate
            dispense(amount);
        }
    }

    function handleDispenseResponse(data, amount) {
        const resultDiv = document.getElementById('result');
        const success = data.success;

        if (success) {
            resultDiv.innerHTML = `
                <div class="success">
                    <h4><i class="fas fa-check-circle"></i> Dispense Complete</h4>
                    <p>${data.message || 'Dispense completed successfully'}</p>
                </div>
            `;
            addLog(`Dispensed ${amount} ml: ${data.message}`, 'success');
        } else {
            resultDiv.innerHTML = `
                <div class="error">
                    <h4><i class="fas fa-exclamation-circle"></i> Dispense Failed</h4>
                    <p>${data.message || 'Dispense operation failed'}</p>
                </div>
            `;
            addLog(`Dispense ${amount} ml failed: ${data.message}`, 'error');
        }
    }

    function handleDispenseError(error) {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `
            <div class="error">
                <h4><i class="fas fa-exclamation-triangle"></i> Network Error</h4>
                <p>${error}</p>
            </div>
        `;
        addLog(`Network error: ${error}`, 'error');
    }

    function runCommand(command) {
        if (isProcessing) return;

        disableButtons(true);
        addLog(`Sending: ${command}`, 'command');

        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `<div class="info">Sending command: ${command}</div>`;

        fetch(`pump_api.php?action=command&command=${encodeURIComponent(command)}`)
            .then(response => response.json())
            .then(data => {
                const success = data.success;

                if (success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4><i class="fas fa-check-circle"></i> Command Sent</h4>
                            <p>Command: ${command}</p>
                            <p>Response: ${data.response || 'No response'}</p>
                        </div>
                    `;
                    addLog(`Response: ${data.response || 'No response'}`, 'success');
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4><i class="fas fa-exclamation-circle"></i> Command Failed</h4>
                            <p>${data.message || 'Command failed'}</p>
                        </div>
                    `;
                    addLog(`Command failed: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4><i class="fas fa-exclamation-triangle"></i> Network Error</h4>
                        <p>${error}</p>
                    </div>
                `;
                addLog(`Network error: ${error}`, 'error');
            })
            .finally(() => {
                disableButtons(false);
            });
    }

    function showConfig() {
        // Load current config into form
        fetch('pump_api.php?action=config')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.config) {
                    const config = data.config;
                    document.getElementById('tubing_diameter').value = config.tubing_diameter || '14.7';
                    document.getElementById('default_rate').value = config.default_rate || '10';
                    document.getElementById('direction').value = config.direction || 'INF';
                    document.getElementById('max_volume').value = config.max_volume || '1000';
                }
            });

        document.getElementById('configForm').style.display = 'block';
    }

    function hideConfig() {
        document.getElementById('configForm').style.display = 'none';
    }

    function saveConfig(event) {
        event.preventDefault();

        const tubing = document.getElementById('tubing_diameter').value;
        const rate = document.getElementById('default_rate').value;
        const direction = document.getElementById('direction').value;
        const maxVolume = document.getElementById('max_volume').value;

        const param = `tubing_diameter=${tubing}&default_rate=${rate}&direction=${direction}&max_volume=${maxVolume}`;

        // Save each parameter individually
        const params = [
            `tubing_diameter=${tubing}`,
            `default_rate=${rate}`,
            `direction=${direction}`,
            `max_volume=${maxVolume}`
        ];

        // Disable form while saving
        const form = event.target;
        const buttons = form.querySelectorAll('button');
        buttons.forEach(btn => btn.disabled = true);

        addLog('Saving configuration...', 'info');

        // Save each parameter
        let savedCount = 0;
        params.forEach(param => {
            fetch(`pump_api.php?action=config&param=${encodeURIComponent(param)}`)
                .then(response => response.json())
                .then(data => {
                    savedCount++;

                    if (data.success) {
                        addLog(data.message, 'success');

                        // Update status display
                        if (param.startsWith('tubing_diameter')) {
                            document.getElementById('tubingStatus').textContent = tubing + ' mm';
                        } else if (param.startsWith('default_rate')) {
                            document.getElementById('rateStatus').textContent = rate + ' ml/min';
                        } else if (param.startsWith('direction')) {
                            document.getElementById('dirStatus').textContent = direction;
                        }

                        if (savedCount === params.length) {
                            const resultDiv = document.getElementById('result');
                            resultDiv.innerHTML = `
                                <div class="success">
                                    <h4><i class="fas fa-check-circle"></i> Configuration Saved</h4>
                                    <p>All settings have been saved successfully</p>
                                </div>
                            `;
                            hideConfig();
                        }
                    } else {
                        addLog(`Failed to save ${param}: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    addLog(`Error saving ${param}: ${error}`, 'error');
                });
        });

        // Re-enable buttons after all saves complete
        setTimeout(() => {
            buttons.forEach(btn => btn.disabled = false);
        }, 3000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize
    addLog('Pump control system loaded', 'info');
    addLog('Port: COM20, Baud: 9600', 'info');
    </script>
</body>
</html>