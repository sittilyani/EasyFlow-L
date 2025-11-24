<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Display success or error messages
$successMessage = isset($_GET['success']) ? urldecode($_GET['success']) : '';
$errorMessage = isset($_GET['error']) ? urldecode($_GET['error']) : '';

// Fetch patient details if p_id is provided
$client = null;
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];
    $patient_query = "SELECT clientName, sex, dob, client_phone, reg_facility, rx_supporter_name, mat_id, reg_date FROM patients WHERE p_id = ?";
    $stmt = mysqli_prepare($conn, $patient_query);
    mysqli_stmt_bind_param($stmt, "i", $p_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($patient_result);
    mysqli_stmt_close($stmt);
}

// Fetch photo details by joining patients and photos using mat_id (based on p_id from URL)
$photo = null;
$photoPath = '';

if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    $sql_photo = "
        SELECT photos.image
        FROM photos
        INNER JOIN patients ON photos.mat_id = patients.mat_id
        WHERE patients.p_id = ?
        ORDER BY photos.visitDate DESC
        LIMIT 1
    ";

    $stmt_photo = $conn->prepare($sql_photo);
    $stmt_photo->bind_param('i', $p_id);
    $stmt_photo->execute();
    $result_photo = $stmt_photo->get_result();
    $photo = $result_photo->fetch_assoc();
    $stmt_photo->close();

    // Check if the photo file exists
    if ($photo && !empty($photo['image'])) {
        $photoPath = '../clientPhotos/' . $photo['image'];
        if (!file_exists($photoPath)) {
            $photoPath = ''; // Clear if the file doesn't exist physically
        }
    }
}

// Fetch clinicians and counselors from tblusers
$clinician_query = "SELECT full_name FROM tblusers WHERE userrole IN ('clinician', 'pyschologist', 'admin', 'super admin')";
$clinician_result = mysqli_query($conn, $clinician_query);
$clinicians = [];
while ($row = mysqli_fetch_assoc($clinician_result)) {
    $clinicians[] = $row['full_name'];
}

// Fetch facility settings
$facilityName = "N/A";
$countyName = "N/A";
$subcountyName = "N/A";
$mflCode = "N/A";
$facilityIncharge = "N/A";
$facilityPhone = "N/A";

$queryFacilitySettings = "SELECT facilityname, mflcode, countyname, subcountyname, facilityincharge, facilityphone FROM facility_settings LIMIT 1";
$resultFacilitySettings = $conn->query($queryFacilitySettings);

if ($resultFacilitySettings && $resultFacilitySettings->num_rows > 0) {
    $rowFacilitySettings = $resultFacilitySettings->fetch_assoc();
    $facilityName = htmlspecialchars($rowFacilitySettings['facilityname']);
    $countyName = htmlspecialchars($rowFacilitySettings['countyname']);
    $subcountyName = htmlspecialchars($rowFacilitySettings['subcountyname']);
    $mflCode = $rowFacilitySettings['mflcode'];
    $facilityIncharge = $rowFacilitySettings['facilityincharge'];
    $facilityPhone = htmlspecialchars($rowFacilitySettings['facilityphone']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDICALLY ASSISTED THERAPY TRANSFER/TRANSIT FORM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; background: #f5f5f5; }
        .container { width: 75%; margin: 0 auto; background: white; padding: 20px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .header-wrapper { position: relative; margin-bottom: 20px; }
        .header { text-align: center; padding: 10px 0; }
        .header h2 { font-size: 16px; margin: 5px 0; font-weight: bold; }
        .header h3 { font-size: 14px; margin: 8px 0; font-weight: bold; }
        .logo { width: 120px; height: auto; margin: 10px 0; }
        .client-photo { position: absolute; top: 30px; right: 60px; width: 100px; height: 120px; border: 2px solid #3498db; background: #f9f9f9; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999; text-align: center; padding: 5px; }
        .section-title { background-color: #3498db; color: white; padding: 8px 40px; margin: 15px 40px 0; font-size: 14px; font-weight: bold; border-radius: 3px; }
        .form-group-1 { padding: 40px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: bold; color: #2c3e50; font-size: 11px; }
        .form-group input, .form-group select, .form-group textarea { padding: 8px; border: 1px solid #dcdcdc; border-radius: 3px; font-size: 11px; font-family: Arial, sans-serif; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        select { cursor: pointer; }
        .readonly-input, .read-only { background: #f0f8ff; cursor: not-allowed; }
        .required-field::after { content: " *"; color: red; }
        .full-width { grid-column: 1 / -1; }
        .signature-table { width: 92%; border-collapse: collapse; margin-left: auto; margin-right: auto; font-size: 11px; }
        .signature-table th, .signature-table td { border: 1px solid #dcdcdc; padding: 8px; text-align: left; }
        .signature-table th { background-color: #3498db; color: #fff; font-weight: bold; }
        .signature-table td { background-color: #f9f9f9; }
        .signature-table input, .signature-table select { width: 100%; padding: 5px; border: 1px solid #dcdcdc; border-radius: 3px; font-size: 10px; }
        .submit-button { background-color: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 14px; font-weight: bold; cursor: pointer; margin: 20px 0; transition: background-color 0.3s; }
        .submit-button:hover { background-color: #2980b9; }
        .export-button { background-color: #27ae60; color: white; padding: 10px 25px; border: none; border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer; margin: 10px 5px; text-decoration: none; display: inline-block; transition: background-color 0.3s; }
        .export-button:hover { background-color: #229954; }
        .button-group { text-align: center; margin: 20px 0;}
        @media print {
            body { background: white; }
            .container { max-width: 100%; padding: 0; }
            .submit-button, .export-button, .button-group, .alert { display: none; }
            @page { size: A4; margin: 15mm; }
        }
        @media screen and (max-width: 768px) {
            .form-group-1 { grid-template-columns: 1fr; }
            .client-photo { position: static; margin: 10px auto; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($successMessage): ?>
            <div class="alert alert-success" id="success-message">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" id="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="header-wrapper">
            <div class="header">
                <h2>Republic of Kenya</h2>
                <img src="../assets/images/Government of Kenya.png" class="logo" alt="Government of Kenya Logo">
                <h2>Ministry of Health</h2>
                <h2>FORM 3G VER. APRI. 2023</h2>
                <h3>MEDICALLY ASSISTED THERAPY TRANSFER/TRANSIT FORM</h3>
            </div>
            <div class="client-photo">
                <?php if ($photoPath && file_exists($photoPath)): ?>
                    <img src="<?php echo $photoPath; ?>" alt="Client Photo" width="150" height="120" style="border-radius: 8px;">
                <?php else: ?>
                    <div class="photo-placeholder">
                        <span>No photo available</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="save_transfer_form.php" enctype="multipart/form-data">
            <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($p_id ?? ''); ?>">

            <div class="section-title">CLIENT'S DETAILS</div>

            <div class="form-group-1">
                <div class="form-group">
                    <label for="facilityname" class="required-field">Facility Name</label>
                    <input type="text" name="facilityname" class="readonly-input" readonly value="<?php echo $facilityName; ?>">
                </div>

                <div class="form-group">
                    <label for="mflcode" class="required-field">MFL Code</label>
                    <input type="text" name="mflcode" class="readonly-input" readonly value="<?php echo $mflCode; ?>">
                </div>

                <div class="form-group">
                    <label for="county">County</label>
                    <input type="text" name="county" class="readonly-input" readonly value="<?php echo $countyName; ?>">
                </div>

                <div class="form-group">
                    <label for="sub_county">Sub County</label>
                    <input type="text" name="sub_county" class="readonly-input" readonly value="<?php echo $subcountyName; ?>">
                </div>

                <div class="form-group">
                    <label for="clientName" class="required-field">Client Name</label>
                    <input type="text" id="clientName" name="clientName" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="mat_id">MAT ID</label>
                    <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['mat_id']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="sex">Sex</label>
                    <input type="text" id="sex" name="sex" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['sex']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="text" id="dob" name="dob" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['dob']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="client_phone">Client Phone</label>
                    <input type="text" id="client_phone" name="client_phone" value="<?php echo $client ? htmlspecialchars($client['client_phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="reg_facility">MAT Clinic Enrolled In</label>
                    <input type="text" id="reg_facility" name="reg_facility" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['reg_facility']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="reg_date">MAT Enrollment Date</label>
                    <input type="text" id="reg_date" name="reg_date" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['reg_date']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="referral_date">Referral Date</label>
                    <input type="date" id="referral_date" name="referral_date" required>
                </div>

                <div class="form-group">
                    <label for="type_of_movement">Type of Movement</label>
                    <select name="type_of_movement" id="type_of_movement" required>
                        <option value="">Select Type</option>
                        <option value="Transfer Out">Transfer Out</option>
                        <option value="Transit">Transit</option>
                        <option value="Other">Other (Specify)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="other_specify">Other (Specify)</label>
                    <input type="text" name="other_specify" placeholder="If other - please specify">
                </div>

                <div class="form-group">
                    <label for="from_site">From (Referral Site)</label>
                    <input type="text" name="from_site" class="readonly-input" readonly value="<?php echo $client ? htmlspecialchars($client['reg_facility']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="to_site">To (Dispensing Site)</label>
                    <input type="text" name="to_site" required>
                </div>
            </div>

            <div class="section-title">TRANSFER NOTES</div>

            <div class="form-group-1">
                <div class="form-group full-width">
                    <label for="reason_transfer">Reason for Transfer/Transit (Transit not exceeding 30 days, Transfer if more than 30 days)</label>
                    <textarea name="reason_transfer" id="reason_transfer" rows="4" style="font-size: 18px; color: blue; line-height: 1.5; font-family: 'Times New Roman', Times, serif;" required></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="clinical_history">Clinical & Drug Use History</label>
                    <textarea name="clinical_history" id="clinical_history" rows="4" style="font-size: 18px; color: blue; line-height: 1.5; font-family: 'Times New Roman', Times, serif;" required></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="psychosocial">Psychosocial Background & Concerns</label>
                    <textarea name="psychosocial" id="psychosocial" rows="4" style="font-size: 18px; color: blue; line-height: 1.5; font-family: 'Times New Roman', Times, serif;" required></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="lab_investigations">Laboratory Investigations Done & Date</label>
                    <textarea name="lab_investigations" id="lab_investigations" rows="4" style="font-size: 18px; color: blue; line-height: 1.5; font-family: 'Times New Roman', Times, serif;"></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="vaccinations">Vaccinations Done & Date</label>
                    <textarea name="vaccinations" id="vaccinations" rows="4" style="font-size: 18px; color: blue; line-height: 1.5; font-family: 'Times New Roman', Times, serif;"></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="diagnosis">Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosis" rows="4" style="font-size: 18px; color: blue; line-height: 1.5; font-family: 'Times New Roman', Times, serif;" required></textarea>
                </div>

                <div class="form-group">
                    <label for="current_dose">Methadone/Buprenorphine Dose</label>
                    <input type="text" name="current_dose" id="current_dose" required>
                </div>

                <div class="form-group">
                    <label for="date_last_administered">Date & Time Last Administered</label>
                    <input type="datetime-local" name="date_last_administered" id="date_last_administered" required>
                </div>

                <div class="form-group full-width">
                    <label for="other_medications">Other Medications</label>
                    <input type="text" name="other_medications" id="other_medications">
                </div>
            </div>

            <div class="section-title" style="font-weight: bold; margin-top: 20px; margin-bottom: 20px;">TREATMENT TEAM</div>

            <table class="signature-table">
                <thead>
                    <tr>
                        <th>Designation</th>
                        <th>Name</th>
                        <th>Organization</th>
                        <th>Signature</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>

            <div class="button-group">
                <button type="submit" class="submit-button">Submit Form</button>
                <button type="button" style="background: green; color: white; width: 100px; height: 40px; border: none; border-radius: 5px;" id="print-pdf" onclick="window.print()">Print PDF</button>
            </div>
        </form>
    </div>

    <script>
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            var successMsg = document.getElementById('success-message');
            var errorMsg = document.getElementById('error-message');
            if (successMsg) successMsg.style.display = 'none';
            if (errorMsg) errorMsg.style.display = 'none';
        }, 5000);
    </script>
</body>
</html>