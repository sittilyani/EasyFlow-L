<?php
session_start();
include "../includes/config.php";

// --- 0. PDF GENERATION SETUP (DOMPDF) ---
// You must have DOMPDF installed. Path is assumed.
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$pdf_save_dir = '../consentsforms/';
if (!is_dir($pdf_save_dir)) {
    mkdir($pdf_save_dir, 0777, true);
}


// --- 1. HANDLE FORM SUBMISSION ---
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and collect POST data
    $p_id = $_POST['p_id'] ?? null;
    $client_name = $_POST['client_name'] ?? '';
    $mat_id = $_POST['mat_id'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $visitDate_raw = $_POST['visitDate'] ?? date('d/m/Y');
    // Convert D/M/Y to Y-M-D for database storage
    $date_obj = DateTime::createFromFormat('d/m/Y', $visitDate_raw);
    $visit_date = $date_obj ? $date_obj->format('Y-m-d') : date('Y-m-d');

    $cso = $_POST['cso'] ?? null;
    $declaration_phone = $_POST['client_phone'] ?? null;
    $national_id = $_POST['nat_id'] ?? null;
    $client_age = $_POST['age'] ?? null;
    $guardian_accompany = $_POST['guardian'] ?? null;
    $guardian_name = $_POST['guardianName'] ?? null;
    $guardian_id = $_POST['guardianID'] ?? null;
    $mat_facility = $_POST['facilityname'] ?? '';

    $clinician_name = $_POST['clinician_name'] ?? '';
    $clinician_org = $_POST['clinician_org'] ?? '';
    $clinician_signature = $_POST['clinician_signature'] ?? '';
    $clinician_date_raw = $_POST['clinician_date'] ?? date('d/m/Y');
    $date_obj = DateTime::createFromFormat('d/m/Y', $clinician_date_raw);
    $clinician_date = $date_obj ? $date_obj->format('Y-m-d') : date('Y-m-d');

    $counselor_name = $_POST['counselor_name'] ?? '';
    $counselor_org = $_POST['counselor_org'] ?? '';
    $counselor_signature = $_POST['counselor_signature'] ?? '';
    $counselor_date_raw = $_POST['counselor_date'] ?? date('d/m/Y');
    $date_obj = DateTime::createFromFormat('d/m/Y', $counselor_date_raw);
    $counselor_date = $date_obj ? $date_obj->format('Y-m-d') : date('Y-m-d');

    // Assume client_signature is an input field now
    $client_signature = $_POST['client_signature'] ?? 'Not Signed';

    if (empty($p_id) || empty($mat_id) || empty($client_name)) {
        $error_message = "Missing critical patient information.";
    } else {
        // Generate PDF filename
        $pdf_datetime = date('Ymd_His');
        // File format: mat_id_YYYYMMDD_HHMMSS.pdf
        $pdf_filename = $mat_id . '_' . $pdf_datetime . '.pdf';
        $full_pdf_path = $pdf_save_dir . $pdf_filename;

        // --- 1.1 Insert into Database ---
        $insert_query = "INSERT INTO client_consents (p_id, mat_id, client_name, sex, visit_date, cso, declaration_phone, national_id, client_age, guardian_accompany, guardian_name, guardian_id, mat_facility, clinician_name, clinician_org, clinician_signature, clinician_date, counselor_name, counselor_org, counselor_signature, counselor_date, pdf_filename)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $insert_query);

        // s: string, i: integer (p_id)
        mysqli_stmt_bind_param($stmt, "isssssssssssssssssssss",
            $p_id, $mat_id, $client_name, $sex, $visit_date, $cso, $declaration_phone, $national_id, $client_age, $guardian_accompany, $guardian_name, $guardian_id, $mat_facility, $clinician_name, $clinician_org, $clinician_signature, $clinician_date, $counselor_name, $counselor_org, $counselor_signature, $counselor_date, $pdf_filename
        );

        if (mysqli_stmt_execute($stmt)) {
            $last_id = mysqli_insert_id($conn);
            $success_message = "Consent form submitted successfully! Generating PDF...";

            // --- 1.2 PDF Generation using DOMPDF ---
            try {
                // Set up options
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options);

                // Start output buffering to capture the HTML for the PDF
                ob_start();
                // Include a separate, clean HTML template or dynamically create it
                // For simplicity, we create the HTML dynamically here:

                // IMPORTANT: Use absolute paths for images in DOMPDF HTML
                $logo_path = realpath('../assets/images/Government of Kenya.png');
                $logo_url = 'file:///' . str_replace('\\', '/', $logo_path);

                $pdf_html = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                    <title>Client Consent Form</title>
                    <style>
                        body { font-family: sans-serif; font-size: 10pt; line-height: 1.6; margin: 0; padding: 0; }
                        .form-container { width: 100%; margin: 0 auto; padding: 20px; }
                        .form-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
                        .form-header img { width: 80px; height: 60px; float: left; margin-right: 20px; }
                        .header-center { display: inline-block; }
                        .form-header h2 { color: #2c3e50; font-size: 14pt; margin: 0; }
                        .form-header h4 { color: #6633CC; font-size: 12pt; margin: 5px 0; }
                        .form-header p { font-size: 8pt; float: right; margin: 0; padding-top: 10px; }
                        .section-title { color: blue; font-weight: bold; font-size: 12pt; margin: 15px 0 8px 0; border-bottom: 1px solid #ccc; padding-bottom: 5px;}
                        .data-pair { margin: 5px 0; }
                        .data-pair span { font-weight: bold; }
                        .consent-list { margin-left: 20px; }
                        .consent-list p { margin: 5px 0; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9pt; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .red-text { color: red; font-style: italic; }
                        .blue-text { color: blue; font-weight: bold; }
                        .signature-line { display: inline-block; border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px; }
                    </style>
                </head>
                <body>
                    <div class="form-container">
                        <div class="form-header">
                            <img src="' . $logo_url . '" alt="Government Logo">
                            <div class="header-center">
                                <h2>MEDICALLY ASSISTED THERAPY</h2>
                                <h4>CLIENT CONSENT FORM</h4>
                            </div>
                            <p>FORM 1A VER. APR. 2023</p>
                            <div style="clear: both;"></div>
                        </div>

                        <div class="section-title">Client Information</div>
                        <div class="data-pair"><span>Date:</span> ' . htmlspecialchars($visitDate_raw) . '</div>
                        <div class="data-pair"><span>Name of Client:</span> ' . htmlspecialchars($client_name) . '</div>
                        <div class="data-pair"><span>MAT ID:</span> ' . htmlspecialchars($mat_id) . '</div>
                        <div class="data-pair"><span>Sex:</span> ' . htmlspecialchars($sex) . '</div>
                        <div class="data-pair"><span>CSO:</span> ' . htmlspecialchars($cso) . '</div>

                        <div class="section-title">Client Declaration</div>
                        <p>I <span class="signature-line">' . htmlspecialchars($client_name) . '</span> of telephone number <span class="signature-line">' . htmlspecialchars($declaration_phone) . '</span> and ID number <span class="signature-line">' . htmlspecialchars($national_id) . '</span></p>
                        <p><span class="red-text">(where the client is under the age of 18 years, state the age of the patient)</span> Age: <span class="signature-line">' . htmlspecialchars($client_age) . '</span></p>
                        <p>and accompanied by <span class="signature-line">' . htmlspecialchars($guardian_accompany) . '</span></p>
                        <p>Guardian Name: <span class="signature-line">' . htmlspecialchars($guardian_name) . '</span>, Guardian ID: <span class="signature-line">' . htmlspecialchars($guardian_id) . '</span></p>

                        <div class="section-title">Consent Agreement</div>
                        <p class="blue-text" style="font-size: 11pt;">I do hereby willingly consent to the following:</p>
                        <div class="consent-list">
                            <p>1. That I have been given information at the CSO about the MAT program</p>
                            <p>2. I have been taken through the rules and regulations of in the MAT program</p>
                            <p>3. I understand that participation in the program is voluntary</p>
                            <p>4. I have been informed of the risks and benefits of being in the MAT program</p>
                            <p>5. Although I understand that the treatment is beneficial to me, I have the right to withdraw from treatment</p>
                            <p>7. I agree to keep, and be on time for all my scheduled appointments with the service provider and his/her health care team at the clinic/treatment Centre.</p>
                            <p>8. I understand that the staff at the clinic/treatment Centre will need to confirm my identity every time before issuing my medication.</p>
                            <p>9. I agree to conduct myself in a courteous manner at the clinic/treatment Centre; No violence, verbal abuse, physical assault and repeated unacceptable destructive behavior to staff and or fellow clients.</p>
                            <p>10. I agree not to arrive at the clinic/treatment Centre intoxicated or under the influence of drugs. If I do, the doctor may not see me and I may not be given any medication until my next scheduled appointment.</p>
                            <p>11. I agree not to sell, share or give any of my medication to another person. I understand that such mishandling of my medication is a serious violation of this agreement and would result in my treatment being terminated without recourse for appeal.</p>
                            <p>12. I agree not to deal, steal or conduct any other illegal or disruptive activities in the clinic/treatment Centre –Drug possession/dealing, carrying weapons and property damage within and around the facility.</p>
                            <p>13. I agree to collect my medication personally at my regular clinic/treatment Centre through daily visits and to consume the whole dose under direct observation of dispensing staff.</p>
                            <p>14. I understand that if I miss an appointment and fail to collect my medication on any day I will not be given an extra dose the following day.</p>
                            <p>15. I understand that if I miss three or more consecutive doses of my medication, the prescription will be cancelled and can only be renewed after another full medical check-up. </p>
                            <p>16. I agree that it is my responsibility to take the full dose of medication I receive from the clinic/treatment Centre staff. I agree that any medication that spills/drops while being taken will not be replaced regardless of the reasons for the loss.</p>
                            <p>17. I understand the dangers of taking more than my prescribed dose of methadone. I agree not to obtain similar medications from any other physicians, pharmacies or other sources without informing my primary treatment providers.</p>
                            <p>18. I understand that mixing my methadone/buprenorphine with other substances, especially alcohol, benzodiazepines such as Diazepam, and other drugs of abuse, can be dangerous. I also understand that death can occur among persons mixing methadone/buprenorphine with benzodiazepines.</p>
                            <p>19. I agree to take my medication as the doctor has instructed and not to alter the way I take my medication without first consulting the doctor.</p>
                            <p>20. I understand that methadone/buprenorphine alone is not sufficient treatment for my dependence and I agree to participate in the patient education and relapse prevention program, as provided, to assist me in my treatment.</p>
                            <p>21. I understand that the consent form will be administered after 3 months of induction and when need arise.</p>
                            <p>22. I understand that consenting to the above listed rules will apply to the mobile van. I will also be bound by all MAT clinic regulations.</p>
                        </div>

                        <p style="margin-top: 15px;">I FREELY and VOLUNTARILY agree to undergo MAT at <span class="signature-line">' . htmlspecialchars($mat_facility) . '</span> or any other MAT outlet.</p>

                        <div style="margin-top: 15px;">
                            <span>Client\'s Signature or Left thumb print:</span> <span class="signature-line">' . htmlspecialchars($client_signature) . '</span>
                        </div>

                        <div class="section-title">Treatment Team</div>
                        <table>
                            <tr><th>Designation</th><th>Name</th><th>Organization</th><th>Signature</th><th>Date</th></tr>
                            <tr><td>MAT Clinician</td><td>' . htmlspecialchars($clinician_name) . '</td><td>' . htmlspecialchars($clinician_org) . '</td><td>' . htmlspecialchars($clinician_signature) . '</td><td>' . htmlspecialchars($clinician_date_raw) . '</td></tr>
                            <tr><td>MAT Counselor</td><td>' . htmlspecialchars($counselor_name) . '</td><td>' . htmlspecialchars($counselor_org) . '</td><td>' . htmlspecialchars($counselor_signature) . '</td><td>' . htmlspecialchars($counselor_date_raw) . '</td></tr>
                        </table>
                    </div>
                </body>
                </html>';

                $dompdf->loadHtml($pdf_html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Output the generated PDF to the file system
                file_put_contents($full_pdf_path, $dompdf->output());

            } catch (Exception $e) {
                $error_message .= " PDF Generation Error (DOMPDF): " . $e->getMessage();
            }

            // Redirect after successful submission and PDF creation
            header("Location: ../reports/view_consents.php?status=success&file=" . urlencode($pdf_filename));
            exit();

        } else {
            $error_message = "Database insertion failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}


// --- 2. PRE-POPULATE DATA FOR THE FORM (Rest of the original form display logic) ---

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch patient details if p_id is provided
$client = null;
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];
    $patient_query = "SELECT clientName, nickName, sex, cso, mat_id, reg_date, client_phone, age, peer_edu_name FROM patients WHERE p_id = ?";
    $stmt = mysqli_prepare($conn, $patient_query);
    mysqli_stmt_bind_param($stmt, "i", $p_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($patient_result);
    mysqli_stmt_close($stmt);

    $mat_id_for_fingerprint = $client['mat_id'] ?? '';
} else {
    $mat_id_for_fingerprint = '';
}


// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$lab_office_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $lab_office_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Fetch facility name from facility_settings
$facilityname = '';
$facilityQuery = "SELECT facilityname FROM facility_settings LIMIT 1";
$facilityResult = mysqli_query($conn, $facilityQuery);
if ($facilityResult && mysqli_num_rows($facilityResult) > 0) {
    $facilityRow = mysqli_fetch_assoc($facilityResult);
    $facilityname = $facilityRow['facilityname'];
}

// Fetch clinicians list
$clinicians = [];
$clinicianQuery = "SELECT first_name, last_name FROM tblusers WHERE userrole = 'clinician' OR userrole LIKE '%clinician%'";
$clinicianResult = mysqli_query($conn, $clinicianQuery);
if ($clinicianResult) {
    while ($row = mysqli_fetch_assoc($clinicianResult)) {
        $clinicians[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}

// Fetch counselors list (using original roles)
$counselors = [];
$counselorQuery = "SELECT first_name, last_name FROM tblusers WHERE userrole = 'psychologist' OR userrole LIKE '%psychiatrist%'";
$counselorResult = mysqli_query($conn, $counselorQuery);
if ($counselorResult) {
    while ($row = mysqli_fetch_assoc($counselorResult)) {
        $counselors[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Consent Form</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px; line-height: 1.6; }
        .form-container { width: 85%; max-width: 1200px; margin: 0 auto; padding: 30px; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .form-header { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #2c3e50; }
        .header-center { text-align: center; }
        .form-header h2 { color: #2c3e50; font-size: 24px; margin: 0; }
        .form-header h4 { color: #6633CC; font-size: 18px; margin: 5px 0; }
        .form-header p { color: #7f8c8d; font-size: 14px; text-align: right; margin: 0; }
        .form-group { display: flex; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group label { width: 250px; font-weight: bold; color: #2c3e50; margin-right: 15px; }
        .form-group input, .form-group select, .form-group textarea { width: 350px; padding: 10px; border: 1px solid #dcdcdc; border-radius: 5px; font-size: 14px; transition: border-color 0.3s; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3498db; outline: none; }
        .form-groups { margin: 20px 0; }
        .form-groups p { margin: 12px 0; padding: 8px 0; }
        .form-groups input[type="text"] { padding: 6px 10px; border: 1px solid #dcdcdc; border-radius: 4px; margin: 0 5px; min-width: 150px; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        .signature-table th, .signature-table td { border: 1px solid #dcdcdc; padding: 12px; text-align: left; }
        .signature-table th { background-color: #3498db; color: #fff; font-weight: bold; }
        .signature-table td { background-color: #f9f9f9; }
        .signature-table select, .signature-table input { width: 100%; padding: 8px; border: 1px solid #dcdcdc; border-radius: 4px; box-sizing: border-box; }
        .submit-button { display: block; margin: 30px auto 0; padding: 12px 30px; background-color: #3498db; color: #fff; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s; }
        .submit-button:hover { background-color: #2980b9; }
        .readonly-input { cursor: not-allowed; background-color: #FFCCFF; }
        .required-field::after { content: " *"; color: red; }
        .section-title { color: #2c3e50; font-size: 20px; margin: 25px 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #ecf0f1; }
        .consent-list { margin-left: 40px; }
        .consent-list p { margin: 10px 0; }
        .italic-text { font-style: italic; }
        .red-text { color: red; }
        .blue-text { color: blue; }
        .bold-text { font-weight: bold; }
        @media (max-width: 768px) { /* ... (Your media queries) ... */ }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if ($success_message): ?>
            <div style="padding: 10px; margin-bottom: 15px; border: 1px solid #d4edda; color: #155724; background-color: #d4edda; border-radius: 5px;">
                <?= htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div style="padding: 10px; margin-bottom: 15px; border: 1px solid #f5c6cb; color: #721c24; background-color: #f8d7da; border-radius: 5px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>CLIENT CONSENT FORM</h4>
            </div>
            <p>FORM 1A VER. APR. 2023</p>
        </div>

        <form method="POST">
            <?php if (isset($_GET['p_id'])): ?>
                <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($_GET['p_id']); ?>">
            <?php endif; ?>

            <div class="section-title" style="color: blue; font-weight: bold; font-size: 18px;">Client Information</div>

            <div class="form-group">
                <label for="visitDate" class="required-field">Date (dd/mm/yyyy):</label>
                <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('d/m/Y'); ?>">
            </div>

            <div class="form-group">
                <label for="client_name" class="required-field">Name of Client:</label>
                <input type="text" id="client_name" name="client_name" class="readonly-input" readonly
                        value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="mat_id" class="required-field">MAT ID:</label>
                <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly
                        value="<?php echo $client ? htmlspecialchars($client['mat_id']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="sex" class="required-field">Sex:</label>
                <input type="text" id="sex" name="sex" class="readonly-input" readonly
                        value="<?php echo $client ? htmlspecialchars($client['sex']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="cso" >CSO:</label>
                <input type="text" id="cso" name="cso"
                        value="<?php echo htmlspecialchars($client['cso'] ?? ''); ?>">
            </div>

            <div class="section-title" style="color: blue; font-weight: bold; font-size: 18px;">Client Declaration</div>

            <div class="form-groups">
                <p>I <input type="text" name="declaration_name" class="readonly-input" readonly
                             value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
                    &nbsp;&nbsp; of telephone number&nbsp;
                    <input type="text" name="client_phone" value="<?php echo $client ? htmlspecialchars($client['client_phone']) : ''; ?>">
                </p>

                <p>and ID number&nbsp; <input type="text" name="nat_id"></p>

                <p><span class="red-text italic-text">(where the client is under the age of 18 years, state the age of the patient)</span>
                    <input type="text" name="age" value="<?php echo $client ? htmlspecialchars($client['age']) : ''; ?>">
                </p>

                <p>and accompanied by&nbsp;
                    <input type="text" name="guardian" placeholder="If parent or guardian">
                    <span class="italic-text blue-text">(if accompanied by a guardian, also indicate the name and details of the guardian)</span>
                </p>

                <p>Guardian Name&nbsp;
                    <input type="text" name="guardianName">&nbsp;&nbsp;
                    Guardian ID: <input type="text" name="guardianID">
                </p>
            </div>

            <div class="section-title">Consent Agreement</div>

            <div class="form-groups">
                <p class="blue-text bold-text" style="font-size: 18px;">I do hereby willingly consent to the following:</p>
            </div>

            <div class="consent-list">
                <p>1. That I have been given information at the CSO about the MAT program</p>
                <p>2. I have been taken through the rules and regulations of in the MAT program</p>
                <p>3. I understand that participation in the program is voluntary</p>
                <p>4. I have been informed of the risks and benefits of being in the MAT program</p>
                <p>5. Although I understand that the treatment is beneficial to me, I have the right to withdraw from treatment</p>
                <p>7.	I agree to keep, and be on time for all my scheduled appointments with the service provider and his/her health care team at the clinic/treatment Centre.</p>
                <p>8.	I understand that the staff at the clinic/treatment Centre will need to confirm my identity every time before issuing my medication.</p>
                <p>9.	I agree to conduct myself in a courteous manner at the clinic/treatment Centre; No violence, verbal abuse, physical assault and repeated unacceptable destructive behavior to staff and or fellow clients.</p>
                <p>10.	I agree not to arrive at the clinic/treatment Centre intoxicated or under the influence of drugs. If I do, the doctor may not see me and I may not be given any medication until my next scheduled appointment.</p>
                <p>11.	I agree not to sell, share or give any of my medication to another person. I understand that such mishandling of my medication is a serious violation of this agreement and would result in my treatment being terminated without recourse for appeal.</p>
                <p>12.	I agree not to deal, steal or conduct any other illegal or disruptive activities in the clinic/treatment Centre –Drug possession/dealing, carrying weapons and property damage within and around the facility.</p>
                <p>13.	I agree to collect my medication personally at my regular clinic/treatment Centre through daily visits and to consume the whole dose under direct observation of dispensing staff.</p>
                <p>14.	I understand that if I miss an appointment and fail to collect my medication on any day I will not be given an extra dose the following day.</p>
                <p>15.	I understand that if I miss three or more consecutive doses of my medication, the prescription will be cancelled and can only be renewed after another full medical check-up. </p>
                <p>16.	I agree that it is my responsibility to take the full dose of medication I receive from the clinic/treatment Centre staff. I agree that any medication that spills/drops while being taken will not be replaced regardless of the reasons for the loss.</p>
                <p>17.	I understand the dangers of taking more than my prescribed dose of methadone. I agree not to obtain similar medications from any other physicians, pharmacies or other sources without informing my primary treatment providers.</p>
                <p>19.	I agree to take my medication as the doctor has instructed and not to alter the way I take my medication without first consulting the doctor.</p>
                <p>18.	I understand that mixing my methadone/buprenorphine with other substances, especially alcohol, benzodiazepines such as Diazepam, and other drugs of abuse, can be dangerous. I also understand that death can occur among persons mixing methadone/buprenorphine with benzodiazepines.</p>
                <p>20.	I understand that methadone/buprenorphine alone is not sufficient treatment for my dependence and I agree to participate in the patient education and relapse prevention program, as provided, to assist me in my treatment.</p>
                <p>21.	I understand that the consent form will be administered after 3 months of induction and when need arise.</p>
                <p>22.	I understand that consenting to the above listed rules will apply to the mobile van. I will also be bound by all MAT clinic regulations.</p>

            </div>

            <div class="form-groups">
                <p>I have been given an opportunity to ask any questions that will help me make an informed decision</p>

                <p>I FREELY and VOLUNTARILY agree to undergo MAT at
                    <input type="text" name="facilityname"
                            value="<?php echo htmlspecialchars($facilityname); ?>" required>
                    or any other MAT outlet:
                </p>
            </div>

            <div class="form-group">
                <label for="thumb_print" class="required-field">Client's Signature or Left thumb print:</label>
                <a href="../clinician/fingerprint_capture.php?mat_id=<?php echo htmlspecialchars($mat_id_for_fingerprint); ?>">Capture Fingerprint</a>
                <input type="text" name="client_signature" placeholder="Client signature/initials" required>
            </div>

            <div class="section-title">Treatment Team</div>

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
                                    <option value="<?php echo htmlspecialchars($clinician); ?>">
                                        <?php echo htmlspecialchars($clinician); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="clinician_org" value="<?php echo htmlspecialchars($facilityname); ?>" required></td>
                        <td><input type="text" name="clinician_signature" placeholder="Clinician's signature" required></td>
                        <td><input type="text" name="clinician_date" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>" required></td>
                    </tr>
                    <tr>
                        <td>MAT Counselor</td>
                        <td>
                            <select name="counselor_name" required>
                                <option value="">Select Counselor</option>
                                <?php foreach ($counselors as $counselor): ?>
                                    <option value="<?php echo htmlspecialchars($counselor); ?>">
                                        <?php echo htmlspecialchars($counselor); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="counselor_org" value="<?php echo htmlspecialchars($facilityname); ?>" required></td>
                        <td><input type="text" name="counselor_signature" placeholder="Counselor's signature" required></td>
                        <td><input type="text" name="counselor_date" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>" required></td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="submit-button">Submit Consent Form & Save PDF</button>
        </form>
    </div>

    <script>
        // Auto-fill current date for signature dates if empty
        document.addEventListener('DOMContentLoaded', function() {
            const currentDate = '<?php echo date("d/m/Y"); ?>';
            const dateFields = document.querySelectorAll('input[name="clinician_date"], input[name="counselor_date"]');
            dateFields.forEach(field => {
                if (!field.value) {
                    field.value = currentDate;
                }
            });
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    mysqli_close($conn);
}
?>