<?php
session_start();

require_once '../includes/config.php'; // Include your database connection

// Fetch facility settings
$facilityName = "N/A";
$countyName = "N/A";
$scountyName = "N/A";
$facilityIncharge = "N/A";
$facilityPhone = "N/A";

$queryFacilitySettings = "SELECT facilityname, countyname, subcountyname, facilityincharge, facilityphone FROM facility_settings LIMIT 1"; // Assuming one row for settings
$resultFacilitySettings = $conn->query($queryFacilitySettings);

if ($resultFacilitySettings && $resultFacilitySettings->num_rows > 0) {
    $rowFacilitySettings = $resultFacilitySettings->fetch_assoc();
    $facilityName = htmlspecialchars($rowFacilitySettings['facilityname']);
    $countyName = htmlspecialchars($rowFacilitySettings['countyname']);
    $scountyName = htmlspecialchars($rowFacilitySettings['subcountyname']);
    $facilityIncharge = htmlspecialchars($rowFacilitySettings['facilityincharge']);
    $facilityPhone = htmlspecialchars($rowFacilitySettings['facilityphone']);
}

// Fetch logged-in user's full name
$loggedInUserId = $_SESSION['user_id']; // Adjust based on your session variable storing the user's ID
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
$maticName = $userFullName['fullName'] ?? '';
$maticMobile = $userFullName['mobile'] ?? '';
$maticSign = $userInitials['initials'] ?? '';
$maticDate = date('Y-m-d'); // Current date in Y-m-d format
// Logged-in user details (from session, set upon login)
$loggedInUserFullName = isset($_SESSION['loggedin_user_fullname']) ? htmlspecialchars($_SESSION['loggedin_user_fullname']) : 'N/A';
$loggedInUserPhone = isset($_SESSION['loggedin_user_phone']) ? htmlspecialchars($_SESSION['loggedin_user_phone']) : 'N/A';
$loggedInUserSignature = isset($_SESSION['loggedin_user_signature']) ? htmlspecialchars($_SESSION['loggedin_user_signature']) : ''; // Could be a path to an image or just a name

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="stylep7.css" type="text/css">
        <title>Form P 7</title>
        <style>
            @page { margin-left: 0.1in; margin-right: 0.1in; margin-top: 0.75in; margin-bottom: 0.75in; }
            body { margin-left: 0.1in; margin-right: 0.1in; margin-top: 0.75in; margin-bottom: 0.75in; }
            /* Styling for input fields to fit within table cells */
            table input[type="text"],
            table input[type="number"],
            table input[type="date"] {
                width: 100%;
                box-sizing: border-box; /* Include padding and border in the element's total width and height */
                padding: 2px 5px;
                border: 1px solid #ccc;
                font-size: 0.9em;
            }
            table .column-fit { /* A class to apply to columns where inputs should fit */
                padding: 0; /* Remove padding from cell to allow input to take full width */
            }
            .comments-textarea {
                width: 100%;
                box-sizing: border-box;
                min-height: 80px; /* Adjust as needed */
                padding: 5px;
                border: 1px solid #ccc;
                font-size: 0.9em;
            }
        </style>
    </head>
    <body>
        <table border="0" cellpadding="0" cellspacing="0" id="sheet0" class="sheet0 gridlines">
            <col class="col0">
            <col class="col1">
            <col class="col2">
            <col class="col3">
            <col class="col4">
            <col class="col5">
            <col class="col6">
            <col class="col7">
            <col class="col8">
            <col class="col9">
            <col class="col10">
            <tbody style = 'width: 100%';>
                <tr class="row0">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style1 null style3" colspan="1">

                        <img src="../assets/images/Government of Kenya.png" width="201" height="191" alt="Kenyan Logo">
                    </td>
                    <td class="column5 style1 null style3" colspan="7">
                        <h2><b>MEDICALLY ASSISTED THERAPY CONTROLLED DRUGS CONSUMPTION REQUEST AND REPORT FORM</b></h2>
                    </td>
                    <td class="column9 style1 null style2" colspan="2">
                        <b>FORM P7</b>
                    </td>
                </tr>
                <tr class="row1">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style1 s style3" colspan="5">Facility: <b><?php echo $facilityName; ?></b></td>
                    <td class="column6 style1 s style2" colspan="5">County: <b><?php echo $countyName; ?></b></td>
                </tr>
                <tr class="row2">
                    <td class="column0">&nbsp;</td>
                    <td class="column6 style1 s style2" colspan="4">
                        <?php
                            // Already handled above with $facilityName
                        ?>
                    </td>
                    <td class="column6 style1 s style2" colspan="4">
                        <?php
                            // Already handled above with $countyName
                        ?>
                    </td>
                </tr>
                <tr class="row3">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style24 s style25" colspan="2">Beginning Date</td>
                    <td class="column3 style26 s style27 column-fit" colspan="3">
                        <input type='text' name='beginning_date' id='beginning_date' value='<?php echo date('Y-m-01'); ?>' readonly>
                    </td>
                    <td class="column6 style24 s style25" colspan="3">Ending Date</td>
                    <td class="column9 style28 s style28 column-fit" colspan="2">
                        <input type='text' name='end_date' id='end_date' value='<?php echo date('Y-m-t'); ?>' readonly>
                    </td>
                </tr>
                <tr class="row4">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style5 s style8" colspan="2">Active on Methadone</td>
                    <td class="column3 style17 s style18" colspan="2">
                        <?php
                                // Include the database connection file
                                include_once('../includes/config.php');

                                // Define the date range for the last 6 days
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');

                                // Define the SQL query to count active patients on Buprenorphine within the last 6 days
                                $query = "SELECT
                                    COUNT(DISTINCT mat_id) AS total_met_count
                                FROM
                                    patients
                                WHERE
                                    drugname = 'Methadone'
                                    AND current_status = 'active'
                                    AND comp_date BETWEEN '$startDate' AND '$endDate'";

                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                        // Fetch the count
                                        $row = $result->fetch_assoc();
                                        $total_met_count = $row['total_met_count'];

                                        // Output the count
                                        echo $total_met_count;
                                } else {
                                        echo "0"; // If no active patientss on Buprenorphine found within the last 6 days, display 0
                                }
                                ?>
                    </td>
                    <td class="column5 style8 s style6" colspan="2">Active of Buprenorphine</td>
                    <td class="column7 style5 s style6" colspan="2">
                        <?php
                                // Include the database connection file
                                include_once('../includes/config.php');

                                // Define the date range for the last 6 days
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');

                                // Define the SQL query to count active patients on Buprenorphine within the last 6 days
                                $query = "SELECT
                                    COUNT(DISTINCT mat_id) AS total_bup_count
                                FROM
                                    patients
                                WHERE
                                    drugname LIKE 'Buprenorphine%'
                                    AND current_status = 'active'
                                    AND comp_date BETWEEN '$startDate' AND '$endDate'";

                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                        // Fetch the count
                                        $row = $result->fetch_assoc();
                                        $total_bup_count = $row['total_bup_count'];

                                        // Output the count
                                        echo $total_bup_count;
                                } else {
                                        echo "0"; // If no active patientss on Buprenorphine found within the last 6 days, display 0
                                }
                                ?>
                    </td>
                    <td class="column9 style9 s">Active on Naltrexone</td>
                    <td class="column10 style7 null">
                        <?php
                                // Include the database connection file
                                include_once('../includes/config.php');

                                // Define the date range for the last 6 days
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');

                                // Define the SQL query to count active patients on Buprenorphine within the last 6 days
                                $query = "SELECT
                                    COUNT(DISTINCT mat_id) AS total_nal_count
                                FROM
                                    patients
                                WHERE
                                    drugname LIKE 'Naltrexone%'
                                    AND current_status = 'active'
                                    AND comp_date BETWEEN '$startDate' AND '$endDate'";

                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                        // Fetch the count
                                        $row = $result->fetch_assoc();
                                        $total_nal_count = $row['total_nal_count'];

                                        // Output the count
                                        echo $total_nal_count;
                                } else {
                                        echo "0"; // If no active patientss on Buprenorphine found within the last 6 days, display 0
                                }
                                ?>
                    </td>
                </tr>
                <tr class="row5">
                    <td class="column0 style10 null">#</td>
                    <td class="column1 style11 s">DRUG PRODUCT</td>
                    <td class="column2 style12 s">&nbsp;Basic Pack Size</td>
                    <td class="column3 style16 s" style="white-space: nowrap;">&nbsp;&nbsp;Beginning Balance</td>
                    <td class="column4 style16 s" style="white-space: nowrap;">&nbsp;&nbsp;Quantity Received this period</td>
                    <td class="column5 style12 s" style="white-space: nowrap;">&nbsp;Total Quantity dispensed in the month</td>
                    <td class="column6 style12 s">&nbsp;Losses</td>
                    <td class="column7 style12 s">&nbsp;Adjustments</td>
                    <td class="column8 style12 s" style="white-space: nowrap;">&nbsp;Physical Count at store</td>
                    <td class="column9 style12 s">&nbsp;Days out of stock at the store</td>
                    <td class="column10 style19 s" style="white-space: nowrap;">&nbsp;Quantity required for RESUPPLY (Continuing patientss)</td>
                </tr>
                <tr class="row5">
                        <td class="column0 style10 null">#</td>
                        <td class="column1 style11 s">DRUG PRODUCT</td>
                        <td class="column2 style12 s">&nbsp;Basic Pack Size</td>
                        <td class="column3 style16 s" style="white-space: nowrap;">&nbsp;&nbsp;Beginning Balance</td>
                        <td class="column4 style16 s" style="white-space: nowrap;">&nbsp;&nbsp;Quantity Received this period</td>
                        <td class="column5 style12 s" style="white-space: nowrap;">&nbsp;Total Quantity dispensed in themonth</td>
                        <td class="column6 style12 s">&nbsp;Losses</td>
                        <td class="column7 style12 s">&nbsp;Adjustments</td>
                        <td class="column8 style12 s" style="white-space: nowrap;">&nbsp;Physical Count at store</td>
                        <td class="column9 style12 s">&nbsp;Days out of stock at the store</td>
                        <td class="column10 style19 s" style="white-space: nowrap;">&nbsp;Quantity required for RESUPPLY (Continuing patientss)</td>
                    </tr>
                    <tr class="row6">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Methadone </td>
                        <td class="column2 style20 s">
                            <input type="text" name="bp1" id="bp1" value="1000 mL" readonly>
                        </td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/MethadoneBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/MethadoneRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Methadonedisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l1" id="l1" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad1" id="ad1" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps1" id="ps1" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockmethadone.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row7">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Buprenorphine 2mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp1" id="bp1" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Buprenorphine2mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Buprenorphine2mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Buprenorphine2mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l2" id="l2" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad2" id="ad2" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps2" id="ps2" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockbuprenorphine2mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row8">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Buprenorphine 4mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp2" id="bp2" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Buprenorphine4mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Buprenorphine4mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Buprenorphine4mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l3" id="l3" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad3" id="ad3" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps3" id="ps3" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockbuprenorphine4mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row9">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Buprenorphine 8mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp3" id="bp3" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Buprenorphine8mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Buprenorphine8mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Buprenorphine8mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l4" id="l4" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad4" id="ad4" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps4" id="ps4" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockbuprenorphine8mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row10">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone tabs 50mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp5" id="bp5" value="7 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Naltrexone50mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Naltrexone50mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Naltrexone50mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l5" id="l5" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad5" id="ad5" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps5" id="ps5" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexone50mg.php'; ?></center></td>
                        <td class="column10 style20 s">-<input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row11">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone tabs 100mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp6" id="bp6" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Naltrexone100mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Naltrexone100mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Naltrexone100mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l6" id="l6" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad6" id="ad6" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps6" id="ps6" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexone100mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row12">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone tabs 150mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp7" id="bp7" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Naltrexone150mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Naltrexone150mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Naltrexone150mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l7" id="l7" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad7" id="ad7" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps7" id="ps7" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexone150mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row13">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone Implant</td>
                        <td class="column2 style20 s"><input type="text" name="bp8" id="bp8" value="1 implant pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/NaltrexoneImplantBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/NaltrexoneImplantRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/NaltrexoneImplantdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l8" id="l8" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad8" id="ad8" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps8" id="ps8" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexoneImplant.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row14">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style15 s style15" colspan="10" rowspan="2">Comments (Including explanation of losses and adjustments): TEXTBOXAREA</td>
                    </tr>
                    <tr class="row15">
                        <td class="column0">&nbsp;</td>
                    </tr>
                <tr class="row16">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style1 s style3" colspan="2">Submitted By:</td>
                    <td class="column3 style21 s style22 column-fit" colspan="2"><input type="text" name="submitted_by_name" id="submitted_by_name" value="<?php echo $maticName; ?>" readonly></td>
                    <td class="column5 style21 s style22 column-fit" colspan="2"><input type="text" name="submitted_by_signature" id="submitted_by_signature" value="<?php echo $maticSign; ?>" readonly></td>
                    <td class="column7 style21 s style22 column-fit" colspan="2"><input type="text" name="submitted_by_mobile" id="submitted_by_mobile" value="<?php echo $maticMobile; ?>" readonly></td>
                    <td class="column9 style21 s style22 column-fit" colspan="2"><input type="date" name="submitted_date" id="submitted_date" value="<?php echo date('Y-m-d'); ?>" readonly></td>
                </tr>
                <tr class="row17">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style1 null style3" colspan="2"></td>
                    <td class="column3 style5 s style6" colspan="2">MAT Pharmacist in charge</td>
                    <td class="column5 style1 s style3" colspan="2">Signature</td>
                    <td class="column7 style1 s style3" colspan="2">Mobile Phone</td>
                    <td class="column9 style1 s style3" colspan="2">Date</td>
                </tr>
                <tr class="row18">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style1 s style3" colspan="2">Reviewed By:</td>
                    <td class="column3 style21 s style22 column-fit" colspan="2"><input type="text" name="reviewed_by_name" id="reviewed_by_name" value="<?php echo $facilityIncharge; ?>" readonly></td>
                    <td class="column5 style21 s style22 column-fit" colspan="2"><input type="text" name="reviewed_by_signature" id="reviewed_by_signature" value="" ></td>
                    <td class="column7 style21 s style22 column-fit" colspan="2"><input type="text" name="reviewed_by_mobile" id="reviewed_by_mobile" value="<?php echo $facilityPhone; ?>" readonly></td>
                    <td class="column9 style21 s style22 column-fit" colspan="2"><input type="date" name="reviewed_date" id="reviewed_date" value="<?php echo date('Y-m-d'); ?>" readonly></td>
                </tr>
                <tr class="row19">
                    <td class="column0">&nbsp;</td>
                    <td class="column1 style1 null style3" colspan="2"></td>
                    <td class="column3 style5 s style6" colspan="2">Pharmacist in charge</td>
                    <td class="column5 style1 s style3" colspan="2">Signature</td>
                    <td class="column7 style1 s style3" colspan="2">Mobile Phone</td>
                    <td class="column9 style1 s style3" colspan="2">Date</td>
                </tr>
            </tbody>
        </table>
    </body>
</html>