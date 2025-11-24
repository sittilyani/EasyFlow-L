<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$loggedInUserId = $_SESSION['user_id'];

// Fetch logged-in user details
$userQuery = "SELECT first_name, last_name, full_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
$loggedInUser = $result->fetch_assoc();
$stmt->close();

$clinician_name = $loggedInUser['full_name'];
$first_name = $loggedInUser['first_name'];
$last_name = $loggedInUser['last_name'];
$clinician_signature = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Fetch facility name from facility settings table
$facility_query = "SELECT facility_id, facilityname FROM facility_settings LIMIT 1";
$facility_result = mysqli_query($conn, $facility_query);
$facility = mysqli_fetch_assoc($facility_result);
$facilityName = $facility ? htmlspecialchars($facility['facilityname']) : '';

// Fetch clinicians and counselors from tblusers
$clinician_query = "SELECT full_name FROM tblusers WHERE userrole IN ('clinician', 'counselor')";
$clinician_result = mysqli_query($conn, $clinician_query);
$clinicians = [];
while ($row = mysqli_fetch_assoc($clinician_result)) {
    $clinicians[] = $row['full_name'];
}

// Fetch patient details if p_id is provided
$client = null;
$p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;
if ($p_id) {
    $patient_query = "SELECT clientName, mat_id, reg_date FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($patient_query);
    if ($stmt) {
        $stmt->bind_param("i", $p_id);
        $stmt->execute();
        $patient_result = $stmt->get_result();
        if ($patient_result->num_rows > 0) {
            $client = $patient_result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->begin_transaction();

        // Get form data
        $visitDate = $_POST['visitDate'];
        $client_name = $_POST['client_name'];
        $facilityname = $_POST['facilityname'];
        $discontinued_Date = $_POST['discontinued_Date'];
        $mat_id = $_POST['mat_id'];

        // Process reasons (array)
        $reasons = isset($_POST['reasons']) ? $_POST['reasons'] : [];
        if (isset($_POST['other_reason']) && !empty($_POST['other_reason'])) {
            $reasons[] = 'Other: ' . $_POST['other_reason'];
        }
        $reasons_text = implode(', ', $reasons);

        $discontinuation_reason = $_POST['follow_up'];
        $discontinue_date = $_POST['discontinue_date'];
        $thumb_print = $_POST['thumb_print'];

        // Treatment team details
        $clinician_name_form = $_POST['clinician_name'];
        $clinician_org = $_POST['clinician_org'];
        $clinician_signature_form = $_POST['clinician_signature'];
        $clinician_date = $_POST['clinician_date'];

        $counselor_name = $_POST['counselor_name'];
        $counselor_org = $_POST['counselor_org'];
        $counselor_signature = $_POST['counselor_signature'];
        $counselor_date = $_POST['counselor_date'];

        // Insert into voluntary_discontinuation table
        $insertQuery = "INSERT INTO voluntary_discontinuation
            (visit_date, client_name, facilityname, discontinued_date, mat_id, request_type,
             discontinuation_reason, discontinue_date, client_signature, clinician_name,
             clinician_org, clinician_signature, clinician_date, counselor_name, counselor_org,
             counselor_signature, counselor_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('sssssssssssssssss',
            $visitDate, $client_name, $facilityname, $discontinued_Date, $mat_id, $reasons_text,
            $discontinuation_reason, $discontinue_date, $thumb_print, $clinician_name_form,
            $clinician_org, $clinician_signature_form, $clinician_date, $counselor_name,
            $counselor_org, $counselor_signature, $counselor_date
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting form data: " . $stmt->error);
        }
        $stmt->close();

        // Update patient status to 'Voluntary Discontinuation'
        $updateQuery = "UPDATE patients SET current_status = 'Voluntary Discontinuation' WHERE mat_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('s', $mat_id);

        if (!$stmt->execute()) {
            throw new Exception("Error updating patient status: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        $successMessage = "Form submitted successfully and patient status updated to 'Voluntary Discontinuation'.";

    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Voluntary Discontinuation Form 3I</title>
    <style>
        body {font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px;}
        .form-container {width: 70%; margin: 0 auto; padding: 30px; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);}
        .form-header {display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; margin-bottom: 30px;}
        .form-header h2 {color: #2c3e50; font-size: 24px; margin: 0;}
        .form-header h3 {color: #6633CC; font-size: 18px; margin: 5px 0;}
        .form-header p {color: #7f8c8d; font-size: 14px;}
        .form-group {display: flex; align-items: center; margin-bottom: 20px;}
        .form-group label {width: 250px; font-weight: bold; color: #2c3e50;}
        .form-group input, .form-group select, .form-group textarea {width: 350px; padding: 10px; border: 1px solid #dcdcdc; border-radius: 5px; font-size: 14px; transition: border-color 0.3s;}
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {border-color: #3498db; outline: none;}
        .checkbox-group {margin: 20px 0;}
        .checkbox-group label {display: block; margin-bottom: 12px; color: #34495e;}
        .checkbox-group input[type="checkbox"] {margin-right: 10px;}
        .radio-group {margin: 20px 0;}
        .radio-group label {display: block; margin-bottom: 10px; color: #34495e;}
        .radio-group input[type="radio"] {margin-right: 10px;}
        .signature-table {width: 100%; border-collapse: collapse; margin-top: 20px;}
        .signature-table th, .signature-table td {border: 1px solid #dcdcdc; padding: 12px; text-align: left; font-size: 14px;}
        .signature-table th {background-color: #3498db; color: #fff;}
        .signature-table td {background-color: #f9f9f9;}
        .submit-button {display: block; margin: 30px auto 0; padding: 12px 30px; background-color: #3498db; color: #fff; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s;}
        .submit-button:hover {background-color: #2980b9;}
        .readonly-input {cursor: not-allowed; background: #FFF0FF;}
        .alert {padding: 15px; margin-bottom: 20px; border-radius: 5px;}
        .alert-success {background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724;}
        .alert-danger {background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;}
    </style>
</head>
<body>
    <div class="form-container">
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <div><h2>MEDICALLY ASSISTED THERAPY</h2><h4>VOLUNTARY DISCONTINUATION REQUEST FORM</h4></div>
            <p>FORM 3I VER. APR. 2022</p>
        </div>

        <form action="" method="POST">
            <div class="form-group">
                <label for="visitDate">Visit Date (dd/mm/yyyy):</label>
                <input type="date" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                I &nbsp;&nbsp;
                <input type="text" id="client_name" name="client_name" class='readonly-input' readonly value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required> Voluntarily request to have my MAT treatment discontinued from
                <input type="text" name="facilityname" class='readonly-input' readonly value="<?php echo $facilityName; ?>">
            </div>
            <div class="form-group">
                With effect from&nbsp;&nbsp;  <input type="date" name="discontinued_Date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="mat_id">MAT ID No.:</label>
                <input type="text" id="mat_id" name="mat_id" class='readonly-input' readonly value="<?php echo $client ? htmlspecialchars($client['mat_id']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <p>I have reached this decision on my own volition and I have discussed the reasons as well as possible complications of this decision with my primary counsellor and the MAT clinician.</p>
            </div>
            <div class="checkbox-group">
                <label>I wish to request for (tick one);</label>
                <label><input type="checkbox" name="reasons[]" value="Abrupt cessation"> Abrupt cessation</label>
                <label><input type="checkbox" name="reasons[]" value="Accelerated taper">Accelerated taper</label>
                <label><input type="checkbox" name="reasons[]" value="Gradual taper of my methadone/buprenorphine dose">Gradual taper of my methadone/buprenorphine dose</label>
                <label><input type="checkbox" name="reasons[]" value="Other"> Other
                    <input type="text" name="other_reason" placeholder="Specify other reason">
                </label>
            </div>
            <div class="form-group">
                <label for="follow_up">Reasons for Discontinuation:</label>
                <textarea id="follow_up" name="follow_up" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label for="discontinue_date">Date Commenced on Discontinuation:</label>
                <input type="date" id="discontinue_date" name="discontinue_date" required>
            </div>
            <div>
                <p>I understand that upon completion of the dose taper, any request to join the MAT program will be treated as a re-induction and hence will follow the laid down procedures.</p>
            </div>

            <div class="form-group">
                <label for="thumb_print">Client's Signature or Left Thumb Print:</label>
                <textarea id="thumb_print" name="thumb_print" rows="5" required></textarea>
            </div>

            <h3>Treatment Team</h3>
            <table class="signature-table">
                <tr>
                    <th>Designation</th>
                    <th>Name</th>
                    <th>Organization</th>
                    <th>Signature</th>
                    <th>Date</th>
                </tr>
                <tr>
                    <td>MAT Clinician</td>
                    <td>
                        <input type="text" name="clinician_name" class="readonly-input" readonly value="<?php echo htmlspecialchars($clinician_name); ?>" required>
                    </td>
                    <td>
                        <input type="text" name="clinician_org" class='readonly-input' readonly value="<?php echo $facilityName; ?>" required>
                    </td>
                    <td>
                        <input type="text" name="clinician_signature" class="readonly-input" readonly value="<?php echo $clinician_signature; ?>" required>
                    </td>
                    <td><input type="date" name="clinician_date" value="<?php echo date('Y-m-d'); ?>" required></td>
                </tr>
                <tr>
                    <td>MAT Counselor</td>
                    <td>
                        <select name="counselor_name" required>
                            <option value="">Select Counselor</option>
                            <?php foreach ($clinicians as $clinician): ?>
                                <option value="<?php echo htmlspecialchars($clinician); ?>"><?php echo htmlspecialchars($clinician); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="counselor_org" value="<?php echo $facilityName; ?>" required></td>
                    <td><input type="text" name="counselor_signature" required></td>
                    <td><input type="date" name="counselor_date" required></td>
                </tr>
            </table>

            <button type="submit" class="submit-button">Submit Form</button>
        </form>
    </div>
</body>
</html>