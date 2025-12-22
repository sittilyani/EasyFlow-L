<?php
session_start();
include '../includes/config.php';

$page_title = 'Multi-Date Dispensing';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$conn->set_charset('utf8mb4');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Fetch pharmacist's name from tblusers
$pharm_officer_name = 'Unknown';
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param('i', $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    $pharm_officer_name = $user['first_name'] . ' ' . $user['last_name'];
}
$userStmt->close();

// Fetch patient data
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : '';
$patient = null;
if ($mat_id) {
    $patientQuery = "SELECT p_id, mat_id, mat_number, clientName, nickName, dob, age, sex, p_address, cso, dosage, drugname, current_status FROM patients WHERE mat_id = ?";
    $patientStmt = $conn->prepare($patientQuery);
    if (!$patientStmt) {
        die("Prepare failed: " . $conn->error);
    }
    $patientStmt->bind_param('s', $mat_id);
    $patientStmt->execute();
    $patientResult = $patientStmt->get_result();
    $patient = $patientResult->fetch_assoc();
    $patientStmt->close();
}

// Process form submission
$successMessages = [];
$errorMessages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dispensing'])) {
    try {
        $conn->begin_transaction();

        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $mat_id = $_POST['mat_id'];

        if (strtotime($startDate) > strtotime($endDate)) {
            throw new Exception("End date must be after start date.");
        }

        if (!$patient) {
            throw new Exception("Patient not found.");
        }

        $dateRange = [];
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);

        while ($currentDate <= $endDateObj) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        foreach ($dateRange as $date) {
            if (isset($_POST['dosages'][$date])) {
                $dosage = (float)$_POST['dosages'][$date];
                $drugname = $patient['drugname'] ?? 'Methadone';

                if ($dosage > 0) {
                    $stockQuery = "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                    $stockStmt = $conn->prepare($stockQuery);
                    $stockStmt->bind_param('s', $drugname);
                    $stockStmt->execute();
                    $stockStmt->bind_result($currentStock);
                    $stockStmt->fetch();
                    $stockStmt->close();

                    if ($currentStock === null || $currentStock < $dosage) {
                        $errorMessages[] = "Insufficient stock of $drugname for $date";
                        continue;
                    }

                    $checkQuery = "SELECT disp_id FROM pharmacy WHERE mat_id = ? AND visitDate = ?";
                    $checkStmt = $conn->prepare($checkQuery);
                    $checkStmt->bind_param('ss', $mat_id, $date);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows === 0) {
                        $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertQuery);
                        $reasons = 'Multi-date dispensing';
                        $stmt->bind_param('sssssissssssss',
                            $date, $mat_id, $patient['mat_number'], $patient['clientName'], $patient['nickName'],
                            $patient['age'], $patient['sex'], $patient['p_address'], $patient['cso'], $drugname,
                            $dosage, $reasons, $patient['current_status'], $pharm_officer_name);

                        if ($stmt->execute()) {
                            $updateStockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                            $updateStockStmt = $conn->prepare($updateStockQuery);
                            $updateStockStmt->bind_param('ds', $dosage, $drugname);
                            $updateStockStmt->execute();
                            $updateStockStmt->close();
                            $successMessages[] = "Dispensed " . number_format($dosage, 2) . " mg of $drugname to {$patient['clientName']} for $date";
                        } else {
                            $errorMessages[] = "Error dispensing to {$patient['clientName']} for $date: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $errorMessages[] = "{$patient['clientName']} already dispensed for $date";
                    }
                    $checkStmt->close();
                }
            }
        }

        $conn->commit();


    } catch (Exception $e) {
        $conn->rollback();
        $errorMessages[] = 'Error: ' . $e->getMessage();
    }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css">
    <style>
        #patientInfo {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        #dosageTableContainer {
            margin-top: 20px;
        }
        .dosage-table {
            width: 100%;
            border-collapse: collapse;
        }
        .dosage-table th, .dosage-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .dosage-table th {
            background-color: #f2f2f2;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        .alert {
            margin: 20px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <h2 style="color: #2C3162;">Multi-Date Dispensing</h2>

    <?php if (!empty($successMessages)): ?>
        <div class="alert alert-success">
            <h4>Successfully Dispensed:</h4>
            <ul>
                <?php foreach ($successMessages as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessages)): ?>
        <div class="alert alert-danger">
            <h4>Errors:</h4>
            <ul>
                <?php foreach ($errorMessages as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($patient): ?>
        <div id="patientInfo">
            <h4>Selected Patient</h4>
            <div class="row">
                <div class="col-md-3"><strong>MAT ID:</strong> <span id="infoMatId"><?php echo htmlspecialchars($patient['mat_id']); ?></span></div>
                <div class="col-md-3"><strong>Name:</strong> <span id="infoName"><?php echo htmlspecialchars($patient['clientName']); ?></span></div>
                <div class="col-md-3"><strong>Status:</strong> <span id="infoStatus"><?php echo htmlspecialchars($patient['current_status']); ?></span></div>
                <div class="col-md-3"><strong>Drug:</strong> <span style="color: red;" id="infoDrugname"><?php echo htmlspecialchars($patient['drugname'] ?? 'Methadone'); ?></span></div>
                <div class="col-md-3"><strong>Dosage:</strong> <span style="color: red; font-weight: bold;" id="infoDosage"><?php echo number_format($patient['dosage'] ?? 0, 2); ?></span></div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">No patient selected.</div>
    <?php endif; ?>

    <form method="POST" action="" id="dispensingForm">
        <input type="hidden" name="mat_id" id="formMatId" value="<?php echo htmlspecialchars($mat_id); ?>">
        <input type="hidden" id="patientDosage" value="<?php echo number_format($patient['dosage'] ?? 0, 2); ?>">
        <input type="hidden" name="pharm_officer_name" value="<?php echo htmlspecialchars($pharm_officer_name); ?>">
        <div class="date-range-form">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required
                               value="<?php echo date('Y-m-d', strtotime('+6 days')); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 30px;">
                        <button type="button" class="btn btn-primary" onclick="generateDosageTable()">Generate Dates</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="dosageTableContainer" style="display: none;">
            <table class="dosage-table" id="dosageTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Dosage (mg)</th>
                    </tr>
                </thead>
                <tbody id="dosageTableBody"></tbody>
            </table>
            <button type="submit" style="margin-top: 20px;" name="submit_dispensing" class="btn btn-success">Submit Dispensing</button>
        </div>
    </form>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        window.generateDosageTable = function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const matId = $('#formMatId').val();
            const patientDosage = parseFloat($('#patientDosage').val()) || 0;

            if (!matId) {
                alert('No patient selected.');
                return;
            }

            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('End date must be after start date.');
                return;
            }

            let currentDate = new Date(startDate);
            const endDateObj = new Date(endDate);
            let tableHtml = '';

            while (currentDate <= endDateObj) {
                const dateStr = currentDate.toISOString().split('T')[0];
                tableHtml += `
                    <tr>
                        <td>${dateStr}</td>
                        <td><input type="int" style='width: 120px'; name="dosages[${dateStr}]" class="form-control quantity-input" value="${patientDosage.toFixed(2)}" min="0" step="0.01"></td>
                    </tr>`;
                currentDate.setDate(currentDate.getDate() + 1);
            }

            $('#dosageTableBody').html(tableHtml);
            $('#dosageTableContainer').show();
        };
    });
    </script>
</body>
</html>