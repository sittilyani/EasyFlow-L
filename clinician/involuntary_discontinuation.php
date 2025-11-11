<?php
require_once '../includes/config.php';

// Fetch clinicians and counselors from tblusers
$clinician_query = "SELECT full_name FROM tblusers WHERE userrole IN ('clinician', 'counselor')";
$clinician_result = mysqli_query($conn, $clinician_query);
$clinicians = [];
while ($row = mysqli_fetch_assoc($clinician_result)) {
    $clinicians[] = $row['full_name'];
}

// Fetch CSOs from csos table
$cso_query = "SELECT cso_id, cso_name FROM csos";
$cso_result = mysqli_query($conn, $cso_query);
$csos = [];
while ($row = mysqli_fetch_assoc($cso_result)) {
    $csos[] = $row;
}

// Fetch patient details if p_id is provided
$client = null;
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];
    $patient_query = "SELECT clientName, mat_id, reg_date FROM patients WHERE p_id = ?";
    $stmt = mysqli_prepare($conn, $patient_query);
    mysqli_stmt_bind_param($stmt, "s", $p_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($patient_result);
    mysqli_stmt_close($stmt);
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

    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            <div><h2>MEDICALLY ASSISTED THERAPY</h2><h4>INVOLUNTARY DISCONTINUATION FORM</h4></div>
            <p>FORM 3H VER. APR. 2023</p>
        </div>

        <form action="submit_form3h.php" method="POST">
            <div class="form-group">
                <label for="visitDate">Date (dd/mm/yyyy):</label>
                <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="client_name">Name of Client:</label>
                <input type="text" id="client_name" name="client_name" class='readonly-input' readonly value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="mat_id">MAT ID No.:</label>
                <input type="text" id="mat_id" name="mat_id" class='readonly-input'  readonly value="<?php echo $client ? htmlspecialchars($client['mat_id']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="enroll_date">Date Enrolled:</label>
                <input type="date" id="enroll_date" name="enroll_date" class='readonly-input'  readonly value="<?php echo $client ? htmlspecialchars($client['reg_date']) : ''; ?>" required>
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
                        <select name="clinician_name" required>
                            <option value="">Select Clinician</option>
                            <?php foreach ($clinicians as $clinician): ?>
                                <option value="<?php echo htmlspecialchars($clinician); ?>"><?php echo htmlspecialchars($clinician); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="clinician_org" required></td>
                    <td><input type="text" name="clinician_signature" required></td>
                    <td><input type="date" name="clinician_date" required></td>
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
                    <td><input type="text" name="counselor_org" required></td>
                    <td><input type="text" name="counselor_signature" required></td>
                    <td><input type="date" name="counselor_date" required></td>
                </tr>
                <tr>
                    <td>CSO Clinician/Counselor</td>
                    <td>
                        <select name="cso_name" required>
                            <option value="">Select CSO</option>
                            <?php foreach ($csos as $cso): ?>
                                <option value="<?php echo htmlspecialchars($cso['cso_name']); ?>" data-cso-id="<?php echo htmlspecialchars($cso['cso_id']); ?>"><?php echo htmlspecialchars($cso['cso_name']); ?></option>
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