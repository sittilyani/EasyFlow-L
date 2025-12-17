<?php
// pump/test_simple.php

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Pump Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        button { padding: 10px 15px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Simple Pump Test</h1>

    <div>
        <button onclick="directTest('test')">Test Connection</button>
        <button onclick="directTest('wakeup')">Wake Up Pump</button>
        <button onclick="dispense()">Dispense</button>
    </div>

    <div id="result" style="margin-top: 20px;"></div>

    <script>
    function directTest(action) {
        showLoading(action === 'test' ? 'Testing connection...' : 'Waking up pump...');

        fetch('direct_python.php?action=' + action)
            .then(r => r.text())
            .then(text => {
                document.getElementById('result').innerHTML = '<pre>' + escapeHtml(text) + '</pre>';
            })
            .catch(err => {
                showError('Request failed: ' + err);
            });
    }

    function dispense() {
        const amount = prompt('Enter amount in ml:', '5');
        if (!amount || isNaN(amount) || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }

        showLoading(`Dispensing ${amount}ml...`);

        fetch(`direct_python.php?action=dispense&amount=${amount}`)
            .then(r => r.text())
            .then(text => {
                document.getElementById('result').innerHTML = '<pre>' + escapeHtml(text) + '</pre>';
            })
            .catch(err => {
                showError('Request failed: ' + err);
            });
    }

    function showLoading(msg) {
        document.getElementById('result').innerHTML =
            `<p class="info">${msg}</p>`;
    }

    function showError(msg) {
        document.getElementById('result').innerHTML =
            `<p class="error">${msg}</p>`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>