<?php
session_start();
include('../includes/config.php');

// Initialize variables
$mat_id = null;
$patientDetails = null;
$viralLoadHistory = [];

if (isset($_GET['mat_id'])) {
    $mat_id = $_GET['mat_id'];

    // 1. Fetch main patient details from the 'patients' table
    $sqlPatient = "SELECT mat_id, reg_date FROM patients WHERE mat_id = ?";
    $stmtPatient = $conn->prepare($sqlPatient);
    $stmtPatient->bind_param('s', $mat_id);
    $stmtPatient->execute();
    $resultPatient = $stmtPatient->get_result();

    if ($resultPatient->num_rows > 0) {
        $patientDetails = $resultPatient->fetch_assoc();

        // 2. Fetch all viral load records for the given mat_id
        $sqlHistory = "SELECT * FROM viral_load WHERE mat_id = ? ORDER BY comp_date DESC";
        $stmtHistory = $conn->prepare($sqlHistory);
        $stmtHistory->bind_param('s', $mat_id);
        $stmtHistory->execute();
        $resultHistory = $stmtHistory->get_result();

        while ($row = $resultHistory->fetch_assoc()) {
            $viralLoadHistory[] = $row;
        }

    } else {
        die("Patient not found.");
    }

    $stmtPatient->close();
    $stmtHistory->close();

} else {
    die("Invalid request. Please provide a patient MAT ID.");
}

// Function to determine viral suppression status
function getSuppressionStatus($results) {
    if (is_numeric($results)) {
        if ($results <= 50) {
            return 'Undetectable';
        } elseif ($results >= 51 && $results <= 200) {
            return 'LDL';
        } elseif ($results > 200 && $results <= 1000) {
            return 'LDL';
        } elseif ($results > 1000) {
            return 'Unsuppressed';
        }
    }
    return 'N/A';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Viral Load History</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/view.css" type="text/css">
    <style>
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .history-table th, .history-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .history-table th {
            background-color: #f2f2f2;
        }
        .excel-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            float: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($patientDetails): ?>
        <h3><?php echo htmlspecialchars($patientDetails['mat_id']); ?> viral suppression history: Enrolment Date: <?php echo htmlspecialchars($patientDetails['reg_date']); ?></h3>

        <button class="excel-btn" onclick="exportToExcel()">Export to Excel</button>
        <div style="clear: both;"></div>

        <?php if (!empty($viralLoadHistory)): ?>
        <table class="history-table" id="history-table">
            <thead>
                <tr>
                    <th>VL ID</th>
                    <th>Last VL Date</th>
                    <th>Results</th>
                    <th>Suppression Status</th>
                    <th>Regimen</th>
                    <th>Regimen Type</th>
                    <th>Clinician Name</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viralLoadHistory as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['vl_id']); ?></td>
                    <!--<td><?php echo htmlspecialchars($record['last_vlDate']); ?></td>-->
                    <td><?php echo empty($record['last_vlDate']) ? "NA" : htmlspecialchars($record['last_vlDate']); ?></td>
                    <!--<td><?php echo htmlspecialchars($record['results']); ?></td>-->
                    <td><?php echo empty($record['results']) ? "NA" : htmlspecialchars($record['results']); ?></td>
                    <td><?php echo htmlspecialchars(getSuppressionStatus($record['results'])); ?></td>
                    <td><?php echo htmlspecialchars($record['art_regimen']); ?></td>
                    <td><?php echo htmlspecialchars($record['regimen_type']); ?></td>
                    <td><?php echo htmlspecialchars($record['clinician_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['clinical_notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No viral load history found for this patient.</p>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        function exportToExcel() {
            var table = document.getElementById("history-table");
            var html = table.outerHTML;
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement("a");
            link.href = uri;
            link.style = "visibility:hidden";
            link.download = "viral_load_history_<?php echo htmlspecialchars($mat_id); ?>.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>