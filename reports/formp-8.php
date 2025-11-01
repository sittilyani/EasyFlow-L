<?php
// Start session
session_start();

// Include database connection
include('../includes/config.php');

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
$pharmicName = $userFullName['fullName'] ?? '';
$pharmicMobile = $userFullName['mobile'] ?? '';
$pharmicSign = $userInitials['initials'] ?? '';
$pharmicDate = date('Y-m-d'); // Current date in Y-m-d format

// Fetch facility settings
$facilityName = "N/A";
$countyName = "N/A";
$facilityIncharge = "N/A";
$facilityPhone = "N/A";

$queryFacilitySettings = "SELECT facilityname, countyname, facilityincharge, facilityphone FROM facility_settings LIMIT 1"; // Assuming one row for settings
$resultFacilitySettings = $conn->query($queryFacilitySettings);

if ($resultFacilitySettings && $resultFacilitySettings->num_rows > 0) {
    $rowFacilitySettings = $resultFacilitySettings->fetch_assoc();
    $facilityName = htmlspecialchars($rowFacilitySettings['facilityname']);
    $countyName = htmlspecialchars($rowFacilitySettings['countyname']);
    $facilityIncharge = htmlspecialchars($rowFacilitySettings['facilityincharge']);
    $facilityPhone = htmlspecialchars($rowFacilitySettings['facilityphone']);
}

// Logged-in user details (from session, set upon login)
$loggedInUserFullName = isset($_SESSION['loggedin_user_fullname']) ? htmlspecialchars($_SESSION['loggedin_user_fullname']) : 'N/A';
$loggedInUserPhone = isset($_SESSION['loggedin_user_phone']) ? htmlspecialchars($_SESSION['loggedin_user_phone']) : 'N/A';
$loggedInUserSignature = isset($_SESSION['loggedin_user_signature']) ? htmlspecialchars($_SESSION['loggedin_user_signature']) : ''; // Could be a path to an image or just a name

?>


<!DOCTYPE html>
<html>
    <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <title>FormP8</title>
            <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
            <link rel="stylesheet" href="stylep8.css" type="text/css">
           <script src="../assets/js/bootstrap.min.js"></script>
    </head>

    <body>
<style>
@page { margin-left: 0.7in; margin-right: 0.7in; margin-top: 0.3in; margin-bottom: 0.75in; }
body { margin-left: 0.7in; margin-right: 0.7in; margin-top: 0.3in; margin-bottom: 0.75in; }
</style>
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
                <col class="col11">
                <tbody>

                <form action="process_FormP8.php" method="POST">
                    <tr class="row0">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style2 s"><img src="../assets/images/Government of Kenya.png" width="115" height="109" alt=""></td>
                        <td class="column2 style3 s style5" colspan="9">MEDICALLY ASSISTED THERAPY (MAT) PATIENTS SUMMARY REPORT</td>
                        <td class="column8 style6 s style8" colspan="1">FORM P8</td>
                    </tr>


                    <tr class="rowDate">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 s style3" colspan="3">Facility:</td>
                        <td class="column6 style1 s style2" colspan="4">
                        <input type="text" name="entryfacility">
                        <?php
                            if (isset($_SESSION['current_facility_name'])) {
                                echo htmlspecialchars($_SESSION['current_facility_name']);
                            } else {
                                echo "No Facility Set";
                            }
                        ?></td>
                        <td class="column6 style1 s style2" colspan="3"><b>County:</b></td>
                        <td class="column6 style1 s style2" colspan="4">
                        <?php
                            if (isset($_SESSION['current_county'])) {
                                echo htmlspecialchars($_SESSION['current_county']);
                            } else {
                                echo "No County Set";
                            }
                        ?></td>
                    </tr>
                    <tr class="rowDetails">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 s style3" colspan="3"><b>Beginning Date:</b></td>
                        <td class="column6 style1 s style2" colspan="3"><input type="date" name="beginDate" style="width: 300px; background-color: #d6eaf8;" placeholder="Select Beginning date"></td>
                        <td class="column6 style1 s style2" colspan="3"><b>Ending Date:</b></td>
                        <td class="column6 style1 s style2" colspan="2"><input type="date" name="endDate" style="width: 300px; background-color: #d6eaf8;" placeholder="Select Ending date"></td>
                    </tr>


                    <tr class="row1">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style9 null style9" colspan="3" rowspan="2"></td>
                        <td class="column4 style10 s style12" colspan="4"><b>Methadone</b></td>
                        <td class="column8 style10 s style12" colspan="4"><b>Buprenorphine</b></td>
                    </tr>
                    <tr class="row2">
                        <td class="column0">&nbsp;</td>
                        <td class="column4 style13 s"><b>Male</b></td>
                        <td class="column5 style13 s"><b>Female</b></td>
                        <td class="column6 style13 s"><b>Others</b></td>
                        <td class="column7 style13 s"><b>Total</b></td>


                        <td class="column8 style13 s"><b>Male</b></td>
                        <td class="column9 style13 s"><b>Female</b></td>
                        <td class="column10 style13 s"><b>Others</b></td>
                        <td class="column11 style13 s"><b>Total</b></td>
                    </tr>
                    <tr class="row3">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s style14" colspan="3">Number of clients inducted in the current reporting period </td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/monthlyinductedMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/monthlyinductedMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/monthlyinductedMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/monthlytotalinductedCount.php'; ?></center></td>
                        <td class="column4 style13 s"><center><?php include '../countsFormP8/countsBupren/monthlyinductedMetMale.php'; ?></center></td>
                        <td class="column5 style13 s"><center><?php include '../countsFormP8/countsBupren/monthlyinductedMetFemale.php'; ?></center></td>
                        <td class="column6 style13 s"><center><?php include '../countsFormP8/countsBupren/monthlyinductedMetOther.php'; ?></center></td>
                        <td class="column7 style13 s"><center><?php include '../countsFormP8/countsBupren/monthlytotalinductedCount.php'; ?></center></td>

                    </tr>
                    <tr class="row4">
                        <td class="column0">&nbsp;</td>


                        <td class="column1 style14 s style14" colspan="3">Total number of active clients </td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/activeMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/activeMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/activeMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/totalactMetCount.php'; ?> </center></td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/countsBupren/activeMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/countsBupren/activeMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/countsBupren/activeMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/countsBupren/totalactMetCount.php'; ?> </center></td
                    </tr>
                    <tr class="row5">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s style14" colspan="3">Number of clients on transit</td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/monthlytransitMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/monthlytransitMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/monthlytransitMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/monthlytotaltransitCount.php'; ?></center></td>

                        <td class="column4 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlytransitMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlytransitMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlytransitMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/countsBupren/monthlytotaltransitCount.php'; ?></center></td>

                    </tr>
                    <tr class="row6">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style17 s style17" rowspan="2">Number of clients drop out in the current reporting period</td>
                        <td class="column2 style9 s style9" colspan="2">LTFU</td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/monthlyltfuMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/monthlyltfuMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/monthlyltfuMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/monthlytotalltfuCount.php'; ?></center></td>
                        <td class="column8 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlyltfuMetMale.php'; ?></center></td>
                        <td class="column9 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlyltfuMetFemale.php'; ?></center></td>
                        <td class="column10 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlyltfuMetOther.php'; ?></center></td>
                        <td class="column11 style16 s"><center><?php include '../countsFormP8/countsBupren/monthlytotalltfuCount.php'; ?></center></td>
                    </tr>
                    <tr class="row7">
                        <td class="column0">&nbsp;</td>
                        <td class="column2 style9 s style9" colspan="2">DECEASED</td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/monthlydeadMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/monthlydeadMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/monthlydeadMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/monthlytotaldeadCount.php'; ?></center></td>
                        <td class="column8 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlydeadMetMale.php'; ?></center></td>
                        <td class="column9 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlydeadMetFemale.php'; ?></center></td>
                        <td class="column10 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlydeadMetOther.php'; ?></center></td>
                        <td class="column11 style16 s"><center><?php include '../countsFormP8/countsBupren/monthlytotaldeadCount.php'; ?></center></td>
                    </tr>
                    
                    <tr class="row15">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s style14" colspan="3">Clients treated on overdose </td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/OverDoseMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/OverDoseFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/OverDoseOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/OverDoseTotal.php'; ?></center></td>
                        <td class="column8 style15 s"><input type="text" name="pharmName" style= "background-color: #d6eaf8;" readonly></td>
                        <td class="column9 style15 s"><input type="text" name="pharmName" style= "background-color: #d6eaf8;" readonly></td>
                        <td class="column10 style15 s"><input type="text" name="pharmName" style= "background-color: #d6eaf8;" readonly></td>
                        <td class="column11 style16 s"><input type="text" name="pharmName" style= "background-color: #d6eaf8;" readonly></td>
                    </tr>
                    <tr class="row16">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s style14" colspan="3">Number of clients missing more than 5 consecutive doses </td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/missedMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/missedFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/missedOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/missedTotal.php'; ?></center></td>
                        <td class="column8 style15 s"><center><?php include '../countsFormP8/countsBupren/missedMale.php'; ?></center></td>
                        <td class="column9 style15 s"><center><?php include '../countsFormP8/countsBupren/missedFemale.php'; ?></center></td>
                        <td class="column10 style15 s"><center><?php include '../countsFormP8/countsBupren/missedOther.php'; ?></center></td>
                        <td class="column11 style16 s"><center><?php include '../countsFormP8/countsBupren/missedTotal.php'; ?></center></td>
                    </tr>
                    <tr class="row17">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s style14" colspan="3">Number of clients Weaned off MAT</td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/monthlyweanedMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/monthlyweanedMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/monthlyweanedMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/monthlytotalweanedCount.php'; ?></center></td>
                        <td class="column8 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlyweanedMetMale.php'; ?></center></td>
                        <td class="column9 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlyweanedMetFemale.php'; ?></center></td>
                        <td class="column10 style15 s"><center><?php include '../countsFormP8/countsBupren/monthlyweanedMetOther.php'; ?></center></td>
                        <td class="column11 style16 s"><center><?php include '../countsFormP8/countsBupren/monthlytotalweanedCount.php'; ?></center></td>
                    </tr>
                    <tr class="row18">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s style14" colspan="3">Methadone average doses</td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/AvgDoseMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/AvgDoseMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/AvgDoseMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/AvgDoseMetTotal.php'; ?></center></td>
                        <td class="column4 style15 s"><center><?php include '../countsFormP8/countsBupren/AvgDoseMetMale.php'; ?></center></td>
                        <td class="column5 style15 s"><center><?php include '../countsFormP8/countsBupren/AvgDoseMetFemale.php'; ?></center></td>
                        <td class="column6 style15 s"><center><?php include '../countsFormP8/countsBupren/AvgDoseMetOther.php'; ?></center></td>
                        <td class="column7 style16 s"><center><?php include '../countsFormP8/countsBupren/AvgDoseMetTotal.php'; ?></center></td>
                    </tr>
                    <tr class="row19">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style20 null style22" colspan="11"></td>
                    </tr>

                    <!--This is the textarea for comments -->

                    <tr class="row20">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style23 s style25" colspan="11">
                            <textarea placeholder="Enter any comments here!"

                                    style="background-color: #d6eaf8; width: 100%;
                                    height: 140px;
                                    resize: none;
                                    font-family: "Times New Roman", Times, serif;
                                    word-wrap: break-word;
                                    white-space: pre-wrap;">

                            </textarea>
                        </td>
                    </tr>
                    <tr class="row21">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style26 s style31" rowspan="2">Report submitted by:</td>
                        <td class="column2 style27 s style29" colspan="3"><center><input type="text" name="pharmicName" style="width: 200px; background-color: #d6eaf8;" value="<?php echo htmlspecialchars($pharmicName); ?>" readonly></center></td>
                        <td class="column5 style27 s style28" colspan="2"><center><input type="text" name="pharmSign" style="width: 200px; background-color: #d6eaf8;" value="<?php echo htmlspecialchars($pharmicSign); ?>" readonly></center></td>
                        <td class="column7 style27 s style29" colspan="3"><center><input type="text" name="pharmicMobile" style="width: 200px; background-color: #d6eaf8;" value="<?php echo htmlspecialchars($pharmicMobile); ?>" readonly></center></td>
                        <td class="column10 style30 s style30" colspan="2"><center><input type="date" name="pharmicDate" style="width: 200px; background-color: #d6eaf8;" value="<?php echo htmlspecialchars($pharmicDate); ?>" readonly></center></td>
                    </tr>
                    <tr class="row22">
                        <td class="column0">&nbsp;</td>
                        <td class="column2 style32 s style34" colspan="3"><center>MAT Pharmacist in charge </center></td>
                        <td class="column5 style32 s style33" colspan="2"><center>Signature</center></td>
                        <td class="column7 style32 s style34" colspan="3"><center>Mobile Phone</center></td>
                        <td class="column10 style35 s style35" colspan="2"><center>Date</center></td>
                    </tr>
                    <tr class="row23">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style26 s style31" rowspan="2">Report reviewed by:</td>
                        <td class="column2 style27 s style29" colspan="3"><center><input type="text" name="pharmicName" style="width: 200px; background-color: #d6eaf8;" value="<?php echo $facilityIncharge; ?>"></center></td>
                        <td class="column5 style27 s style28" colspan="2"><center><input type="text" name="pharmSign" style="width: 200px; background-color: #d6eaf8;" placeholder="Enter Initials"></center></td>
                        <td class="column7 style36 s style38" colspan="3"><center><input type="text" name="pharmicMobile" style="width: 200px; background-color: #d6eaf8;" value="<?php echo $facilityPhone; ?>"></center></td>
                        <td class="column10 style39 s style39" colspan="2"><center><input type="date" name="pharmicDate" style="width: 200px; background-color: #d6eaf8;" value="<?php echo date('Y-m-d'); ?>"></center></td>
                    </tr>
                    <tr class="row24">
                        <td class="column0">&nbsp;</td>
                        <td class="column2 style32 s style34" colspan="3"><center>Pharmacist in charge </center></td>
                        <td class="column5 style32 s style33" colspan="2"><center>Signature</center></td>
                        <td class="column7 style32 s style34" colspan="3"><center>Mobile Phone</center></td>
                        <td class="column10 style35 s style35" colspan="2"><center>Date</center></td>
                    </tr>
                </form>
                </tbody>
        </table>

        <!--Totals for rows across-->


    </body>
</html>
