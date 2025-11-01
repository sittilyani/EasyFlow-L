<?php
// Require dompdf library
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include '../includes/config.php';

// Calculate default dates for the previous month
$defaultEndDate = date('Y-m-t', strtotime('last month')); // Last day of previous month
$defaultStartDate = date('Y-m-01', strtotime('last month')); // First day of previous month

// Get selected dates from form submission or use defaults
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Validate dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));


// For now, using placeholders with 0
$ever_inducted_male = 0;
$ever_inducted_female = 0;
$ever_inducted_total = $ever_inducted_male + $ever_inducted_female;
$weaned_off_male = 0;
$weaned_off_female = 0;
$weaned_off_total = $weaned_off_male + $weaned_off_female;

// Handle exports
$export = isset($_GET['export']) ? $_GET['export'] : '';
session_start();

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

// Fetch clinicians and counselors from tblusers
$clinician_query = "SELECT full_name FROM tblusers WHERE userrole IN ('clinician', 'counselor', 'admin', 'super admin')";
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
    $facilityIncharge = htmlspecialchars($rowFacilitySettings['facilityincharge']);
    $facilityPhone = htmlspecialchars($rowFacilitySettings['facilityphone']);
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MOH 731 Plus -6 SUMMARY REPORTING TOOL FOR Medically Assisted Therapy (MAT)</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; }
        .form-group-1 {padding: 20px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;}
        .form-group-1 > div:last-child {grid-column: 1 / -1; display: flex; align-items: center; gap: 15px;}
        .form-group-1 > div:last-child label {font-weight: bold; color: #2c3e50; margin: 0;}
        .form-group-1 > div:last-child input[type="date"] {padding: 10px; border: 1px solid #dcdcdc; width: 45%; border-radius: 5px; font-size: 14px;}
        .form-group {display: flex; flex-direction: column;}
        .form-group label {margin-bottom: 8px; font-weight: bold; color: #2c3e50;}
        .signature-table { width: 100%; border-collapse: collapse; margin-left: auto; margin-right: auto; margin-top: 20px; font-size: 12px; }
        .signature-table th, .signature-table td { border: 1px solid #dcdcdc; padding: 8px; text-align: left; }
        .signature-table th { background-color: yellow; color: #000000; font-weight: bold; }
        .signature-table td { background-color: #f9f9f9; }
        .signature-table input, .signature-table select { width: 100%; padding: 5px; border: 1px solid #dcdcdc; border-radius: 3px; font-size: 10px; }
        .form-group input {padding: 10px; border: 1px solid #dcdcdc; border-radius: 5px; font-size: 14px;}
        .readonly-input, .read-only {cursor: not-allowed; background: #FFF0FF;}
        .required-field::after {content: " *"; color: red;}
        .logo { width: 200px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 5px; }
        .section-header { background-color: yellow; font-weight: bold; }
        .form-group { margin-bottom: 10px; }
        @media print {
            @page { size: A3 portrait; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Republic of Kenya</h2>
        <img src="../assets/images/Government of Kenya.png" width="204" height="154" alt="">
        <h2>Ministry of Health</h2>
        <h2>MOH 731 Plus -6</h2>
        <h3>SUMMARY REPORTING TOOL FOR Medically Assisted Therapy (MAT)</h3>
    </div>
    <form method="GET" action="">
        <div class="form-group">
            <div class="form-group-1">
                <div class="form-group">
                    <label for="facilityname" class="required-field">Facility Name:</label>
                    <input type="text" name="facilityname" class="readonly-input" readonly value="<?php echo $facilityName; ?>">
                </div>
                <div class="form-group">
                    <label for="mflcode" class="required-field">MFL Code:</label>
                    <input type="text" name="mflcode" value="<?php echo $mflCode; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="county">County:</label>
                    <input type="text" name="county" class="readonly-input" readonly value="<?php echo $countyName; ?>">
                </div>
                <div class="form-group">
                    <label for="sub_county">Sub County:</label>
                    <input type="text" name="sub_county" class="read-only" readonly value="<?php echo $subcountyName; ?>">
                </div>
                <div>
                    <label for="start_date">Start date:</label>
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                    <label for="end_date">End date:</label>
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                    <button type="submit" style="background: blue; color: white; width: 100px; height: 40px; border: none; border-radius: 5px;">Update Dates</button>
                </div>
            </div>
        </div>
    </form>
    <table>
        <tr class="section-header"><td colspan="4">1.0 EVER INDUCTED</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Ever inducted on MAT</td><td><?php include "../count_moh_731/ever_inducted_male.php"; ?></td><td><?php include "../count_moh_731/ever_inducted_female.php"; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/ever_inducted_all.php'; ?></td></tr>
        <tr><td>Total Number of MAT Clients Ever Weaned off MAT</td><td><?php include "../count_moh_731/ever_weaned_male.php"; ?></td><td><?php include "../count_moh_731/ever_weaned_female.php"; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/ever_weaned_all.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">1.1 MAT INDUCTION WITHIN THE REPORTING PERIOD</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Newly inducted on MAT in the Reporting Period 15 - 19 Years</td><td><?php include '../count_moh_731/newly_inducted_male_1519.php'; ?></td><td><?php include '../count_moh_731/newly_inducted_female_1519.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/newly_inducted_all_1519.php'; ?></td></tr>
        <tr><td>Number Newly inducted on MAT in the Reporting Period 20 - 24 Years</td><td><?php include '../count_moh_731/newly_inducted_male_2024.php'; ?></td><td><?php include '../count_moh_731/newly_inducted_female_2024.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/newly_inducted_all_2024.php'; ?></td></tr>
        <tr><td>Number Newly inducted on MAT in the Reporting Period 25 - 29 Years</td><td><?php include '../count_moh_731/newly_inducted_male_2529.php'; ?></td><td><?php include '../count_moh_731/newly_inducted_female_2529.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/newly_inducted_all_2529.php'; ?></td></tr>
        <tr><td>Number Newly inducted on MAT in the Reporting Period 30+ Years</td><td><?php include '../count_moh_731/newly_inducted_male_30plus.php'; ?></td><td><?php include '../count_moh_731/newly_inducted_female_30plus.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/newly_inducted_all_30plus.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">1.2 Currently on MAT Methadone</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Currently on Methadone 15 - 19 Years</td><td><?php include '../count_moh_731/currently_on_methadone_male_1519.php'; ?></td><td><?php include '../count_moh_731/currently_on_methadone_female_1519.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_methadone_all_1519.php'; ?></td></tr>
        <tr><td>Number Currently on Methadone 20 - 24 Years</td><td><?php include '../count_moh_731/currently_on_methadone_male_2024.php'; ?></td><td><?php include '../count_moh_731/currently_on_methadone_female_2024.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_methadone_all_2024.php'; ?></td></tr>
        <tr><td>Number Currently on Methadone 25 - 29 Years</td><td><?php include '../count_moh_731/currently_on_methadone_male_2529.php'; ?></td><td><?php include '../count_moh_731/currently_on_methadone_female_2529.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_methadone_all_2529.php'; ?></td></tr>
        <tr><td>Number Currently on Methadone 30+ Years</td><td><?php include '../count_moh_731/currently_on_methadone_male_30plus.php'; ?></td><td><?php include '../count_moh_731/currently_on_methadone_female_30plus.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_methadone_all_30plus.php'; ?></td></tr>
        <tr><td>Total Number of clients on Transit who received Methadone in the Reporting Month</td><td><?php include '../count_moh_731/transit_on_methadone_male.php'; ?></td><td><?php include '../count_moh_731/transit_on_methadone_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/transit_on_methadone_all.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">1.3 Currently on MAT(Buprenorphine)</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Currently on Buprenorphine 15 - 19 Years</td><td><?php include '../count_moh_731/currently_on_bup_male_1519.php'; ?></td><td><?php include '../count_moh_731/currently_on_bup_female_1519.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_bup_all_1519.php'; ?></td></tr>
        <tr><td>Number Currently on Buprenorphine 20 - 24 Years</td><td><?php include '../count_moh_731/currently_on_bup_male_2024.php'; ?></td><td><?php include '../count_moh_731/currently_on_bup_female_2024.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_bup_all_2024.php'; ?></td></tr>
        <tr><td>Number Currently on Buprenorphine 25 - 29 Years</td><td><?php include '../count_moh_731/currently_on_bup_male_2529.php'; ?></td><td><?php include '../count_moh_731/currently_on_bup_female_2529.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_bup_all_2529.php'; ?></td></tr>
        <tr><td>Number Currently on Buprenorphine 30+ Years</td><td><?php include '../count_moh_731/currently_on_bup_male_30plus.php'; ?></td><td><?php include '../count_moh_731/currently_on_bup_female_30plus.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/currently_on_bup_all_30plus.php'; ?></td></tr>
        <tr><td>Total Number of clients on transit who received Buprenorphine in the Reporting Month</td><td><?php include '../count_moh_731/transit_on_bup_male.php'; ?></td><td><?php include '../count_moh_731/transit_on_bup_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/transit_on_bup_all.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">1.4 WEANING OFF</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number of Clients Weaned off Buprenorphine</td><td><?php include '../count_moh_731/weaned_off_bup_male.php'; ?></td><td><?php include '../count_moh_731/weaned_off_bup_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/weaned_off_bup_all.php'; ?></td></tr>
        <tr><td>Number of Clients Weaned off Methadone</td><td><?php include '../count_moh_731/weaned_off_methadone_male.php'; ?></td><td><?php include '../count_moh_731/weaned_off_methadone_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/weaned_off_methadone_all.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">1.5 MAT INTERUPTIONS</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number of clients missing more than 5 consecutive doses in the Reporting Month </td><td><?php include '../count_moh_731/missed_more_5_male.php'; ?></td><td><?php include '../count_moh_731/missed_more_5_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/missed_more_5_all.php'; ?></td></tr>
        <tr><td>Number of clients LTFU in the Reporting Month </td><td><?php include '../count_moh_731/current_ltfu_male.php'; ?></td><td><?php include '../count_moh_731/current_ltfu_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/current_ltfu_all.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">1.6 HIV TESTING</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number of MAT Clients tested for HIV in the reporting Period 15 - 19 Years</td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients tested for HIV in the reporting Period 20 - 24 Years</td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients tested for HIV in the reporting Period 25 - 29 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients tested for HIV in the reporting Period 30+ Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">1.7 MAT Clients New-HIV Positive</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number of MAT Clients HIV Positive in the reporting Period 20 - 24 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients HIV Positive in the reporting Period 20 - 24 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients HIV Positive in the reporting Period 25 - 29 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients HIV Positive in the reporting Period 30+ Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">1.8 Number of MAT Clients Started on ART both offsite & Onsite</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>MAT Clients Started on ART 15 - 19 Year</td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>MAT Clients Started on ART 20 - 24 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>MAT Clients Started on ART 25 - 29 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>MAT Clients Started on ART 30+ Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">1.9 Number of MAT clients with Known HIV Positive Status</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Total Number of Active MAT clients HIV Positive as at the reporting Period 15 - 19 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total Number of Active MAT clients HIV Positive as at the reporting Period 20 - 24 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total Number of Active MAT clients HIV Positive as at the reporting Period 25 - 29 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total Number of Active MAT clients HIV Positive as at the reporting Period 30+ Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.0 Total Number of MAT Clients Currently on ART both offsite and onsite</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>MAT Clients Currently on ART 15 - 19 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>MAT Clients Currently on ART 20 - 24 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>MAT Clients Currently on ART 25 - 29 Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>MAT Clients Currently on ART 30+ Years </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.1 Viral load tracking MAT Clients</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Viral load result in the last 12 months </td><td><?php include '../count_moh_731/viral_load_last12mo_male.php'; ?></td><td><?php include '../count_moh_731/viral_load_last12mo_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/viral_load_last12mo_all.php'; ?></td></tr>
        <tr><td>Suppressed-&lt; 200 copies </td><td><?php include '../count_moh_731/viral_load_suppressed_200_male.php'; ?></td><td><?php include '../count_moh_731/viral_load_suppressed_200_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/viral_load_suppressed_200_all.php'; ?></td></tr>
        <tr><td>Suppressed-&lt; 50 copies </td><td><?php include '../count_moh_731/viral_load_suppressed_50_male.php'; ?></td><td><?php include '../count_moh_731/viral_load_suppressed_50_female.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../count_moh_731/viral_load_suppressed_50_all.php'; ?></td></tr>

        <tr class="section-header"><td colspan="4">2.2 Overdose MAT Clients </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Experienced overdose </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Received naloxone </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Deaths due to overdose </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.3 Psychosocial Interventions </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Received Psychosocial Interventions </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Supported with Community Reintegration </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.4 Violence prevention and Support </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Total Number who Experienced Violence Emotional/Psychological </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total Number who Experienced Physical Violence </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total Number who Experienced Sexual Violence </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total Number who Received violence support </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.5 Mental Health </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Screened_MH </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Diagnosed_MH </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Treated within the Facility_MH </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.6 STI MAT </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Screened _STI </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Diagnosed with STI </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Treated_STI </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.7 HCV (Hepatitis C) MAT </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Screened_HCV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Positive_HCV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Positive_HCV (Antibody test) </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Positive_HCV (Confirmatory PCR test) </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Treated_HCV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Currently on HCV Treatment </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.8 HBV (Hepatitis B) MAT </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Screened_HBV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Negative_HBV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Negative_HBV_Vaccinated </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Positive_HBV (Antibody Test) </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Positive_HBV (Confirmatory PCR Test) </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Managed_HBV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Currently under Management_HBV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">2.9 TB MAT </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Screened_TB </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Diagnosed _TB </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Started_TB RX </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Issued_TPT </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of TB clients HIV positive </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Total number of TB Clients on HAART </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">3.0 PrEP MAT </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Initiated_PrEP </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Testing HIV_positive while on PrEP </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of PrEP users diagnosed with STIs </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr class="section-header"><td colspan="4">3.1 PEP MAT </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number Exposed to HIV </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number Receive PEP <72hrs </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>

        <tr class="section-header"><td colspan="4">4.0 Nutrition support </td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Number of MAT Clients SAM </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number of MAT Clients MAM </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
        <tr><td>Number initiated on nutrition support </td><td>0</td><td>0</td><td style="color: blue; font-weight: bold;">0</td></tr>
    </table>
    <div class="section-title" style="font-weight: bold; margin-top: 20px; margin-bottom: 20px;">REPORTING TEAM</div>
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
                <td><input type="text" style="width: 90%" name="clinician_org" required></td>
                <td><input type="text" style="width: 90%" name="clinician_signature" required></td>
                <td><input type="date" style="width: 90%" name="clinician_date" required></td>
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
                <td><input type="text" style="width: 90%" name="counselor_org" required></td>
                <td><input type="text" style="width: 90%" name="counselor_signature" required></td>
                <td><input type="date" style="width: 90%" name="counselor_date" required></td>
            </tr>
        </tbody>
    </table>
    <div>
        <label for="print-pdf"></label>
        <button style="margin-top: 20px; background: green; color: white; width: 100px; height: 40px; border: none; border-radius: 5px;" id="print-pdf" onclick="window.print()">Print PDF</button>
    </div>
</body>
</html>