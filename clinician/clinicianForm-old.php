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
$clinician_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $clinician_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Get the patient ID from the query parameter
$p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;

// Fetch patient data from patients and viral_load tables
$combinedData = [];
if ($p_id) {
    $query = "
        SELECT
            p.p_id,
            p.mat_id COLLATE utf8mb4_general_ci AS mat_id,
            p.clientName,
            p.nickName,
            p.sname,
            p.dob,
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age,
            p.reg_date,
            p.sex,
            p.marital_status,
            p.current_status,
            COALESCE(vl.results, mh.results) AS results,
            COALESCE(vl.last_vlDate, mh.last_vlDate) AS last_vlDate,
            COALESCE(vl.next_appointment, mh.next_appointment, p.next_appointment) AS next_appointment
        FROM patients p
        LEFT JOIN viral_load vl ON p.mat_id COLLATE utf8mb4_general_ci = vl.mat_id COLLATE utf8mb4_general_ci
        LEFT JOIN medical_history mh ON p.mat_id COLLATE utf8mb4_general_ci = mh.mat_id COLLATE utf8mb4_general_ci
        WHERE p.p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $p_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $combinedData = $result->fetch_assoc();
        }
    } else {
        die("Query Error: " . $stmt->error);
    }
    $stmt->close();
}

// Define default values for missing fields
$defaultData = [
    'p_id' => '',
    'mat_id' => '',
    'clientName' => '',
    'nickName' => '',
    'sname' => '',
    'dob' => '',
    'age' => '',
    'reg_date' => '',
    'art_regimen' => '',
    'regimen_type' => '',
    'tb_status' => '',
    'sex' => '',
    'marital_status' => '',
    'current_status' => '',
    'results' => '',
    'last_vlDate' => '',
    'next_appointment' => '',
];
$combinedData = array_merge($defaultData, $combinedData);

// Handle form submission feedback
$successMessages = [];
$errorMessages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['mat_id']) || empty($_POST['clientName'])) {
        $errorMessages[] = "MAT ID and Client Name are required.";
    } else {
        // Assume clinicianForm_process.php handles the insert
        // Redirect back to clinician_list.php with a success message
        $successMessages[] = "Form submitted successfully.";
        header("Location: clinician_list.php?message=" . urlencode("Form submitted successfully."));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinician Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .grid-container{display:grid;grid-template-columns:repeat(5,1fr);grid-gap:20px;background-color:#9FB;margin:0 50px;padding:20px}
        .form-control,input,select{width:80%;height:30px;margin-bottom:15px;margin-top:10px}
        label{font-weight:bold}
        h2{color:#2C3162;margin-top:20px;margin-left:50px}
        #btn-submit{width:250px;color:#fff;background-color:#2C3162;height:35px;border-radius:5px;border:none;cursor:pointer}
        .readonly-input{background-color:#E8E8E8;cursor:not-allowed}
        textarea{width:80%;height:80px}
        .alert{padding:15px;margin:20px 50px;border:1px solid transparent;border-radius:4px}
        .alert-success{color:#3c763d;background-color:#dff0d8;border-color:#d6e9c6}
        .alert-danger{color:#a94442;background-color:#f2dede;border-color:#ebccd1}
</style>
    <script>

        function toggleARTFields() {
            const hivStatus = document.getElementById('hiv_status').value;
            const artRegimen = document.getElementById('art_regimen');
            const regimenType = document.getElementById('regimen_type');

            if (hivStatus === 'Negative') {
                artRegimen.disabled = true;
                regimenType.disabled = true;
                artRegimen.value = 'None';
                regimenType.value = 'None';
            } else {
                artRegimen.disabled = false;
                regimenType.disabled = false;
            }
        }

        // Re-enable fields before form submission so they get submitted
        document.addEventListener('DOMContentLoaded', function() {
            toggleARTFields();

            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                // Temporarily enable disabled fields before submission
                const artRegimen = document.getElementById('art_regimen');
                const regimenType = document.getElementById('regimen_type');

                if (artRegimen.disabled) {
                    artRegimen.disabled = false;
                    artRegimen.value = 'None';
                }
                if (regimenType.disabled) {
                    regimenType.disabled = false;
                    regimenType.value = 'None';
                }
            });
        });
    </script>
</head>
<body>
    <h2>Clinical Follow Up Form</h2>

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

    <?php if (!empty($combinedData['p_id'])): ?>
        <form action="clinicianForm_process-old.php" method="post" class="post">
            <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($combinedData['p_id']); ?>">
            <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($combinedData['current_status']); ?>">
            <input type="hidden" name="clinician_name" value="<?php echo htmlspecialchars($clinician_name); ?>">
            <div class="grid-container">
                <div class="grid-item">
                    <label for="visitDate">Visit Date</label><br>
                    <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>"><br>

                    <label for="mat_id">MAT ID</label><br>
                    <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['mat_id']); ?>"><br>

                    <label for="clientName">Client Name</label><br>
                    <input type="text" name="clientName" value="<?php echo htmlspecialchars($combinedData['clientName']); ?>" required><br>

                    <label for="nickName">Nick Name</label><br>
                    <input type="text" name="nickName" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['nickName'] ?? ''); ?>"><br>

                    <label for="sname">Sur Name</label><br>
                    <input type="text" name="sname" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['sname'] ?? ''); ?>"><br>
                </div>
                <div class="grid-item">
                    <label for="dob">Date of Birth</label><br>
                    <input type="text" name="dob" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['dob']); ?>"><br>

                    <label for="age">Age</label><br>
                    <input type="text" name="age" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['age']); ?>"><br>

                    <label for="reg_date">Enrolment Date</label><br>
                    <input type="text" name="reg_date" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['reg_date']); ?>"><br>

                    <label for="sex">Gender</label><br>
                    <input type="text" name="sex" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['sex']); ?>"><br>

                    <label for="marital_status">Marital Status</label><br>
                    <input type="text" name="marital_status" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['marital_status'] ?? ''); ?>"><br>
                </div>
                <div class="grid-item">
                    <label for="hiv_status">HIV Status</label><br>
                    <select name="hiv_status" id="hiv_status" class="form-control" onchange="toggleARTFields()">
                        <?php
                        $sql = "SELECT hiv_status_name FROM tbl_hiv_status";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['hiv_status_name'] == 'Negative') ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['hiv_status_name']) . "' $selected>" . htmlspecialchars($row['hiv_status_name'] ?? '') . "</option>";
                            }
                        } else {
                            echo "<option value=''>No status found</option>";
                        }
                        ?>
                    </select><br>

                    <label for="art_regimen">ART Regimen</label><br>
                    <select name="art_regimen" id="art_regimen" class="form-control">
                        <?php
                        $sql = "SELECT regimen_name FROM regimens";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['regimen_name'] == 'None') ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['regimen_name']) . "' $selected>" . htmlspecialchars($row['regimen_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No regimen found</option>";
                        }
                        ?>
                    </select><br>

                    <label for="regimen_type">Regimen Type</label><br>
                    <select name="regimen_type" id="regimen_type" class="form-control">
                        <?php
                        $sql = "SELECT regimen_type_name FROM regimen_type";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['regimen_type_name'] == 'None') ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['regimen_type_name']) . "' $selected>" . htmlspecialchars($row['regimen_type_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No type found</option>";
                        }
                        ?>
                    </select><br>

                    <label for="tb_status">TB Status</label><br>
                    <select name="tb_status" class="form-control">
                        <?php
                        $sql = "SELECT status_name FROM tb_status";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == 'Unknown') ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['status_name']) . "' $selected>" . htmlspecialchars($row['status_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No status found</option>";
                        }
                        ?>
                    </select><br>
                </div>
                <div class="grid-item">
                    <label for="hepc_status">Hepatitis C Status</label><br>
                    <select name="hepc_status" class="form-control">
                        <?php
                        $sql = "SELECT status_name FROM hepc_status";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == 'Unknown') ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['status_name']) . "' $selected>" . htmlspecialchars($row['status_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No status found</option>";
                        }
                        ?>
                    </select><br>
                    <label for="other_status">Other Disease Status</label><br>
                    <select name="other_status" class="form-control">
                        <?php
                        $sql = "SELECT status_name FROM other_status";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == 'None') ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['status_name']) . "' $selected>" . htmlspecialchars($row['status_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No status found</option>";
                        }
                        ?>
                    </select><br>

                    <label for="clinical_notes">Clinical Notes</label><br>
                    <textarea name="clinical_notes" id="clinical_notes" cols="30" rows="4"></textarea><br>

                    <label for="current_status">Current Status</label><br>
                    <input type="text" name="current_status" class="readonly-input" readonly value="<?php echo htmlspecialchars($combinedData['current_status']); ?>"><br>
                </div>
                <div class="grid-item">
                    <label for="last_vlDate">Last VL Date</label><br>
                    <input type="date" name="last_vlDate" value="<?php echo htmlspecialchars($combinedData['last_vlDate']); ?>"><br>

                    <label for="results">VL Results</label><br>
                    <input type="text" name="results" value="<?php echo htmlspecialchars($combinedData['results'] ?? ''); ?>"><br>

                    <label for="clinician_name">Clinician Name</label><br>
                    <input type="text" name="clinician_name" value="<?php echo htmlspecialchars($clinician_name); ?>" class="readonly-input" readonly><br>

                    <label for="next_appointment">Next Appointment Date</label><br>
                    <input type="date" name="next_appointment" value="<?php echo htmlspecialchars($combinedData['next_appointment']); ?>"><br>

                    <button type="submit" name="submit" id="btn-submit">Submit</button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">No patient found for the provided ID.</div>
    <?php endif; ?>
</body>
</html>