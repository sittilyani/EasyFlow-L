<?php
session_start();
include "../includes/config.php";


// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name
$hcw_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
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
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Fetch status options from "status" table
$statusOptions = [];
$statusQuery = "SELECT status_name FROM status";
$statusResult = $conn->query($statusQuery);
while ($statusRow = $statusResult->fetch_assoc()) {
    $statusOptions[] = $statusRow['status_name'];
}

// Fetch route options from "administrationroutes" table
$routesOptions = [];
$routesQuery = "SELECT routetype FROM administrationroutes";
$routesResult = $conn->query($routesQuery);
while ($routesRow = $routesResult->fetch_assoc()) {
    $routesOptions[] = $routesRow['routetype'];
}

// Fetch drugs options from "drug" table
$drugsOptions = [];
$drugsQuery = "SELECT drugname FROM drug";
$drugsResult = $conn->query($drugsQuery);
while ($drugsRow = $drugsResult->fetch_assoc()) {
    $drugsOptions[] = $drugsRow['drugname'];
}

// Fetch dosage options from "dosing" table
$dosagesOptions = [];
$dosagesQuery = "SELECT dosage FROM dosing";
$dosagesResult = $conn->query($dosagesQuery);
while ($dosagesRow = $dosagesResult->fetch_assoc()) {
    $dosagesOptions[] = $dosagesRow['dosage'];
}

// Fetch duration options from "durationunits" table
$durationsOptions = [];
$durationsQuery = "SELECT duration FROM durationunits";
$durationsResult = $conn->query($durationsQuery);
while ($durationsRow = $durationsResult->fetch_assoc()) {
    $durationsOptions[] = $durationsRow['duration'];
}

// Fetch available drugs from stock_movements (for stock checking)
$drugs = [];
$drugQuery = "SELECT MAX(drugID) AS drugID, drugname FROM stock_movements WHERE total_qty > 0 GROUP BY drugname ORDER BY drugname";
$drugResult = $conn->query($drugQuery);
while ($row = $drugResult->fetch_assoc()) {
    $drugs[] = $row;
}

// Fetch existing prescriptions for the patient
$prescriptions = [];
if ($currentSettings) {
    $prescriptionQuery = "SELECT * FROM other_prescriptions WHERE mat_id = ? ORDER BY date_created DESC";
    $prescriptionStmt = $conn->prepare($prescriptionQuery);
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
        $conn->begin_transaction();
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
        $stmt = $conn->prepare($query);
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
                $drugName = $drugData['drugname'] ?? '';
                $routetype = $drugData['routetype'] ?? '';
                $dosage = !empty($drugData['dosage']) ? (float)$drugData['dosage'] : 0;
                $duration = !empty($drugData['duration']) ? (int)$drugData['duration'] : 0;
                $totalDose = $dosage * $duration;

                if ($dosage > 0 && $duration > 0 && !empty($drugName) && !empty($routetype)) {
                    // Check stock
                    $stockQuery = "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                    $stockStmt = $conn->prepare($stockQuery);
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
                    $insertPrescriptionQuery = "INSERT INTO other_prescriptions (mat_id, clientName, sex, age, drugName, routetype, dosage, duration, totalDose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $prescriptionStmt = $conn->prepare($insertPrescriptionQuery);
                    $prescriptionStmt->bind_param('sssssssdi', $mat_id, $clientName, $sex, $currentSettings['age'], $drugName, $routetype, $dosage, $duration, $totalDose);
                    if ($prescriptionStmt->execute()) {
                        // Update stock
                        $updateStockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                        $updateStockStmt = $conn->prepare($updateStockQuery);
                        $updateStockStmt->bind_param('ds', $totalDose, $drugName);
                        $updateStockStmt->execute();
                        $updateStockStmt->close();
                        $successMessages[] = "Prescribed $totalDose mg of $drugName (Dosage: $dosage mg, Duration: $duration days, Route: $routetype) for $clientName";
                    } else {
                        $errorMessages[] = "Error prescribing $drugName for $clientName";
                    }
                    $prescriptionStmt->close();
                } else {
                    $errorMessages[] = "Invalid input for drug $drugName: ensure all fields are filled.";
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
<html>
<head>
    <title>Prescription Update</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            margin: 10px 60px;
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
            width: 90%;
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
        .dosage-input, .duration-input, .route-input, .drug-input {
            width: 150px;
            text-align: left;
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
                        <select name="other_drugs[${drugIndex}][drugname]" class="form-control drug-input" required>
                            <option value="">Select Drug</option>
                            <?php foreach ($drugsOptions as $drug): ?>
                                <option value="<?php echo htmlspecialchars($drug); ?>">
                                    <?php echo htmlspecialchars($drug); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="other_drugs_${drugIndex}_routetype">Route:</label>
                        <select name="other_drugs[${drugIndex}][routetype]" class="form-control route-input" required>
                            <option value="">Select Route</option>
                            <?php foreach ($routesOptions as $route): ?>
                                <option value="<?php echo htmlspecialchars($route); ?>">
                                    <?php echo htmlspecialchars($route); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="other_drugs_${drugIndex}_dosage">Daily Dosage:</label>
                        <select name="other_drugs[${drugIndex}][dosage]" class="form-control dosage-input" required>
                            <option value="">Select Dosage</option>
                            <?php foreach ($dosagesOptions as $dosage): ?>
                                <option value="<?php echo htmlspecialchars($dosage); ?>">
                                    <?php echo htmlspecialchars($dosage); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="other_drugs_${drugIndex}_duration">Duration:</label>
                        <input type="integer" name="other_drugs[${drugIndex}][duration]" class="form-control duration-input" required>

                    </div>
                    <div class="col-md-2">
                        <label for="other_drugs_${drugIndex}_duration">Duration:</label>
                        <select name="other_drugs[${drugIndex}][duration]" class="form-control duration-input" required>
                            <option value="">Select Duration</option>
                            <?php foreach ($durationsOptions as $duration): ?>
                                <option value="<?php echo htmlspecialchars($duration); ?>">
                                    <?php echo htmlspecialchars($duration); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2" style="margin-top: 30px;">
                        <button type="button" class="remove-drug-btn" onclick="removeDrug(this)">Remove</button>
                    </div>
                </div>`;
            container.appendChild(newDrugDiv);
            drugIndex++;
        }
        function removeDrug(button) {
            const drugDivs = document.querySelectorAll('.other-drug');
            if (drugDivs.length > 1) {
                button.closest('.other-drug').remove();
            }
        }
    </script>
</head>
<body>
    <div class="main-content">
        <h2>Update Patient Dosage Information</h2>
        <!-- Success/Error Messages -->
        <?php if (!empty($successMessages)): ?>
            <div class="alert alert-success">
                <h4>Success:</h4>
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
        <!-- Main Content -->
        <?php if ($currentSettings): ?>
            <!-- Existing Prescriptions -->
            <h2 style="margin-left: 60px;">Current Prescriptions</h2>
            <?php if (!empty($prescriptions)): ?>
                <table class="prescription-table">
                    <tr>
                        <th>Drug Name</th>
                        <th>Dosing</th>
                        <th>Route</th>
                        <th>Duration</th>
                        <th>Duration Unit</th>
                        <th>Total Dose</th>
                        <th>Date Created</th>
                    </tr>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prescription['drugName']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['routetype'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['durationUnit']); ?></td
                            <td><?php echo htmlspecialchars($prescription['totalDose']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['date_created']); ?></td>
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
                        <input type="text" name="userId" value="<?php echo htmlspecialchars($userId); ?>" class="readonly-input" readonly><br>
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
                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $status == ($currentSettings['current_status'] ?? '') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select><br>
                    </div>
                </div>
                <!-- Additional Drugs -->
                <h2 style="margin-left: 60px;">Prescribe Additional Drugs</h2>
                <div id="otherDrugsContainer" style="margin: 20px 60px;">
                    <div class="other-drug" style="margin-bottom: 5px;">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="other_drugs_0_drugname">Drug Name:</label>
                                <select name="other_drugs[0][drugname]" class="form-control drug-input" required>
                                    <option value="">Select Drug</option>
                                    <?php foreach ($drugsOptions as $drug): ?>
                                        <option value="<?php echo htmlspecialchars($drug); ?>">
                                            <?php echo htmlspecialchars($drug); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="other_drugs_0_routetype">Route:</label>
                                <select name="other_drugs[0][routetype]" class="form-control route-input" required>
                                    <option value="">Select Route</option>
                                    <?php foreach ($routesOptions as $route): ?>
                                        <option value="<?php echo htmlspecialchars($route); ?>">
                                            <?php echo htmlspecialchars($route); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="other_drugs_0_dosage">Dosing:</label>
                                <select name="other_drugs[0][dosage]" class="form-control dosage-input" required>
                                    <option value="">Select Dosage</option>
                                    <?php foreach ($dosagesOptions as $dosage): ?>
                                        <option value="<?php echo htmlspecialchars($dosage); ?>">
                                            <?php echo htmlspecialchars($dosage); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="other_drugs_0_durationunit">Duration</label>
                                <input type="integer" name="other_drugs[0] [durationunit]" class="form-control durationunit-input" required>
                            </div>
                            <div class="col-md-2">
                                <label for="other_drugs_0_duration">Duration Units:</label>
                                <select name="other_drugs[0][duration]" class="form-control duration-input" required>
                                    <option value="">Select Duration</option>
                                    <?php foreach ($durationsOptions as $duration): ?>
                                        <option value="<?php echo htmlspecialchars($duration); ?>">
                                            <?php echo htmlspecialchars($duration); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="col-md-2">
                                <button type="button" class="remove-drug-btn" onclick="removeDrug(this)">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="add-drug-btn" onclick="addDrug()">Add Another Drug</button>
                <input type="submit" name="update" value="Prescribe" class="submit-btn">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>