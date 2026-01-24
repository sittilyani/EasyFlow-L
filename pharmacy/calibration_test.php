<?php
session_start();
include 'includes/config.php';

// Test calibration by dispensing a known amount
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pump_id = $_POST['pump_id'];
        $test_dosage = 10; // 10mg test dose

        // Get pump calibration and port
        $query = "SELECT pd.port, pc.calibration_factor, pc.concentration_mg_per_ml
                            FROM pump_devices pd
                            LEFT JOIN pump_calibration pc ON pd.id = pc.pump_id AND pc.is_active = TRUE
                            WHERE pd.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $pump_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pump = $result->fetch_assoc();

        $factor = $pump['calibration_factor'] ?? 500;
        $concentration = $pump['concentration_mg_per_ml'] ?? 5.00;
        $port = $pump['port'];

        // Calculate mL to dispense
        $ml = ($test_dosage / $concentration) * $factor;

        // Send pump command
        $pump_cmd = "/1m50h10j4V1600L400z{$ml}P{$ml}R";
        $command = "pumpAPI.exe $port 9600 raw $pump_cmd";

        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        echo json_encode([
                'success' => $return_var === 0,
                'expected_ml' => $test_dosage / $concentration,
                'pump_units' => $ml,
                'command' => $command
        ]);
        exit;
}
?>

<!DOCTYPE html>
<html>
<body>
        <h3>Pump Calibration Test</h3>
        <form id="testForm">
                <select name="pump_id">
                        <?php
                        $pumps = $conn->query("SELECT * FROM pump_devices");
                        while ($pump = $pumps->fetch_assoc()): ?>
                                <option value="<?php echo $pump['id']; ?>">
                                        <?php echo $pump['label']; ?> (<?php echo $pump['port']; ?>)
                                </option>
                        <?php endwhile; ?>
                </select>
                <button type="submit">Test Dispense 10mg</button>
        </form>
        <div id="result"></div>

        <script>
                document.getElementById('testForm').onsubmit = function(e) {
                        e.preventDefault();
                        var formData = new FormData(this);

                        fetch('calibration_test.php', {
                                method: 'POST',
                                body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                                document.getElementById('result').innerHTML =
                                        'Dispensing complete. Expected: ' + data.expected_ml.toFixed(2) +
                                        'mL, Pump Units: ' + data.pump_units;
                        });
                };
        </script>
</body>
</html>