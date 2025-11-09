<?php
// Start session
session_start();

// Include database connection
include('../includes/config.php');

// Fetch logged-in user's full name
$loggedInUserId = $_SESSION['user_id'];
$sqlFullName = "SELECT CONCAT(first_name, ' ', last_name) AS fullName, mobile FROM tblusers WHERE user_id = ?";
$stmtFullName = $conn->prepare($sqlFullName);
$stmtFullName->bind_param("i", $loggedInUserId);
$stmtFullName->execute();
$resultFullName = $stmtFullName->get_result();
$userFullName = $resultFullName->fetch_assoc();

// Fetch logged-in user's initials
$sqlInitials = "SELECT CONCAT(LEFT(first_name, 1), '.', LEFT(last_name, 1)) AS initials FROM tblusers WHERE user_id = ?";
$stmtInitials = $conn->prepare($sqlInitials);
$stmtInitials->bind_param("i", $loggedInUserId);
$stmtInitials->execute();
$resultInitials = $stmtInitials->get_result();
$userInitials = $resultInitials->fetch_assoc();

// Get user's details
$pharmicName = $userFullName['fullName'] ?? '';
$pharmicMobile = $userFullName['mobile'] ?? '';
$pharmicSign = $userInitials['initials'] ?? '';
$pharmicDate = date('Y-m-d');

// Calculate previous month's start and end dates
$prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
$prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));

// Fetch facility settings
$facilityName = "N/A";
$countyName = "N/A";
$facilityIncharge = "N/A";
$facilityPhone = "N/A";

$queryFacilitySettings = "SELECT facilityname, countyname, facilityincharge, facilityphone FROM facility_settings LIMIT 1";
$resultFacilitySettings = $conn->query($queryFacilitySettings);

if ($resultFacilitySettings && $resultFacilitySettings->num_rows > 0) {
    $rowFacilitySettings = $resultFacilitySettings->fetch_assoc();
    $facilityName = htmlspecialchars($rowFacilitySettings['facilityname']);
    $countyName = htmlspecialchars($rowFacilitySettings['countyname']);
    $facilityIncharge = htmlspecialchars($rowFacilitySettings['facilityincharge']);
    $facilityPhone = htmlspecialchars($rowFacilitySettings['facilityphone']);
}

$loggedInUserFullName = isset($_SESSION['loggedin_user_fullname']) ? htmlspecialchars($_SESSION['loggedin_user_fullname']) : 'N/A';
$loggedInUserPhone = isset($_SESSION['loggedin_user_phone']) ? htmlspecialchars($_SESSION['loggedin_user_phone']) : 'N/A';
$loggedInUserSignature = isset($_SESSION['loggedin_user_signature']) ? htmlspecialchars($_SESSION['loggedin_user_signature']) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>FormP8</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>
        @page {
            margin: 0.3in 0.7in 0.75in 0.7in;
        }

        body {
            margin: 0.3in 0.7in 0.75in 0.7in;
            font-family: "Times New Roman", Times, serif;
        }

        .sheet0 {
            width: 100%;
            border-collapse: collapse;
        }

        .sheet0 td {
            padding: 5px;
            vertical-align: middle;
        }

        /* Column widths */
        .col0 { width: 20px; }

        /* Header styles */
        .header-row td {
            padding: 10px 5px;
        }

        .logo-cell {
            text-align: center;
            width: 120px;
        }

        .title-cell {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            vertical-align: middle;
        }

        .form-code {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            vertical-align: middle;
        }

        /* Facility info styles */
        .info-row td {
            padding: 8px 5px;
        }

        .info-label {
            font-weight: bold;
            width: 100px;
        }

        .info-value {
            background-color: #f8f9fa;
            padding: 5px;
        }

        /* Table header styles */
        .table-header {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
        }

        .sub-header {
            background-color: #f1f3f5;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
            padding: 5px;
        }

        /* Data cell styles */
        .data-row td {
            border: 1px solid #dee2e6;
            padding: 8px 5px;
        }

        .row-label {
            background-color: #f8f9fa;
            padding-left: 10px;
        }

        .data-cell {
            text-align: center;
            background-color: #fff;
        }

        .total-cell {
            text-align: center;
            background-color: #e7f3ff;
            font-weight: bold;
        }

        /* Input styles */
        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: #d6eaf8;
        }

        textarea {
            width: 100%;
            height: 140px;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: #d6eaf8;
            font-family: "Times New Roman", Times, serif;
            resize: vertical;
        }

        input[readonly] {
            background-color: #e9ecef;
        }

        /* Signature section styles */
        .signature-section {
            margin-top: 20px;
        }

        .signature-row td {
            padding: 10px 5px;
            border: 1px solid #dee2e6;
        }

        .signature-label {
            background-color: #f8f9fa;
            font-weight: bold;
            vertical-align: middle;
        }

        .signature-field {
            text-align: center;
            padding: 5px;
        }

        .signature-field input {
            width: 200px;
            margin: 0 auto;
        }

        .field-label {
            text-align: center;
            background-color: #f1f3f5;
            font-size: 12px;
            padding: 5px;
        }

        /* Button styles */
        #print-pdf {
            margin: 20px 0;
            padding: 10px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        #print-pdf:hover {
            background-color: #0056b3;
        }

        /* Print styles */
        @media print {
            #print-pdf {
                display: none;
            }

            body {
                margin: 0;
            }
        }

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .signature-field input {
                width: 150px;
            }
        }
    </style>
</head>

<body>
    <table class="sheet0">
        <colgroup>
            <col class="col0">
            <col span="11">
        </colgroup>
        <tbody>
            <form action="process_FormP8.php" method="POST">
                <!-- Header Row -->
                <tr class="header-row">
                    <td class="col0">&nbsp;</td>
                    <td class="logo-cell">
                        <img src="../assets/images/Government of Kenya.png" width="115" height="109" alt="Kenya Coat of Arms">
                    </td>
                    <td class="title-cell" colspan="9">
                        MEDICALLY ASSISTED THERAPY (MAT) PATIENTS SUMMARY REPORT
                    </td>
                    <td class="form-code">FORM P8</td>
                </tr>

                <!-- Facility Information -->
                <tr class="info-row">
                    <td class="col0">&nbsp;</td>
                    <td class="info-label" colspan="3">Facility:</td>
                    <td class="info-value" colspan="4">
                        <?php echo isset($_SESSION['current_facility_name']) ? htmlspecialchars($_SESSION['current_facility_name']) : "No Facility Set"; ?>
                    </td>
                    <td class="info-label" colspan="3"><b>County:</b></td>
                    <td class="info-value" colspan="1">
                        <?php echo isset($_SESSION['current_county']) ? htmlspecialchars($_SESSION['current_county']) : "No County Set"; ?>
                    </td>
                </tr>

                <!-- Date Range -->
                <tr class="info-row">
                    <td class="col0">&nbsp;</td>
                    <td class="info-label" colspan="3"><b>Beginning Date:</b></td>
                    <td colspan="3">
                        <input type="date" name="beginDate" value="<?php echo $prevMonthStart; ?>" required>
                    </td>
                    <td class="info-label" colspan="3"><b>Ending Date:</b></td>
                    <td colspan="2">
                        <input type="date" name="endDate" value="<?php echo $prevMonthEnd; ?>" required>
                    </td>
                </tr>

                <!-- Main Table Header -->
                <tr>
                    <td class="col0">&nbsp;</td>
                    <td class="table-header" colspan="3" rowspan="2"></td>
                    <td class="table-header" colspan="4">Methadone</td>
                    <td class="table-header" colspan="4">Buprenorphine</td>
                </tr>
                <tr>
                    <td class="col0">&nbsp;</td>
                    <td class="sub-header">Male</td>
                    <td class="sub-header">Female</td>
                    <td class="sub-header">Others</td>
                    <td class="sub-header">Total</td>
                    <td class="sub-header">Male</td>
                    <td class="sub-header">Female</td>
                    <td class="sub-header">Others</td>
                    <td class="sub-header">Total</td>
                </tr>

                <!-- Data Rows -->
                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Number of clients inducted in the current reporting period</td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyinductedMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyinductedMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyinductedMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/monthlytotalinductedCount.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyinductedMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyinductedMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyinductedMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/monthlytotalinductedCount.php'; ?></td>
                </tr>

                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Total number of active clients</td>
                    <td class="data-cell"><?php include '../countsFormP8/activeMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/activeMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/activeMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/totalactMetCount.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/activeMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/activeMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/activeMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/totalactMetCount.php'; ?></td>
                </tr>

                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Number of clients on transit</td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlytransitMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlytransitMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlytransitMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/monthlytotaltransitCount.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlytransitMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlytransitMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlytransitMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/monthlytotaltransitCount.php'; ?></td>
                </tr>

                <!-- Dropout Section -->
                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" rowspan="2">Number of clients drop out in the current reporting period</td>
                    <td colspan="2">LTFU</td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyltfuMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyltfuMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyltfuMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/monthlytotalltfuCount.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyltfuMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyltfuMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyltfuMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/monthlytotalltfuCount.php'; ?></td>
                </tr>
                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td colspan="2">DECEASED</td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlydeadMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlydeadMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlydeadMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/monthlytotaldeadCount.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlydeadMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlydeadMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlydeadMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/monthlytotaldeadCount.php'; ?></td>
                </tr>

                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Clients treated on overdose</td>
                    <td class="data-cell"><?php include '../countsFormP8/OverDoseMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/OverDoseFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/OverDoseOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/OverDoseTotal.php'; ?></td>
                    <td class="data-cell">-</td>
                    <td class="data-cell">-</td>
                    <td class="data-cell">-</td>
                    <td class="total-cell">-</td>
                </tr>

                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Number of clients missing more than 5 consecutive doses</td>
                    <td class="data-cell"><?php include '../countsFormP8/missedMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/missedFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/missedOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/missedTotal.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/missedMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/missedFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/missedOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/missedTotal.php'; ?></td>
                </tr>

                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Number of clients Weaned off MAT</td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyweanedMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyweanedMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/monthlyweanedMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/monthlytotalweanedCount.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyweanedMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyweanedMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/monthlyweanedMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/monthlytotalweanedCount.php'; ?></td>
                </tr>

                <tr class="data-row">
                    <td class="col0">&nbsp;</td>
                    <td class="row-label" colspan="3">Average daily doses</td>
                    <td class="data-cell"><?php include '../countsFormP8/AvgDoseMetMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/AvgDoseMetFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/AvgDoseMetOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/AvgDoseMetTotal.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/AvgDoseBupMale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/AvgDoseBupFemale.php'; ?></td>
                    <td class="data-cell"><?php include '../countsFormP8/countsBupren/AvgDoseBupOther.php'; ?></td>
                    <td class="total-cell"><?php include '../countsFormP8/countsBupren/AvgDoseBupTotal.php'; ?></td>
                </tr>

                <!-- Comments Section -->
                <tr>
                    <td class="col0">&nbsp;</td>
                    <td colspan="11" style="padding: 20px 0;">&nbsp;</td>
                </tr>

                <tr>
                    <td class="col0">&nbsp;</td>
                    <td colspan="11">
                        <textarea name="comments" placeholder="Enter any comments here!"></textarea>
                    </td>
                </tr>

                <!-- Signature Section -->
                <tr class="signature-row">
                    <td class="col0">&nbsp;</td>
                    <td class="signature-label" rowspan="2">Report submitted by:</td>
                    <td class="signature-field" colspan="3">
                        <input type="text" name="pharmicName" value="<?php echo htmlspecialchars($pharmicName); ?>" readonly>
                    </td>
                    <td class="signature-field" colspan="2">
                        <input type="text" name="pharmSign" value="<?php echo htmlspecialchars($pharmicSign); ?>" readonly>
                    </td>
                    <td class="signature-field" colspan="3">
                        <input type="text" name="pharmicMobile" value="<?php echo htmlspecialchars($pharmicMobile); ?>" readonly>
                    </td>
                    <td class="signature-field" colspan="2">
                        <input type="date" name="pharmicDate" value="<?php echo htmlspecialchars($pharmicDate); ?>" readonly>
                    </td>
                </tr>
                <tr class="signature-row">
                    <td class="col0">&nbsp;</td>
                    <td class="field-label" colspan="3">MAT Pharmacist in charge</td>
                    <td class="field-label" colspan="2">Signature</td>
                    <td class="field-label" colspan="3">Mobile Phone</td>
                    <td class="field-label" colspan="2">Date</td>
                </tr>

                <tr class="signature-row">
                    <td class="col0">&nbsp;</td>
                    <td class="signature-label" rowspan="2">Report reviewed by:</td>
                    <td class="signature-field" colspan="3">
                        <input type="text" name="reviewerName" value="<?php echo $facilityIncharge; ?>">
                    </td>
                    <td class="signature-field" colspan="2">
                        <input type="text" name="reviewerSign" placeholder="Enter Initials">
                    </td>
                    <td class="signature-field" colspan="3">
                        <input type="text" name="reviewerMobile" value="<?php echo $facilityPhone; ?>">
                    </td>
                    <td class="signature-field" colspan="2">
                        <input type="date" name="reviewerDate" value="<?php echo date('Y-m-d'); ?>">
                    </td>
                </tr>
                <tr class="signature-row">
                    <td class="col0">&nbsp;</td>
                    <td class="field-label" colspan="3">Pharmacist in charge</td>
                    <td class="field-label" colspan="2">Signature</td>
                    <td class="field-label" colspan="3">Mobile Phone</td>
                    <td class="field-label" colspan="2">Date</td>
                </tr>
            </form>
        </tbody>
    </table>

    <button id="print-pdf" onclick="window.print()">Print PDF</button>
</body>
</html>