<?php
// pump/test_python_pump.php

require_once 'pump_python_wrapper.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Python Pump Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        button { padding: 10px 15px; margin: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h1>MasterPlex Pump Test (Python Version)</h1>

    <?php
    try {
        $pump = new PythonPumpWrapper();
        echo "<p class='info'>Python detected: " . $pump->pythonPath . "</p>";
        echo "<p class='info'>Using COM port: COM3</p>";
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <div>
        <button onclick="testConnection()">Test Connection</button>
        <button onclick="wakeupPump()">Wake Up Pump</button>
        <button onclick="dispensePump()">Dispense 5ml</button>
        <button onclick="debugPump()">Debug Info</button>
    </div>

    <div id="result" style="margin-top: 20px;"></div>

    <script>
    function testConnection() {
        showLoading('Testing connection...');

        fetch('python_pump_api.php?action=test')
            .then(r => r.json())
            .then(data => {
                showResult(data);
            })
            .catch(err => {
                showError('Request failed: ' + err);
            });
    }

    function wakeupPump() {
        showLoading('Waking up pump...');

        fetch('python_pump_api.php?action=wakeup')
            .then(r => r.json())
            .then(data => {
                showResult(data);
            })
            .catch(err => {
                showError('Request failed: ' + err);
            });
    }

    function dispensePump() {
        const amount = prompt('Enter amount in ml:', '5');
        if (!amount || isNaN(amount) || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }

        showLoading(`Dispensing ${amount}ml...`);

        fetch(`python_pump_api.php?action=dispense&amount=${amount}`)
            .then(r => r.json())
            .then(data => {
                showResult(data);
            })
            .catch(err => {
                showError('Request failed: ' + err);
            });
    }

    function debugPump() {
        showLoading('Getting debug info...');

        fetch('python_pump_api.php?action=debug')
            .then(r => r.json())
            .then(data => {
                showResult(data);
            })
            .catch(err => {
                showError('Request failed: ' + err);
            });
    }

    function showLoading(msg) {
        document.getElementById('result').innerHTML =
            `<p class="info">${msg}</p>`;
    }

    function showResult(data) {
        const resultDiv = document.getElementById('result');
        const success = data.success;

        let html = `<p class="${success ? 'success' : 'error'}">`;
        html += (success ? '? ' : '? ') + (data.message || 'Operation completed');
        html += '</p>';

        if (data.raw_output || data.return_code !== undefined) {
            html += '<pre>';
            html += JSON.stringify(data, null, 2);
            html += '</pre>';
        }

        resultDiv.innerHTML = html;
    }

    function showError(msg) {
        document.getElementById('result').innerHTML =
            `<p class="error">${msg}</p>`;
    }
    </script>
</body>
</html>