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

// Sample data pulling - replace with actual queries
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form P7 - Controlled Drugs Consumption Report</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        body { font-family: Arial, sans-serif;  background-color: #f8f9fa; color: #333; padding: 30px; }
            .container { width: 100%;   margin: 0 auto;    background: #fff;  padding: 20px;  border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);   }
            .form-header {  display: grid; grid-template-columns: repeat(1fr, 2fr, 1fr); text-align: center; margin-bottom: 10px; }
            h2, h3 { text-align: center;  margin-bottom: 10px; }
            .section { margin-bottom: 20px;}
            .section label {  display: inline-block; width: 250px; font-weight: bold; }
            .section input, textarea {  width: calc(100% - 260px);  padding: 6px;  margin-bottom: 8px; border: 1px solid #ccc; border-radius: 4px; }
            .two-columns label {  width: 400px; }
            table {width: 100%; border-collapse: collapse; margin-bottom: 20px;}
            table, th, td { border: 1px solid #666; }
            th, td {  padding: 8px; text-align: center;  font-size: 14px;}
            textarea { width: 100%;  border-radius: 4px; }
            .signatures {display: flex; justify-content: space-between; gap: 30px;  margin-top: 20px;}
            .signature-block {flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 8px; background: #fdfdfd; }
            .signature-block label {display: block; margin-top: 5px; font-weight: bold; }
            .signature-block input { width: 100%; padding: 5px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;  }
            .dates label {width: 250px; font-weight: bold; }
            .dates input {width: calc(50% - 270px); margin-left: 10px; }
            .form-group-1 {padding: 20px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;}
            .form-group-1 > div:last-child {grid-column: 1 / -1; display: flex; align-items: center; gap: 15px;}
            .form-group-1 > div:last-child label {font-weight: bold; color: #2c3e50; margin: 0;}
            .form-group-1 > div:last-child input[type="date"] {padding: 10px; border: 1px solid #dcdcdc; width: 45%; border-radius: 5px; font-size: 14px;}
            .form-group {display: flex; flex-direction: column;}
            .form-group label {margin-bottom: 8px; font-weight: bold; color: #2c3e50;}
            .received-input{text-align: center; color: #333399; font-weight: bold;}
            .int-input{width: 100%; height: 100%; background: yellow;}
            .form-header {display: grid; grid-template-columns: 20% 60% 20%; align-items: center; margin-bottom: 10px; border: none;  padding: 10px; }
            .form-header .logo-left { text-align: center; }
            .form-header .title-center {text-align: center; }
            .form-header .form-version {text-align: center;}
            .int-input {  width: 100%;  height: 100%; background: #E3E3E3;  border: none; text-align: center; padding: 8px; box-sizing: border-box; }
            table td { padding: 0;  }
            table td input.int-input {  height: 100%;  min-height: 40px;  }
            .resupply-input {width: 100%;  height: 100%; background: #CCFFFF; "border: none; text-align: center; padding: 8px; box-sizing: border-box; }
            .print-pdf{width: 120px; background: #333399; height: 40px; margin-top: 20px; color: #ffffff;             }

        </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <div class="logo-left">
                <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            </div>
            <div class="title-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <p>CONSUMPTION REQUEST AND REPORT</p>
            </div>
            <div class="form-version">
                <p>FORM P7 VER. SEP. 2025</p>
            </div>
        </div>
        <hr style="height: 2px; background-color: black; border: none;">
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
        <hr style="height: 2px; background-color: black; border: none;">
        <div class="form-group-1">
            <label>Number of Clients Who Received Methadone:</label>
            <input type="number" class='received-input' name="clients_methadone" value="<?php include '../countsFormp7/active_on_methadone.php' ?>">

            <label>Number of Clients Who Received Naltrexone:</label>
            <input type="number" class='received-input' name="clients_naltrexone" value="<?php include '../countsFormp7/active_on_naltrexone.php' ?>">

            <label>Number of Clients Who Received Buprenorphine:</label>
            <input type="number" class='received-input' name="clients_buprenorphine" value="<?php include '../countsFormp7/active_on_buprenorphine.php' ?>">

            <label>Average doses consumed in the Reporting Month - Methadone:</label>
            <input type="text" class='received-input' name="avg_methadone" value="<?php include '../countsFormp7/average_dose_methadone.php' ?>">
            <label>Average doses consumed in the Reporting Month - Buprenorphine:</label>
            <input type="text" class='received-input' name="avg_buprenorphine"value="<?php include '../countsFormp7/average_dose_buprenorphine.php' ?>">
        </div>
        <hr style="height: 2px; background-color: black; border: none;">
        <table>

            <thead>
                <tr>
                    <th>DRUG PRODUCT</th>
                    <th>Basic Pack Size</th>
                    <th>Beginning Balance</th>
                    <th>Quantity Received this period</th>
                    <th>Total Quantity dispensed in the month</th>
                    <th>Losses</th>
                    <th>Adjustments</th>
                    <th>Physical Count at Store</th>
                    <th>Days out of stock</th>
                    <th style="max-width: 200px;">Quantity required for RESUPPLY (Continuing patients)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Methadone</td><td>1000 ml</td><td><?php include '../countsFormp7/MethadoneBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/MethadoneRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/Methadonedisp.php' ?></td><td><input type="int"  class="int-input" name="losses_methadone"></td><td><input type="int"  class="int-input" name="adjustments_methadone"></td><td><input type="int"  class="int-input" name="physical_count_methadone" value="<?php echo isset($_GET['physical_count_methadone']) ? $_GET['physical_count_methadone'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_methadone"></td><td  style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_methadone.php'; ?></td>
                </tr>
                <tr>
                    <td>Buprenorphine 2mg</td><td>28 tabs</td><td><?php include '../countsFormp7/Buprenorphine2mgBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/Buprenorphine2mgRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/Buprenorphine2mgdisp.php' ?></td><td><input type="int" class="int-input" name="losses_buprenorphine2mg"></td><td><input type="int" class="int-input" name = "adjustments_buprenorphine2mg"></td><td><input type="int" class="int-input" name="physical_count_buprenorphine2mg" value="<?php echo isset($_GET['physical_count_buprenorphine2mg']) ? $_GET['physical_count_buprenorphine2mg'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_buprenorphine2mg"></td><td style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_buprenorphine2mg.php'; ?></td>
                </tr>
                <tr>
                    <td>Buprenorphine 8mg</td><td>28 tabs</td><td><?php include '../countsFormp7/Buprenorphine8mgBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/Buprenorphine8mgRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/Buprenorphine8mgdisp.php' ?></td><td><input type="int" class="int-input" name="losses_buprenorphine8mg"></td><td><input type="int" class="int-input" name = "adjustments_buprenorphine8mg"></td><td><input type="int" class="int-input" name="physical_count_buprenorphine8mg" value="<?php echo isset($_GET['physical_count_buprenorphine8mg']) ? $_GET['physical_count_buprenorphine8mg'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_buprenorphine8mg"></td><td style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_buprenorphine8mg.php'; ?></td>
                </tr>
                <tr>
                    <td>Naltrexone tabs 50mg</td><td>28 tabs</td><td><?php include '../countsFormp7/Naltrexone50mgBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/Naltrexone50mgRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/Naltrexone50mgdisp.php' ?></td><td><input type="int" class="int-input" name="losses_naltrexone50mg"></td><td><input type="int" class="int-input" name = "adjustments_naltrexone50mg"></td><td><input type="int" class="int-input" name="physical_count_naltrexone50mg" value="<?php echo isset($_GET['physical_count_naltrexone50mg']) ? $_GET['physical_count_naltrexone50mg'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_naltrexone50mg"></td><td style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_naltrexone50mg.php'; ?></td>
                </tr>
                <tr>
                    <td>Naltrexone tabs 100mg</td><td>28 tabs</td><td><?php include '../countsFormp7/Naltrexone100mgBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/Naltrexone100mgRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/Naltrexone100mgdisp.php' ?></td><td><input type="int" class="int-input" name="losses_naltrexone100mg"></td><td><input type="int" class="int-input" name = "adjustments_naltrexone100mg"></td><td><input type="int" class="int-input" name="physical_count_naltrexone100mg" value="<?php echo isset($_GET['physical_count_naltrexone100mg']) ? $_GET['physical_count_naltrexone100mg'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_naltrexone100mg"></td><td style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_naltrexone100mg.php'; ?></td>
                </tr>
                <tr>
                    <td>Naltrexone tabs 150mg</td><td>28 tabs</td><td><?php include '../countsFormp7/Naltrexone150mgBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/Naltrexone150mgRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/Naltrexone150mgdisp.php' ?></td><td><input type="int" class="int-input" name="losses_naltrexone150mg"></td><td><input type="int" class="int-input" name = "adjustments_naltrexone150mg"></td><td><input type="int" class="int-input" name="physical_count_naltrexone150mg" value="<?php echo isset($_GET['physical_count_naltrexone150mg']) ? $_GET['physical_count_naltrexone150mg'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_naltrexone150mg"></td><td style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_naltrexone150mg.php'; ?></td>
                </tr>
                <tr>
                    <td>Naltrexone Implant</td><td>Pieces</td><td><?php include '../countsFormp7/NaltrexoneimplantBB.php' ?></td><td style='color: red;'><?php include '../countsFormp7/NaltrexoneimplantRcvd.php' ?></td><td style='color: blue;'><?php include '../countsFormp7/NaltrexoneImplantdisp.php' ?></td><td><input type="int" class="int-input" name="losses_naltrexoneimplant"></td><td><input type="int" class="int-input" name = "adjustments_naltrexoneimplant"></td><td><input type="int" class="int-input" name="physical_count_naltrexoneimplant" value="<?php echo isset($_GET['physical_count_naltrexoneimplant']) ? $_GET['physical_count_naltrexoneimplant'] : ''; ?>" onchange="this.form.submit()"></td><td><input type="int" class="int-input" name="days_out_of_stock_naltrexoneimplant"></td><td style='background: #ccffff;'><?php include '../countsFormP7/quantity_for_resupply_naltrexoneimplant.php'; ?></td>
                </tr>
            </tbody>
        </table>

        <div class="div">
            <label>Comments (Including explanation of losses and adjustments):</label>
            <textarea rows="4" name="comments"></textarea>
        </div>

        <div class="signatures">
            <div class="signature-block">
                <h4>Submitted by</h4>
                <label>Pharmacist in charge:</label><input type="text" name="pharmacist_name" value="<?php echo htmlspecialchars($clinician_name); ?>">
                <label>Signature:</label><input type="text" name="signature" value="<?php echo htmlspecialchars($clinician_name); ?>">
                <label>Mobile phone int:</label><input type="text" name="phone">
                <label>Date:</label><input type="date" name="date_submitted">
            </div>

            <div class="signature-block">
                <h4>Confirmed by</h4>
                <label>MAT Pharmacist:</label><input type="text" name="mat_pharmacist" value="<?php echo $facilityIncharge; ?>">
                <label>Signature:</label><input type="text" name="mat_signature" value="<?php echo $facilityIncharge; ?>">
                <label>Mobile phone int:</label><input type="text" name="mat_phone" value="<?php echo $facilityPhone; ?>">
                <label>Date:</label><input type="date" name="mat_date">
            </div>
        </div>
        <button id="print-pdf" onclick="window.print()" class="print-pdf">Print PDF</button>
    </div>
   <script src="../assets/js/bootstrap.min.js"></script>
       <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Array of all physical count input names
                const physicalCountInputs = [
                    'physical_count_methadone',
                    'physical_count_buprenorphine2mg',
                    'physical_count_buprenorphine8mg',
                    'physical_count_naltrexone50mg',
                    'physical_count_naltrexone100mg',
                    'physical_count_naltrexone150mg',
                    'physical_count_naltrexoneimplant'
                ];

                // Loop through each input name and add event listener
                physicalCountInputs.forEach(function(inputName) {
                    const input = document.querySelector('input[name="' + inputName + '"]');

                    if (input) {
                        input.addEventListener('change', function() {
                            // Get current URL parameters
                            const urlParams = new URLSearchParams(window.location.search);

                            // Set the new physical count value
                            urlParams.set(inputName, this.value);

                            // Reload the page with updated parameters
                            window.location.search = urlParams.toString();
                        });
                    }
                });
            });
        </script>
</body>
</html>
