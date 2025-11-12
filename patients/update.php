<?php
session_start();

// Include configuration and header files
require_once('../includes/config.php');

// Check if patient ID is provided
if (!isset($_GET['p_id'])) {
    die("Error: Patient ID not specified.");
}

$patientId = $_GET['p_id'];

// Verify database connection exists - using $conn instead of $mysqli
if (!isset($conn) || $conn->connect_error) {
    die("Database connection error. Please check your config.php file. Error: " .
        (isset($conn) ? $conn->connect_error : 'Connection not established'));
}

// Fetch the current settings for the patient
$query = "SELECT * FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param('i', $patientId);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();

// Check if patient exists
if (!$currentSettings) {
    die("Error: Patient not found in the database.");
}

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    // Collect and sanitize form data
    $mat_id = trim($_POST['mat_id']);
    $mat_number = trim($_POST['mat_number']);
    $clientName = trim($_POST['clientName']);
    $sname = trim($_POST['sname']);
    $nickName = trim($_POST['nickName']);
    $nat_id = trim($_POST['nat_id']);
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $reg_facility = trim($_POST['reg_facility']);
    $mflcode = trim($_POST['mflcode']);
    $county = $_POST['county'];
    $scounty = $_POST['scounty'];
    $residence_scounty = $_POST['residence_scounty'];
    $p_address = trim($_POST['p_address']);
    $reg_date = $_POST['reg_date'];
    $client_phone = trim($_POST['client_phone']);
    $mat_status = $_POST['mat_status'];
    $transfer_id = trim($_POST['transfer_id']);
    $referral_type = $_POST['referral_type'];
    $referring_facility = $_POST['referring_facility'];
    $reffering_fac_client_number = trim($_POST['reffering_fac_client_number']);
    $accompanment_type = $_POST['accompanment_type'];
    $peer_edu_name = trim($_POST['peer_edu_name']);
    $peer_edu_phone = trim($_POST['peer_edu_phone']);
    $rx_supporter_name = trim($_POST['rx_supporter_name']);
    $drugname = $_POST['drugname'];
    $dosage = trim($_POST['dosage']);
    $reasons = trim($_POST['reasons']);
    $current_status = $_POST['current_status'];
    $hcw_name = trim($_POST['hcw_name']);
    $comp_date = $_POST['comp_date'];

    // Format dates
    $reg_date = date('Y-m-d', strtotime($reg_date));
    $comp_date = date('Y-m-d H:i:s', strtotime($comp_date));

    // Update query
    $query = "UPDATE patients SET mat_id = ?, mat_number = ?, clientName = ?,
            sname = ?, nickName = ?, nat_id = ?, dob = ?, sex = ?, reg_facility = ?, mflcode = ?,
            county = ?, scounty = ?, reg_date = ?, residence_scounty = ?, p_address = ?,
            client_phone = ?, mat_status = ?, transfer_id = ?, referral_type = ?, referring_facility = ?,
            reffering_fac_client_number = ?, accompanment_type = ?, peer_edu_name = ?, peer_edu_phone = ?,
            rx_supporter_name = ?, drugname = ?, dosage = ?, reasons = ?, current_status = ?, hcw_name = ?,
            comp_date = ? WHERE p_id = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        $errorMessage = "Error preparing update statement: " . $conn->error;
    } else {
        $stmt->bind_param('sssssssssssssssssssssssssssssssi',
            $mat_id, $mat_number, $clientName, $sname, $nickName, $nat_id, $dob, $sex,
            $reg_facility, $mflcode, $county, $scounty, $reg_date, $residence_scounty,
            $p_address, $client_phone, $mat_status, $transfer_id, $referral_type,
            $referring_facility, $reffering_fac_client_number, $accompanment_type,
            $peer_edu_name, $peer_edu_phone, $rx_supporter_name, $drugname, $dosage,
            $reasons, $current_status, $hcw_name, $comp_date, $patientId);

        if ($stmt->execute()) {
            $successMessage = "Patient Profile updated successfully";
            // Refresh to show updated data
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'update.php?p_id=" . $patientId . "&success=1';
                }, 1000);
            </script>";
        } else {
            $errorMessage = "Error updating patient: " . $stmt->error;
        }
    }
}

// Fetch the logged-in user's clinician name from tblusers
$hcwName = '';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // This is the user ID from session
    $query = "SELECT first_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param('i', $userId); // Using user ID from session
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $hcwName = $user ? $user['first_name'] : '';
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $successMessage = "Patient details updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Update</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
        form {
            display: grid;
            grid-template-columns: repeat(5, 1fr); /* Three equal columns */}

        .main-content {
            padding: 20px;
            max-width: 95%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="form-group"><h2>Update Patient Information</h2></div>
        
        <?php if (!empty($successMessage)): ?>
            <div class="message success" style="background: #CCFFCC; height: 50px;"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="message error"  style="background: #FFC7D2; height: 50px;"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form method="post">
                <div class="form_group">
                    <label for="p_id">Patient ID</label>
                    <input type="text" name="p_id" value="<?php echo $patientId; ?>" class="readonly-input" readonly>
                </div>
                <div class="form_group">
                    <label for="mat_id">MAT ID</label>
                    <input type="text" name="mat_id" value="<?php echo htmlspecialchars($currentSettings['mat_id']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="mat_number">MAT Number</label>
                    <input type="text" name="mat_number" value="<?php echo htmlspecialchars($currentSettings['mat_number']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="clientName">Client Name</label>
                    <input type="text" name="clientName" value="<?php echo htmlspecialchars($currentSettings['clientName']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="sname">Surname</label>
                    <input type="text" name="sname" value="<?php echo htmlspecialchars($currentSettings['sname']); ?>">
                </div>
                <div class="form_group">
                    <label for="nickName">Nickname</label>
                    <input type="text" name="nickName" value="<?php echo htmlspecialchars($currentSettings['nickName']); ?>">
                </div>
                <div class="form_group">
                    <label for="nat_id">National ID</label>
                    <input type="text" name="nat_id" value="<?php echo htmlspecialchars($currentSettings['nat_id']); ?>">
                </div>
                <div class="form_group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo $currentSettings['dob']; ?>" required>
                </div>
                <div class="form_group">
                    <label for="sex">Sex</label>
                    <input type="text" name="sex" value="<?php echo htmlspecialchars($currentSettings['sex']); ?>" class="readonly-input" readonly>
                </div>
                <div class="form_group">
                    <label for="p_address">Current Residence</label>
                    <input type="text" name="p_address" value="<?php echo htmlspecialchars($currentSettings['p_address']); ?>">
                </div>
                <div class="form_group">
                    <label for="reg_facility">Enrollment Facility</label>
                    <input type="text" name="reg_facility" value="<?php echo htmlspecialchars($currentSettings['reg_facility']); ?>" class="readonly-input" readonly>
                </div>
                <div class="form_group">
                    <label for="mflcode">MFL Code</label>
                    <input type="text" name="mflcode" value="<?php echo htmlspecialchars($currentSettings['mflcode']); ?>" class="readonly-input" readonly>
                </div>
                <div class="form_group">
                    <label for="county">County</label>
                    <select name="county" required>
                        <?php
                        $sql = "SELECT county_name FROM counties";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['county_name'] == $currentSettings['county']) ? 'selected' : '';
                                echo "<option value='" . $row['county_name'] . "' $selected>" . $row['county_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No counties found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="scounty">Sub County</label>
                    <select name="scounty" required>
                        <?php
                        $sql = "SELECT sub_county_name FROM sub_counties";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['sub_county_name'] == $currentSettings['scounty']) ? 'selected' : '';
                                echo "<option value='" . $row['sub_county_name'] . "' $selected>" . $row['sub_county_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No sub counties found</option>";
                        }
                        ?>
                    </select>

                    <label for="reg_date">Enrollment Date</label>
                    <input type="date" name="reg_date" value="<?php echo $currentSettings['reg_date']; ?>" class="readonly-input" readonly>
                </div>
                <div class="form_group">
                    <label for="residence_scounty">Residence Sub County</label>
                    <input type="text" name="residence_scounty" value="<?php echo htmlspecialchars($currentSettings['residence_scounty']); ?>">
                </div>
                <div class="form-group">
                    <label for="p_address">Physical Address (residence)</label>
                    <input type="text" id="p_address" name="p_address" placeholder="Inmate: if in PRISON">
                </div>
                <div class="form_group">
                    <label for="client_phone">Client Phone</label>
                    <input type="text" name="client_phone" value="<?php echo htmlspecialchars($currentSettings['client_phone']); ?>">
                </div>
                <div class="form_group">
                    <label for="mat_status">Enrollment Status (TI or New)</label>
                    <select name="mat_status" required>
                        <?php
                        $sql = "SELECT enrolment_status_name FROM enrolment_status";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['enrolment_status_name'] == $currentSettings['mat_status']) ? 'selected' : '';
                                echo "<option value='" . $row['enrolment_status_name'] . "' $selected>" . $row['enrolment_status_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No enrollment status found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="transfer_id">Transfer ID</label>
                    <input type="text" name="transfer_id" value="<?php echo htmlspecialchars($currentSettings['transfer_id']); ?>">
                </div>
                <div class="form_group">
                    <label for="referral_type">Referral Type</label>
                    <select name="referral_type">
                        <?php
                        $sql = "SELECT referralType FROM tblreferral";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['referralType'] == $currentSettings['referral_type']) ? 'selected' : '';
                                echo "<option value='" . $row['referralType'] . "' $selected>" . $row['referralType'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No referral types found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="cso">CSO Name</label>
                    <select name="cso">
                        <?php
                        $sql = "SELECT cso_name FROM tblcso";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['cso_name'] == $currentSettings['cso']) ? 'selected' : '';
                                echo "<option value='" . $row['cso_name'] . "' $selected>" . $row['cso_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No CSOs found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="referring_facility">Referring Facility</label>
                    <select name="referring_facility">
                        <?php
                        $sql = "SELECT facilityname FROM facilities";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['facilityname'] == $currentSettings['referring_facility']) ? 'selected' : '';
                                echo "<option value='" . $row['facilityname'] . "' $selected>" . $row['facilityname'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No facilities found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="reffering_fac_client_number">Referring Facility Client Number</label>
                    <input type="text" name="reffering_fac_client_number" value="<?php echo htmlspecialchars($currentSettings['reffering_fac_client_number']); ?>">

                    <label for="accompanment_type">Accompaniment Type</label>
                    <select name="accompanment_type">
                        <?php
                        $sql = "SELECT accompanmentType FROM tblaccompanment";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['accompanmentType'] == $currentSettings['accompanment_type']) ? 'selected' : '';
                                echo "<option value='" . $row['accompanmentType'] . "' $selected>" . $row['accompanmentType'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No accompaniment types found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="peer_edu_name">Peer Educator Name</label>
                    <input type="text" name="peer_edu_name" value="<?php echo htmlspecialchars($currentSettings['peer_edu_name']); ?>">
                </div>
                <div class="form_group">
                    <label for="peer_edu_phone">Peer Educator Phone</label>
                    <input type="text" name="peer_edu_phone" value="<?php echo htmlspecialchars($currentSettings['peer_edu_phone']); ?>">
                </div>
                <div class="form_group">
                    <label for="rx_supporter_name">Treatment Supporter Name</label>
                    <input type="text" name="rx_supporter_name" value="<?php echo htmlspecialchars($currentSettings['rx_supporter_name']); ?>">
                </div>
                <div class="form_group">
                    <label for="drugname">Drug</label>
                    <select name="drugname" required>
                        <?php
                        $sql = "SELECT drugname FROM drug";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['drugname'] == $currentSettings['drugname']) ? 'selected' : '';
                                echo "<option value='" . $row['drugname'] . "' $selected>" . $row['drugname'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No drugs found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="dosage">Initial Dosage</label>
                    <input type="text" name="dosage" value="<?php echo htmlspecialchars($currentSettings['dosage']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="reasons">Reasons for Dose Adjustments</label>
                    <input type="text" name="reasons" value="<?php echo htmlspecialchars($currentSettings['reasons']); ?>">
                </div>
                <div class="form_group">
                    <label for="current_status">Current Status</label>
                    <select name="current_status" required>
                        <?php
                        $sql = "SELECT status_name FROM status";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == $currentSettings['current_status']) ? 'selected' : '';
                                echo "<option value='" . $row['status_name'] . "' $selected>" . $row['status_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No statuses found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="hcw_name">Healthcare Worker Name</label>
                    <input type="text" name="hcw_name" value="<?php echo htmlspecialchars($hcwName); ?>" class="readonly-input" readonly>
                </div>
                <div class="form_group">
                    <label for="comp_date">Status Change Date</label>
                    <input type="datetime-local" name="comp_date" value="<?php echo date('Y-m-d\TH:i', strtotime($currentSettings['comp_date'])); ?>" required>
                </div>
                <div class="form_group">
                    <input type="submit" name="update" class="custom-submit-btn" value="Update Patient Information">
                </div>
            </div>
        </form>
    </div>
</body>
</html>