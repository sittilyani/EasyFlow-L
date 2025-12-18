<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cole-Parmer Pump Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-group { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 10px 15px; margin: 5px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { opacity: 0.9; }
        button.danger { background: #dc3545; }
        button.warning { background: #ffc107; color: black; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #007bff; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <h1>Cole-Parmer 2000-0078 Pump Test</h1>

    <div class="test-group">
        <h3>Protocol Tests</h3>
        <button onclick="runTest('diagnostic')">Run Full Diagnostic</button>
        <button onclick="runTest('test_ascii')">Test ASCII Protocol</button>
        <button onclick="runTest('test_modbus')">Test Modbus Protocol</button>
    </div>

    <div class="test-group">
        <h3>Standard Operations</h3>
        <button onclick="runTest('wakeup')">Initialize Pump</button>
        <button onclick="runTest('start')">Start Pump</button>
        <button onclick="runTest('stop')">Stop Pump</button>
        <button class="danger" onclick="runTest('emergency_stop')">Emergency Stop</button>
    </div>

    <div class="test-group">
        <h3>Dispense</h3>
        <button onclick="dispense(1)">1 ml</button>
        <button onclick="dispense(5)">5 ml</button>
        <button onclick="dispense(10)">10 ml</button>
        <button onclick="dispense(50)">50 ml</button>
        <button onclick="customDispense()">Custom...</button>
    </div>

    <div class="test-group">
        <h3>Status & Info</h3>
        <button onclick="runTest('status')">Get Status</button>
        <button onclick="runTest('version')">Get Version</button>
        <button onclick="runTest('settings')">Get Settings</button>
    </div>

    <div id="result" style="margin-top: 30px;"></div>

    <script>
    function runTest(action) {
        document.getElementById('result').innerHTML =
            `<p class="info">Running ${action}...</p>`;

        fetch(`run_cole_command.php?action=${action}`)
            .then(r => r.text())
            .then(html => {
                document.getElementById('result').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('result').innerHTML =
                    `<p class="error">Error: ${err}</p>`;
            });
    }

    function dispense(amount) {
        document.getElementById('result').innerHTML =
            `<p class="info">Dispensing ${amount} ml...</p>`;

        fetch(`run_cole_command.php?action=dispense&amount=${amount}`)
            .then(r => r.text())
            .then(html => {
                document.getElementById('result').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('result').innerHTML =
                    `<p class="error">Error: ${err}</p>`;
            });
    }

    function customDispense() {
        const amount = prompt('Enter volume in ml:', '10');
        if (amount && !isNaN(amount) && amount > 0) {
            dispense(amount);
        }
    }

    // Auto-test on page load
    window.onload = function() {
        document.getElementById('result').innerHTML =
            '<p class="info">Ready to test Cole-Parmer pump. Click a button to begin.</p>';
    };
    </script>
</body>
</html>