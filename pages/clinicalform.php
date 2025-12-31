<?php
session_start();
include "../includes/config.php";
include "../includes/footer.php";
include "../includes/header.php";

$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name
$hcw_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $mysqli->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $hcw_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Get the user_id from the query parameter
$userId = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;

// Fetch the current settings for the user
$currentSettings = null;
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Fetch status options from "status" table
$statusOptions = [];
$statusQuery = "SELECT status_name FROM status";
$statusResult = $mysqli->query($statusQuery);
while ($statusRow = $statusResult->fetch_assoc()) {
    $statusOptions[] = $statusRow['status_name'];
}

// Fetch available drugs from stock_movements
$drugs = [];
$drugQuery = "SELECT MAX(drugID) AS drugID, drugname FROM stock_movements WHERE total_qty > 0 GROUP BY drugname ORDER BY drugname";
$drugResult = $mysqli->query($drugQuery);
while ($row = $drugResult->fetch_assoc()) {
    $drugs[] = $row;
}

// Fetch existing prescriptions for the patient
$prescriptions = [];
if ($currentSettings) {
    $prescriptionQuery = "SELECT * FROM other_prescriptions WHERE mat_id = ? ORDER BY date_created DESC";
    $prescriptionStmt = $mysqli->prepare($prescriptionQuery);
    $prescriptionStmt->bind_param('s', $currentSettings['mat_id']);
    $prescriptionStmt->execute();
    $prescriptionResult = $prescriptionStmt->get_result();
    while ($row = $prescriptionResult->fetch_assoc()) {
        $prescriptions[] = $row;
    }
    $prescriptionStmt->close();
}

// Handle form submission
$successMessages = [];
$errorMessages = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    try {
        $mysqli->begin_transaction();
        $current_status = $_POST['current_status'];

        if ($current_status !== "Active") {
            throw new Exception("Cannot prescribe for inactive client.");
        }

        // Update patient information
        $mat_id = $_POST['mat_id'];
        $mat_number = $_POST['mat_number'];
        $clientName = $_POST['clientName'];
        $sname = $_POST['sname'];
        $dob = $_POST['dob'];
        $sex = $_POST['sex'];
        $p_address = $_POST['p_address'];
        $dosage = $_POST['dosage'];
        $reasons = $_POST['reasons'];

        $query = "UPDATE patients SET mat_id = ?, mat_number = ?, clientName = ?, sname = ?, dob = ?, sex = ?, p_address = ?, dosage = ?, reasons = ?, current_status = ? WHERE p_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssssssssi', $mat_id, $mat_number, $clientName, $sname, $dob, $sex, $p_address, $dosage, $reasons, $current_status, $userId);
        if ($stmt->execute()) {
            $successMessages[] = "Patient dosage updated successfully";
        } else {
            $errorMessages[] = "Error updating patient information";
        }
        $stmt->close();

        // Process additional drugs
        if (isset($_POST['other_drugs'])) {
            foreach ($_POST['other_drugs'] as $index => $drugData) {
                $drugID = !empty($drugData['drugID']) ? (int)$drugData['drugID'] : null;
                $drugName = $drugData['drugname'];
                $indications = $drugData['indications'];
                $dosage = (int)$drugData['dosage'];
                $duration = (int)$drugData['duration'];
                $totalDose = $dosage * $duration;

                if ($dosage > 0 && $duration > 0 && !empty($drugName)) {
                    // Check stock
                    $stockQuery = "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                    $stockStmt = $mysqli->prepare($stockQuery);
                    $stockStmt->bind_param('s', $drugName);
                    $stockStmt->execute();
                    $stockStmt->bind_result($currentStock);
                    $stockStmt->fetch();
                    $stockStmt->close();

                    if ($currentStock === null || $currentStock < $totalDose) {
                        $errorMessages[] = "Insufficient stock of $drugName";
                        continue;
                    }

                    // Insert into other_prescriptions
                    $insertPrescriptionQuery = "INSERT INTO other_prescriptions (mat_id, clientName, sex, age, drugID, drugName, indications, dosage, duration, totalDose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $prescriptionStmt = $mysqli->prepare($insertPrescriptionQuery);
                    $prescriptionStmt->bind_param('ssssisssii', $mat_id, $clientName, $sex, $currentSettings['age'], $drugID, $drugName, $indications, $dosage, $duration, $totalDose);
                    if ($prescriptionStmt->execute()) {
                        // Update stock
                        $updateStockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                        $updateStockStmt = $mysqli->prepare($updateStockQuery);
                        $updateStockStmt->bind_param('is', $totalDose, $drugName);
                        $updateStockStmt->execute();
                        $updateStockStmt->close();
                        $successMessages[] = "Prescribed $totalDose mg of $drugName (Dosage: $dosage mg, Duration: $duration days) for $clientName";
                    } else {
                        $errorMessages[] = "Error prescribing $drugName for $clientName";
                    }
                    $prescriptionStmt->close();
                }
            }
        }

        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $errorMessages[] = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prescription Update</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 10px;
            padding-left: 10px;
            padding-right: 10px;
            margin: 20px 60px;
            width: 70%;
        }
        .grid-item {
            grid-column: span 1;
            border: solid thin;
            padding: 20px;
            width: 300px;
            background-color: #FFFFCC;
        }
        input, select, textarea {
            width: 100%;
            margin-bottom: 10px;
            margin-top: 10px;
            padding: 5px;
        }
        input[type="submit"] {
            background-color: #1A5276;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            height: 50px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        label {
            font-size: 16px;
            font-weight: bold;
        }
        .readonly-input {
            background-color: #FFE6B2;
        }
        .submit-btn {
            height: 50px;
            font-size: 18px;
        }
        h2 {
            margin-left: 60px;
            margin-top: 10px;
            color: #2C3162;
        }
        .alert {
            padding: 15px;
            margin: 20px 60px;
            border: 1px solid transparent;
            border-radius: 4px;
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
        .prescription-table {
            width: 70%;
            margin: 20px 60px;
            border-collapse: collapse;
        }
        .prescription-table th, .prescription-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .prescription-table th {
            background-color: #2C3162;
            color: white;
        }
        .other-drug {
            margin-bottom: 15px;
        }
        .add-drug-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 60px;
        }
        .add-drug-btn:hover {
            background-color: #218838;
        }
        .remove-drug-btn {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .dosage-input, .duration-input {
            width: 80px;
            text-align: center;
        }
        .indications-input {
            width: 150px;
        }
    </style>
    <script>
        function validateStatus() {
            const status = document.getElementById('current_status').value;
            if (status !== 'Active') {
                alert('Cannot prescribe for inactive client.');
                return false;
            }
            return true;
        }
        let drugIndex = 1;
        function addDrug() {
            const container = document.getElementById('otherDrugsContainer');
            const newDrugDiv = document.createElement('div');
            newDrugDiv.className = 'other-drug';
            newDrugDiv.style.marginBottom = '15px';
            newDrugDiv.innerHTML = `
                <div class="row" style="margin: 0 60px;">
                    <div class="col-md-3">
                        <label for="other_drugs_${drugIndex}_drugname">Drug Name:</label>
                        <select name="other_drugs[${drugIndex}][drugname]" class="form-control" required>
                            <option value="">Select Drug</option>
                            <?php foreach ($drugs as $drug): ?>
                            <option value="<?php echo htmlspecialchars($drug['drugname']); ?>" data-drugid="<?php echo $drug['drugID'] ?? ''; ?>">
                                <?php echo htmlspecialchars($drug['drugname']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="other_drugs[${drugIndex}][drugID]" class="drug-id-input">
                    </div>
                    <div class="col-md-3">
                        <label for="other_drugs_${drugIndex}_indications">Indications:</label>
                        <input type="text" name="other_drugs[${drugIndex}][indications]" class="form-control indications-input" placeholder="Enter indications">
                    </div>
                    <div class="col-md-2">
                        <label for="other_drugs_${drugIndex}_dosage">Daily Dosage (mg):</label>
                        <input type="number" name="other_drugs[${drugIndex}][dosage]" class="form-control dosage-input" min="0" step="1" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label for="other_drugs_${drugIndex}_duration">Duration (days):</label>
                        <input type="number" name="other_drugs[${drugIndex}][duration]" class="form-control duration-input" min="0" step="1" placeholder="0">
                    </div>
                    <div class="col-md-2" style="margin-top: 30px;">
                        <button type="button" class="remove-drug-btn" onclick="removeDrug(this)">Remove</button>
                    </div>
                </div>`;
            container.appendChild(newDrugDiv);
            const select = newDrugDiv.querySelector('select');
            const hiddenInput = newDrugDiv.querySelector('.drug-id-input');
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                hiddenInput.value = selectedOption.getAttribute('data-drugid') || '';
            });
            drugIndex++;
        }
        function removeDrug(button) {
            if (document.querySelectorAll('.other-drug').length > 1) {
                button.closest('.other-drug').remove();
            }
        }
    </script>
</head>
<body>
    <h2>Update Patient Dosage Information</h2>
    <!-- Success/Error Messages -->
    <?php if (!empty($successMessages)): ?>
        <div class="alert alert-success">
            <h4>Success:</h4>
            <ul>
                <?php foreach ($successMessages as $msg): ?>
                    <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessages)): ?>
        <div class="alert alert-danger">
            <h4>Errors:</h4>
            <ul>
                <?php foreach ($errorMessages as $msg): ?>
                    <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <!-- Main Content -->
    <?php if ($currentSettings): ?>
        <!-- Existing Prescriptions -->
        <h2 style="margin-left: 60px;">Current Prescriptions</h2>
        <?php if (!empty($prescriptions)): ?>
            <table class="prescription-table">
                <tr>
                    <th>Drug Name</th>
                    <th>Indications</th>
                    <th>Dosage (mg)</th>
                    <th>Duration (days)</th>
                    <th>Total Dose (mg)</th>
                    <th>Date Created</th>
                </tr>
                <?php foreach ($prescriptions as $prescription): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prescription['drugName']); ?></td>
                        <td><?php echo htmlspecialchars($prescription['indications']); ?></td>
                        <td><?php echo $prescription['dosage']; ?></td>
                        <td><?php echo $prescription['duration']; ?></td>
                        <td><?php echo $prescription['totalDose']; ?></td>
                        <td><?php echo $prescription['date_created']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div style="margin: 20px 60px;">No additional prescriptions found.</div>
        <?php endif; ?>
        <!-- Prescription Form -->
        <form method="post" onsubmit="return validateStatus();">
            <div class="grid-container">
                <div class="grid-item">
                    <label for="p_id">Patient Entry ID</label><br>
                    <input type="text" name="userId" value="<?php echo $userId; ?>" class="readonly-input" readonly><br>
                    <label for="mat_id">MAT ID</label><br>
                    <input type="text" name="mat_id" value="<?php echo htmlspecialchars($currentSettings['mat_id']); ?>" class="readonly-input" readonly><br>
                    <label for="mat_number">MAT Number</label><br>
                    <input type="text" name="mat_number" value="<?php echo htmlspecialchars($currentSettings['mat_number']); ?>" class="readonly-input" readonly><br>
                    <label for="clientName">Client Name</label><br>
                    <input type="text" name="clientName" value="<?php echo htmlspecialchars($currentSettings['clientName']); ?>" required><br>
                </div>
                <div class="grid-item">
                    <label for="sname">Sur Name</label><br>
                    <input type="text" name="sname" value="<?php echo htmlspecialchars($currentSettings['sname']); ?>"><br>
                    <label for="dob">Date of Birth</label><br>
                    <input type="text" name="dob" value="<?php echo htmlspecialchars($currentSettings['dob']); ?>" class="readonly-input" readonly><br>
                    <label for="sex">Sex</label><br>
                    <input type="text" name="sex" value="<?php echo htmlspecialchars($currentSettings['sex']); ?>" class="readonly-input" readonly><br>
                    <label for="p_address">Current Residence</label><br>
                    <input type="text" name="p_address" value="<?php echo htmlspecialchars($currentSettings['p_address']); ?>" class="readonly-input"><br>
                </div>
                <div class="grid-item">
                    <label for="drugname">Drug</label><br>
                    <input type="text" name="drugname" value="<?php echo htmlspecialchars($currentSettings['drugname'] ?? 'Methadone'); ?>" class="readonly-input" readonly><br>
                    <label for="dosage">New Dosage</label><br>
                    <input type="text" name="dosage" value="<?php echo htmlspecialchars($currentSettings['dosage']); ?>" required><br>
                    <label for="reasons">Reasons for dose adjustment</label><br>
                    <textarea name="reasons" cols="30" rows="6" required><?php echo htmlspecialchars($currentSettings['reasons'] ?? ''); ?></textarea>
                </div>
                <div class="grid-item">
                    <label for="current_status">Current Patient Status</label><br>
                    <select name="current_status" id="current_status" required>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $status == $currentSettings['current_status'] ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br>
                    <input type="submit" name="update" value="Prescribe" class="submit-btn">
                </div>
            </div>
            <!-- Additional Drugs -->
            <h2 style="margin-left: 60px;">Prescribe Additional Drugs</h2>
            <div id="otherDrugsContainer" style="margin: 20px 60px;">
                <div class="other-drug" style="margin-bottom: 15px;">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="other_drugs_0_drugname">Drug Name:</label>
                            <select name="other_drugs[0][drugname]" class="form-control" required>
                                <option value="">Select Drug</option>
                                <?php foreach ($drugs as $drug): ?>
                                    <option value="<?php echo htmlspecialchars($drug['drugname']); ?>" data-drugid="<?php echo $drug['drugID'] ?? ''; ?>">
                                        <?php echo htmlspecialchars($drug['drugname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="other_drugs[0][drugID]" class="drug-id-input">
                        </div>
                        <div class="col-md-3">
                            <label for="other_drugs_0_indications">Indications:</label>
                            <input type="text" name="other_drugs[0][indications]" class="form-control indications-input" placeholder="Enter indications">
                        </div>
                        <div class="col-md-2">
                            <label for="other_drugs_0_dosage">Daily Dosage (mg):</label>
                            <input type="number" name="other_drugs[0][dosage]" class="form-control dosage-input" min="0" step="1" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label for="other_drugs_0_duration">Duration (days):</label>
                            <input type="number" name="other_drugs[0][duration]" class="form-control duration-input" min="0" step="1" placeholder="0">
                        </div>
                        <div class="col-md-2" style="margin-top: 30px;">
                            <button type="button" class="remove-drug-btn" onclick="removeDrug(this)">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="add-drug-btn" onclick="addDrug()">Add Another Drug</button>
        </form>
    <?php endif; ?>
</body>
</html>