<?php
session_start();
include "../includes/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name
$hcw_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $hcw_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Get the patient ID from the query parameter
$userId = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;

// Update age for all patients
$age_update_query = "UPDATE patients SET age = TIMESTAMPDIFF(YEAR, dob, CURDATE()) WHERE dob IS NOT NULL AND dob <= CURDATE()";
$conn->query($age_update_query);

// Fetch the current settings for the patient
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

// Handle form submission
$successMessages = [];
$errorMessages = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    try {
        $conn->begin_transaction();
        $current_status = $_POST['current_status'];

        if ($current_status !== "Active") {
            throw new Exception("Cannot update dosage for inactive client.");
        }

        // Update patient information
        $mat_id = $_POST['mat_id'];
        $mat_number = $_POST['mat_number'];
        $clientName = $_POST['clientName'];
        $sname = $_POST['sname'];
        $dob = $_POST['dob'];
        $sex = $_POST['sex'];
        $p_address = $_POST['p_address'];
        $drugname = $_POST['drugname'];
        $dosage = $_POST['dosage'];
        $reasons = $_POST['reasons'];

        $query = "UPDATE patients SET mat_id = ?, mat_number = ?, clientName = ?, sname = ?, dob = ?, sex = ?, p_address = ?, drugname = ?, dosage = ?, reasons = ?, current_status = ? WHERE p_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssssssssssi', $mat_id, $mat_number, $clientName, $sname, $dob, $sex, $p_address, $drugname, $dosage, $reasons, $current_status, $userId);
        if ($stmt->execute()) {
            $conn->commit();

            // Redirect to dispensing.php with success message
            $successMessage = urlencode("Patient dosage updated successfully, please search to confirm changes!");
            header("Location: ../pharmacy/dispensing.php?message=" . $successMessage);
            exit();
        } else {
            $errorMessages[] = "Error updating patient information";
        }
        $stmt->close();

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
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>.
        .main-content{
            height: 40%;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 0 1rem;
            margin: 2rem auto;
            width: 90%;

        }

    </style>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        function validateStatus() {
            const status = document.getElementById('current_status').value;
            if (status !== 'Active') {
                alert('Cannot update dosage for inactive client.');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
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
        <!-- Prescription Form -->

        <div class="main-content">

            <h2>Opioid prescription dose adjustments</h2>

            <form method="post" onsubmit="return validateStatus();">

                <div class="form-group">
                    <label for="p_id">Patient Entry ID</label>
                    <input type="text" name="userId" value="<?php echo htmlspecialchars($userId); ?>" class="readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label for="mat_id">MAT ID</label>
                    <input type="text" name="mat_id" value="<?php echo htmlspecialchars($currentSettings['mat_id'] ?? ''); ?>" class="readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label for="mat_number">MAT Number</label>
                    <input type="text" name="mat_number" value="<?php echo htmlspecialchars($currentSettings['mat_number'] ?? ''); ?>" class="readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label for="clientName">Client Name</label>
                    <input type="text" name="clientName" value="<?php echo htmlspecialchars($currentSettings['clientName'] ?? ''); ?>" class="readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label for="sname">Sur Name</label>
                    <input type="text" name="sname" value="<?php echo htmlspecialchars($currentSettings['sname'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="text" name="dob" value="<?php echo htmlspecialchars($currentSettings['dob'] ?? ''); ?>" class="readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label for="sex">Sex</label>
                    <input type="text" name="sex" value="<?php echo htmlspecialchars($currentSettings['sex'] ?? ''); ?>" class="readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label for="p_address">Current Residence</label>
                    <input type="text" name="p_address" value="<?php echo htmlspecialchars($currentSettings['p_address'] ?? ''); ?>">
                </div>
                <div class="form-group">
                <label for="drugname">Drug <span style='color: red; font-weight: bold;'>&#10033;</span></label>
                <select id="drugname" name="drugname" required>
                    <?php
                    if (isset($conn)) {
                        $sql = "SELECT drugName FROM drug WHERE drugname IN ('methadone', 'Buprenorphine 2mg', 'Buprenorphine 4mg', 'Buprenorphine 8mg')";
                        $result = $conn->query($sql);

                        // Get the current drug name from patient settings
                        $currentDrug = $currentSettings['drugname'] ?? '';

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $drugName = htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8');
                                // Check if this drug matches the current patient's drug
                                $selected = ($drugName === $currentDrug) ? 'selected' : '';
                                echo "<option value='" . $drugName . "' " . $selected . ">" . $drugName . "</option>";
                            }
                        } else {
                            echo "<option value=''>No drugs available</option>";
                        }
                    }
                    ?>
                </select>
            </div>
                <div class="form-group">
                    <label for="dosage">New Dosage</label>
                    <input type="number" name="dosage" step="0.01" value="<?php echo htmlspecialchars($currentSettings['dosage'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="current_status">Current Patient Status</label>
                    <select name="current_status" id="current_status" required>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $status == ($currentSettings['current_status'] ?? '') ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="reasons">Reasons for dose adjustment</label>
                    <textarea name="reasons" cols="30" rows="6" required><?php echo htmlspecialchars($currentSettings['reasons'] ?? ''); ?></textarea>
                </div>

                <input type="submit" class='custom-submit-btn' name="update" value="Update Prescription" class="submit-btn">
        </form>
    </div>
    <?php else: ?>
        <div style="margin: 20px 60px;">No patient found for the provided ID.</div>
    <?php endif; ?>

</body>
</html>