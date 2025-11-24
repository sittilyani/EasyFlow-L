<?php
session_start();
require_once '../includes/config.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->begin_transaction();

        // Get form data
        $p_id = $_POST['p_id'];
        $facilityname = $_POST['facilityname'];
        $mflcode = $_POST['mflcode'];
        $county = $_POST['county'];
        $sub_county = $_POST['sub_county'];
        $clientName = $_POST['clientName'];
        $mat_id = $_POST['mat_id'];
        $sex = $_POST['sex'];
        $dob = $_POST['dob'];
        $client_phone = $_POST['client_phone'];
        $reg_facility = $_POST['reg_facility'];
        $reg_date = $_POST['reg_date'];
        $referral_date = $_POST['referral_date'];
        $type_of_movement = $_POST['type_of_movement'];
        $other_specify = isset($_POST['other_specify']) ? $_POST['other_specify'] : null;
        $from_site = $_POST['from_site'];
        $to_site = $_POST['to_site'];
        $reason_transfer = $_POST['reason_transfer'];
        $clinical_history = $_POST['clinical_history'];
        $psychosocial = $_POST['psychosocial'];
        $lab_investigations = isset($_POST['lab_investigations']) ? $_POST['lab_investigations'] : null;
        $vaccinations = isset($_POST['vaccinations']) ? $_POST['vaccinations'] : null;
        $diagnosis = $_POST['diagnosis'];
        $current_dose = $_POST['current_dose'];
        $date_last_administered = $_POST['date_last_administered'];
        $other_medications = isset($_POST['other_medications']) ? $_POST['other_medications'] : null;
        $clinician_name = $_POST['clinician_name'];
        $clinician_org = $_POST['clinician_org'];
        $clinician_signature = $_POST['clinician_signature'];
        $clinician_date = $_POST['clinician_date'];
        $counselor_name = $_POST['counselor_name'];
        $counselor_org = $_POST['counselor_org'];
        $counselor_signature = $_POST['counselor_signature'];
        $counselor_date = $_POST['counselor_date'];

        // Generate PDF filename: mat_id_clinician_date.pdf
        $pdf_filename = $mat_id . '_' . $clinician_date . '.pdf';

        // Prepare JSON data for future use
        $json_data = json_encode([
            'p_id' => $p_id,
            'facilityname' => $facilityname,
            'mflcode' => $mflcode,
            'county' => $county,
            'sub_county' => $sub_county,
            'clientName' => $clientName,
            'mat_id' => $mat_id,
            'sex' => $sex,
            'dob' => $dob,
            'client_phone' => $client_phone,
            'reg_facility' => $reg_facility,
            'reg_date' => $reg_date,
            'referral_date' => $referral_date,
            'type_of_movement' => $type_of_movement,
            'other_specify' => $other_specify,
            'from_site' => $from_site,
            'to_site' => $to_site,
            'reason_transfer' => $reason_transfer,
            'clinical_history' => $clinical_history,
            'psychosocial' => $psychosocial,
            'lab_investigations' => $lab_investigations,
            'vaccinations' => $vaccinations,
            'diagnosis' => $diagnosis,
            'current_dose' => $current_dose,
            'date_last_administered' => $date_last_administered,
            'other_medications' => $other_medications,
            'clinician_name' => $clinician_name,
            'clinician_org' => $clinician_org,
            'clinician_signature' => $clinician_signature,
            'clinician_date' => $clinician_date,
            'counselor_name' => $counselor_name,
            'counselor_org' => $counselor_org,
            'counselor_signature' => $counselor_signature,
            'counselor_date' => $counselor_date
        ]);

        // Insert into transfer_forms table
        $insertQuery = "INSERT INTO transfer_forms
            (p_id, facilityname, mflcode, county, sub_county, clientName, mat_id, sex, dob,
             client_phone, reg_facility, reg_date, referral_date, type_of_movement, other_specify,
             from_site, to_site, reason_transfer, clinical_history, psychosocial, lab_investigations,
             vaccinations, diagnosis, current_dose, date_last_administered, other_medications,
             clinician_name, clinician_org, clinician_signature, clinician_date, counselor_name,
             counselor_org, counselor_signature, counselor_date, pdf_filename, json_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insertQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('isssssssssssssssssssssssssssssssssss',
            $p_id, $facilityname, $mflcode, $county, $sub_county, $clientName, $mat_id, $sex, $dob,
            $client_phone, $reg_facility, $reg_date, $referral_date, $type_of_movement, $other_specify,
            $from_site, $to_site, $reason_transfer, $clinical_history, $psychosocial, $lab_investigations,
            $vaccinations, $diagnosis, $current_dose, $date_last_administered, $other_medications,
            $clinician_name, $clinician_org, $clinician_signature, $clinician_date, $counselor_name,
            $counselor_org, $counselor_signature, $counselor_date, $pdf_filename, $json_data
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting transfer form data: " . $stmt->error);
        }
        $stmt->close();

        // Update patient status to 'Transout'
        $updateQuery = "UPDATE patients SET current_status = 'Transout' WHERE mat_id = ?";
        $stmt = $conn->prepare($updateQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed for update: " . $conn->error);
        }

        $stmt->bind_param('s', $mat_id);

        if (!$stmt->execute()) {
            throw new Exception("Error updating patient status: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaction before generating PDF
        $conn->commit();

        // Generate PDF
        $pdfGenerated = generateTransferPDF($_POST, $pdf_filename, $conn);

        if ($pdfGenerated) {
            $successMessage = "Transfer form submitted successfully, patient status updated to 'Transout', and PDF saved.";
            header("Location: patient_transfer_form.php?p_id=" . $p_id . "&success=" . urlencode($successMessage));
            exit();
        } else {
            throw new Exception("PDF generation failed");
        }

    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Error: " . $e->getMessage();
        error_log("Transfer form error: " . $e->getMessage());
        header("Location: patient_transfer_form.php?p_id=" . $p_id . "&error=" . urlencode($errorMessage));
        exit();
    }
}

function generateTransferPDF($data, $filename, $conn) {
    try {
        // Create the directory if it doesn't exist
        $pdfDir = '../transferforms/';
        if (!file_exists($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }

        $pdfPath = $pdfDir . $filename;

        // Fetch photo path
        $photoPath = '';
        $photoBase64 = '';

        $sql_photo = "
            SELECT photos.image
            FROM photos
            INNER JOIN patients ON photos.mat_id = patients.mat_id
            WHERE patients.p_id = ?
            ORDER BY photos.visitDate DESC
            LIMIT 1
        ";
        $stmt_photo = $conn->prepare($sql_photo);
        if ($stmt_photo) {
            $stmt_photo->bind_param('i', $data['p_id']);
            $stmt_photo->execute();
            $result_photo = $stmt_photo->get_result();
            $photo = $result_photo->fetch_assoc();
            $stmt_photo->close();

            if ($photo && !empty($photo['image'])) {
                $photoPath = '../clientPhotos/' . $photo['image'];
                if (file_exists($photoPath)) {
                    $imageData = file_get_contents($photoPath);
                    $photoBase64 = 'data:image/jpeg;base64,' . base64_encode($imageData);
                }
            }
        }

        // Create HTML content for PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #333; }
                .container { width: 100%; padding: 10px; }
                .header { text-align: center; margin-bottom: 15px; position: relative; }
                .header h2 { font-size: 14px; margin: 3px 0; font-weight: bold; }
                .header h3 { font-size: 12px; margin: 3px 0; font-weight: bold; color: #6633CC; }
                .client-photo { position: absolute; top: 0; right: 10px; width: 80px; height: 100px; border: 2px solid #3498db; }
                .section-title { background-color: #3498db; color: white; padding: 6px 10px; margin: 10px 0 5px; font-size: 11px; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                table td { border: 1px solid #333; padding: 4px; font-size: 10px; vertical-align: top; }
                .label { font-weight: bold; width: 35%; background-color: #f5f5f5; }
                .value { width: 65%; }
                .signature-table th { background-color: #3498db; color: white; font-weight: bold; border: 1px solid #333; padding: 4px; font-size: 10px; }
                .signature-table td { border: 1px solid #333; padding: 4px; font-size: 9px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Republic of Kenya</h2>
                    <h2>Ministry of Health</h2>
                    <h2>FORM 3G VER. APRIL 2023</h2>
                    <h3>MEDICALLY ASSISTED THERAPY TRANSFER/TRANSIT FORM</h3>
                    ' . ($photoBase64 ? '<img src="' . $photoBase64 . '" class="client-photo" />' : '') . '
                </div>

                <div class="section-title">CLIENT\'S DETAILS</div>
                <table>
                    <tr><td class="label">Facility Name</td><td class="value">' . htmlspecialchars($data['facilityname']) . '</td></tr>
                    <tr><td class="label">MFL Code</td><td class="value">' . htmlspecialchars($data['mflcode']) . '</td></tr>
                    <tr><td class="label">County</td><td class="value">' . htmlspecialchars($data['county']) . '</td></tr>
                    <tr><td class="label">Sub County</td><td class="value">' . htmlspecialchars($data['sub_county']) . '</td></tr>
                    <tr><td class="label">Client Name</td><td class="value">' . htmlspecialchars($data['clientName']) . '</td></tr>
                    <tr><td class="label">MAT ID</td><td class="value">' . htmlspecialchars($data['mat_id']) . '</td></tr>
                    <tr><td class="label">Sex</td><td class="value">' . htmlspecialchars($data['sex']) . '</td></tr>
                    <tr><td class="label">Date of Birth</td><td class="value">' . htmlspecialchars($data['dob']) . '</td></tr>
                    <tr><td class="label">Client Phone</td><td class="value">' . htmlspecialchars($data['client_phone']) . '</td></tr>
                    <tr><td class="label">MAT Clinic Enrolled In</td><td class="value">' . htmlspecialchars($data['reg_facility']) . '</td></tr>
                    <tr><td class="label">MAT Enrollment Date</td><td class="value">' . htmlspecialchars($data['reg_date']) . '</td></tr>
                    <tr><td class="label">Referral Date</td><td class="value">' . htmlspecialchars($data['referral_date']) . '</td></tr>
                    <tr><td class="label">Type of Movement</td><td class="value">' . htmlspecialchars($data['type_of_movement']) . '</td></tr>
                    <tr><td class="label">From (Referral Site)</td><td class="value">' . htmlspecialchars($data['from_site']) . '</td></tr>
                    <tr><td class="label">To (Dispensing Site)</td><td class="value">' . htmlspecialchars($data['to_site']) . '</td></tr>
                </table>

                <div class="section-title">TRANSFER NOTES</div>
                <table>
                    <tr><td class="label">Reason for Transfer/Transit</td><td class="value">' . nl2br(htmlspecialchars($data['reason_transfer'])) . '</td></tr>
                    <tr><td class="label">Clinical & Drug Use History</td><td class="value">' . nl2br(htmlspecialchars($data['clinical_history'])) . '</td></tr>
                    <tr><td class="label">Psychosocial Background</td><td class="value">' . nl2br(htmlspecialchars($data['psychosocial'])) . '</td></tr>
                    <tr><td class="label">Laboratory Investigations</td><td class="value">' . nl2br(htmlspecialchars($data['lab_investigations'] ?? 'N/A')) . '</td></tr>
                    <tr><td class="label">Vaccinations</td><td class="value">' . nl2br(htmlspecialchars($data['vaccinations'] ?? 'N/A')) . '</td></tr>
                    <tr><td class="label">Diagnosis</td><td class="value">' . nl2br(htmlspecialchars($data['diagnosis'])) . '</td></tr>
                    <tr><td class="label">Current Dose</td><td class="value">' . htmlspecialchars($data['current_dose']) . '</td></tr>
                    <tr><td class="label">Date Last Administered</td><td class="value">' . htmlspecialchars($data['date_last_administered']) . '</td></tr>
                    <tr><td class="label">Other Medications</td><td class="value">' . htmlspecialchars($data['other_medications'] ?? 'N/A') . '</td></tr>
                </table>

                <div class="section-title">TREATMENT TEAM</div>
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
                        <td>' . htmlspecialchars($data['clinician_name']) . '</td>
                        <td>' . htmlspecialchars($data['clinician_org']) . '</td>
                        <td>' . htmlspecialchars($data['clinician_signature']) . '</td>
                        <td>' . htmlspecialchars($data['clinician_date']) . '</td>
                    </tr>
                    <tr>
                        <td>MAT Counselor</td>
                        <td>' . htmlspecialchars($data['counselor_name']) . '</td>
                        <td>' . htmlspecialchars($data['counselor_org']) . '</td>
                        <td>' . htmlspecialchars($data['counselor_signature']) . '</td>
                        <td>' . htmlspecialchars($data['counselor_date']) . '</td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
        ';

        // Instantiate and use the dompdf class
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to file
        $output = $dompdf->output();
        file_put_contents($pdfPath, $output);

        return true;

    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        return false;
    }
}
?>