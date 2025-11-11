<?php
session_start();
include '../includes/config.php';

// Get the mat_id from the query parameter
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
        // Retrieve all form data
        $vl_id_post = $_POST['vl_id'];
        $mat_id_post = $_POST['mat_id'];
        $clientName = $_POST['clientName'];
        $dob = $_POST['dob'];
        $sex = $_POST['sex'];
        $hiv_status = $_POST['hiv_status'];
        $art_regimen = $_POST['art_regimen'];
        $regimen_type = $_POST['regimen_type'];
        $clinical_notes = $_POST['clinical_notes'];
        $last_vlDate = $_POST['last_vlDate'];
        $results = $_POST['results'];
        $clinician_name = $_POST['clinician_name'];
        $next_appointment = $_POST['next_appointment'];

        // Update query for viral_load table
        $query = "UPDATE viral_load SET
                            clientName = ?,
                            dob = ?,
                            sex = ?,
                            hiv_status = ?,
                            art_regimen = ?,
                            regimen_type = ?,
                            clinical_notes = ?,
                            last_vlDate = ?,
                            results = ?,
                            clinician_name = ?,
                            next_appointment = ?,
                            comp_date = ?
                            WHERE vl_id = ?";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
                die("Prepare failed: " . $conn->error);
        }

        // Bind all parameters
        $stmt->bind_param('ssssssssssssi',
                $clientName,
                $dob,
                $sex,
                $hiv_status,
                $art_regimen,
                $regimen_type,
                $clinical_notes,
                $last_vlDate,
                $results,
                $clinician_name,
                $next_appointment,
                $comp_date,
                $vl_id_post
        );

        if ($stmt->execute()) {
                $successMessage = "Viral Load Status updated successfully";
                echo "<div style='background-color: #deffee; color: green; font-size: 20px; padding: 8px; width: 100%; height: 40px;'>Patient details Updated Successfully</div>";
                header("Refresh: 3; URL=../clinician/treatment.php");
                exit();
        } else {
                $errorMessage = "Error updating record: " . $stmt->error;
                echo "<div style='background-color: #fce8e8; color: red; font-size: 20px; padding: 8px; width: 100%; height: 40px;'>Error updating patient details: " . htmlspecialchars($stmt->error) . "</div>";
        }

        $stmt->close();
}

// Fetch the current settings for the patient from viral_load and patients tables
$currentSettings = [];
if ($mat_id) {
        $query = "SELECT vl.*, p.clientName, p.dob, p.sex
                            FROM viral_load vl
                            JOIN patients p ON vl.mat_id = p.mat_id
                            WHERE vl.mat_id = ?
                            ORDER BY vl.vl_id DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $mat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentSettings = $result->fetch_assoc();
        $stmt->close();
}

// Fetch the logged-in user's clinician name from tblusers
$clinicianName = '';
if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $query = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $clinicianName = $user ? $user['first_name'] . ' ' . $user['last_name'] : '';
        $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
        <title>Viral Load Update</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
        <style>
                form {
                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                        grid-gap: 20px;
                        padding-left: 10px;
                        padding-right: 10px;
                        width: 80%;
                        margin-left: auto;
                        margin-right: auto;
                }
        </style>
</head>
<body>
        <div class="content-main">
                <h2>Update Viral Load Information</h2>
                <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                <?php endif; ?>
                <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                <?php endif; ?>
                <form method="post">
                        <div class="form-group">
                                <label for="mat_id">MAT ID</label>
                                <input type="text" name="mat_id" value="<?= htmlspecialchars($currentSettings['mat_id'] ?? '') ?>" class="readonly-input" readonly required>
                        </div>
                        <div class="form-group">
                                <label for="clientName">Client Name</label>
                                <input type="text" name="clientName" value="<?= htmlspecialchars($currentSettings['clientName'] ?? '') ?>" class="readonly-input" readonly required>
                        </div>
                        <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" value="<?= htmlspecialchars($currentSettings['dob'] ?? '') ?>" class="readonly-input" readonly>
                        </div>
                        <div class="form-group">
                                <label for="reg_date">Date of Enrolment</label>
                                <input type="date" name="reg_date" value="<?= htmlspecialchars($currentSettings['reg_date'] ?? '') ?>" class="readonly-input" readonly required>
                        </div>
                        <div class="form-group">
                                <label for="sex">Sex</label>
                                <input type="text" name="sex" value="<?= htmlspecialchars($currentSettings['sex'] ?? '') ?>" class="readonly-input" readonly>
                        </div>
                        <div class="form-group">
                                <label for="hiv_status">HIV Status</label>
                                <input type="text" name="hiv_status" value="<?= htmlspecialchars($currentSettings['hiv_status'] ?? '') ?>" class="readonly-input" readonly>
                        </div>
                        <div class="form-group">
                            <label for="art_regimen">ART Regimen</label><br>
                            <select name="art_regimen" id="art_regimen" class="form-control">
                                <?php
                                // Re-run query for dropdown options
                                $sql = "SELECT regimen_name FROM regimens";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Check if this option matches the current setting
                                        $selected = ($row['regimen_name'] == ($currentSettings['art_regimen'] ?? '')) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['regimen_name']) . "' $selected>" . htmlspecialchars($row['regimen_name']) . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No regimen found</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="regimen_type">Regimen Type</label><br>
                            <select name="regimen_type" id="regimen_type" class="form-control">
                                <?php
                                // Re-run query for dropdown options
                                $sql = "SELECT regimen_type_name FROM regimen_type";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Check if this option matches the current setting
                                        $selected = ($row['regimen_type_name'] == ($currentSettings['regimen_type'] ?? '')) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['regimen_type_name']) . "' $selected>" . htmlspecialchars($row['regimen_type_name']) . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No type found</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                                <label for="last_vlDate">Last Viral Load Date</label>
                                <input type="date" name="last_vlDate" value="<?= htmlspecialchars($currentSettings['last_vlDate'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                                <label for="results">Last Viral Load Results</label>
                                <input type="text" name="results" value="<?= htmlspecialchars($currentSettings['results'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                                <label for="next_appointment">Next Appointment</label>
                                <input type="date" name="next_appointment" value="<?= htmlspecialchars($currentSettings['next_appointment'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                                <label for="clinician_name">Clinician Name</label>
                                <input type="text" name="clinician_name" value="<?= htmlspecialchars($clinicianName) ?>" class="readonly-input" readonly>
                        </div>
                        <div class="form-group">
                                <label for="clinical_notes">Clinical Notes</label>
                                <textarea name="clinical_notes" cols="33" rows="4"><?= htmlspecialchars($currentSettings['clinical_notes'] ?? '') ?></textarea>
                        </div>
                        <input type="submit" name="update" class="custom-submit-btn" value="Update">
                </form>
        </div>
</body>
</html>