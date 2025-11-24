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

// Fetch facility details
$queryFacilitySettings = "SELECT facilityname, mflcode, countyname, subcountyname, facilityincharge, facilityphone FROM facility_settings LIMIT 1";
$resultFacilitySettings = $conn->query($queryFacilitySettings);
$facilityName = '';
if ($resultFacilitySettings && $resultFacilitySettings->num_rows > 0) {
    $rowFacilitySettings = $resultFacilitySettings->fetch_assoc();
    $facilityName = htmlspecialchars($rowFacilitySettings['facilityname']);
    $countyName = htmlspecialchars($rowFacilitySettings['countyname']);
    $subcountyName = htmlspecialchars($rowFacilitySettings['subcountyname']);
    $mflCode = $rowFacilitySettings['mflcode'];
    $facilityIncharge = htmlspecialchars($rowFacilitySettings['facilityincharge']);
    $facilityPhone = htmlspecialchars($rowFacilitySettings['facilityphone']);
}

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
        $mat_id = $_POST['mat_id'];
        $enroll_date = $_POST['enroll_date'];
        $discontinue_date = $_POST['discontinue_date'];

        // Process reasons (array)
        $reasons = isset($_POST['reasons']) ? $_POST['reasons'] : [];
        if (isset($_POST['other_reason']) && !empty($_POST['other_reason'])) {
            $reasons[] = 'Other: ' . $_POST['other_reason'];
        }
        $reasons_text = implode(', ', $reasons);

        $plan = $_POST['plan'];
        $follow_up = $_POST['follow_up'];

        // Treatment team details
        $clinician_name_form = $_POST['clinician_name'];
        $clinician_org = $_POST['clinician_org'];
        $clinician_signature_form = $_POST['clinician_signature'];
        $clinician_date = $_POST['clinician_date'];

        $counselor_name = $_POST['counselor_name'];
        $counselor_org = $_POST['counselor_org'];
        $counselor_signature = $_POST['counselor_signature'];
        $counselor_date = $_POST['counselor_date'];

        $cso_name = $_POST['cso_name'];
        $cso_org = $_POST['cso_org'];
        $cso_signature = $_POST['cso_signature'];
        $cso_date = $_POST['cso_date'];

        // Insert into involuntary_discontinuation table
        $insertQuery = "INSERT INTO involuntary_discontinuation
            (visit_date, client_name, mat_id, enroll_date, discontinue_date, reasons, discontinuation_plan,
             follow_up_plan, clinician_name, clinician_org, clinician_signature, clinician_date,
             counselor_name, counselor_org, counselor_signature, counselor_date,
             cso_name, cso_org, cso_signature, cso_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssssssssssssssssssss',
            $visitDate, $client_name, $mat_id, $enroll_date, $discontinue_date, $reasons_text, $plan,
            $follow_up, $clinician_name_form, $clinician_org, $clinician_signature_form, $clinician_date,
            $counselor_name, $counselor_org, $counselor_signature, $counselor_date,
            $cso_name, $cso_org, $cso_signature, $cso_date
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting form data: " . $stmt->error);
        }
        $stmt->close();

        // Update patient status to 'stopped'
        $updateQuery = "UPDATE patients SET current_status = 'stopped' WHERE mat_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('s', $mat_id);

        if (!$stmt->execute()) {
            throw new Exception("Error updating patient status: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        $successMessage = "Form submitted successfully and patient status updated to 'stopped'.";

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
    <title>MAT Involuntary Discontinuation Form 3H</title>
    <style>
        body {font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px;}
        .form-container {width: 80%; margin: 0 auto; padding: 30px; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);}
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
        .readonly-input {cursor: not-allowed; background: #FFCCFF;}
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
            <div><h2>MEDICALLY ASSISTED THERAPY</h2><h4>INVOLUNTARY DISCONTINUATION FORM</h4></div>
            <p>FORM 3H VER. APR. 2023</p>
        </div>

        <form action="" method="POST">
            <div class="form-group">
                <label for="visitDate">Date (dd/mm/yyyy):</label>
                <input type="date" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="client_name">Name of Client:</label>
                <input type="text" id="client_name" name="client_name" class='readonly-input' readonly value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="mat_id">MAT ID No.:</label>
                <input type="text" id="mat_id" name="mat_id" class='readonly-input' readonly value="<?php echo $client ? htmlspecialchars($client['mat_id']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="enroll_date">Date Enrolled:</label>
                <input type="date" id="enroll_date" name="enroll_date" class='readonly-input' readonly value="<?php echo ($client && !empty($client['reg_date'])) ? htmlspecialchars($client['reg_date']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="discontinue_date">Date Commenced on Involuntary Discontinuation:</label>
                <input type="date" id="discontinue_date" name="discontinue_date" placeholder="dd/mm/yyyy" required>
            </div>

            <div class="checkbox-group">
                <label>Reason for Discontinuation (Tick all that apply):</label>
                <label><input type="checkbox" name="reasons[]" value="High risk of drug overdose"> High risk of drug overdose due to frequent intoxication with alcohol and other drugs, overdose incidents despite repeated client education</label>
                <label><input type="checkbox" name="reasons[]" value="Violence"> Verbal or physical violence or threatened violence to other clients or staff</label>
                <label><input type="checkbox" name="reasons[]" value="Drug possession"> Drug possession or dealing around the clinic or institution</label>
                <label><input type="checkbox" name="reasons[]" value="Weapons"> Carrying weapons around the clinic or institution</label>
                <label><input type="checkbox" name="reasons[]" value="Diversion"> Diversion of methadone or buprenorphine</label>
                <label><input type="checkbox" name="reasons[]" value="Property damage"> Property damage or theft from the clinic or institution</label>
                <label><input type="checkbox" name="reasons[]" value="Disruptive behavior"> Repeated unacceptable disruptive behavior around the clinic or institution</label>
                <label><input type="checkbox" name="reasons[]" value="Other"> Other
                    <input type="text" name="other_reason" placeholder="Specify other reason">
                </label>
            </div>

            <div class="radio-group">
                <label>Recommended Discontinuation Plan:</label>
                <label><input type="radio" name="plan" value="Abrupt cessation" required> Abrupt cessation</label>
                <label><input type="radio" name="plan" value="Rapid taper"> Rapid taper</label>
                <label><input type="radio" name="plan" value="Gradual taper"> Gradual taper</label>
            </div>

            <div class="form-group">
                <label for="follow_up">Follow Up Plan:</label>
                <textarea id="follow_up" name="follow_up" rows="5" required></textarea>
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
                <tr>
                    <td>CSO Clinician/Counselor</td>
                    <td>
                        <select name="cso_name" required>
                            <option value="">Select CSO Clinician</option>
                            <?php foreach ($clinicians as $clinician): ?>
                                <option value="<?php echo htmlspecialchars($clinician); ?>"><?php echo htmlspecialchars($clinician); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="cso_org" required></td>
                    <td><input type="text" name="cso_signature" required></td>
                    <td><input type="date" name="cso_date" required></td>
                </tr>
            </table>

            <button type="submit" class="submit-button">Submit Form</button>
        </form>
    </div>
</body>
</html>