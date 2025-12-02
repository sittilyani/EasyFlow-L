<?php
include "../includes/config.php";

    // Get current month and year for default selection (optional)
    $currentMonth = date('m');
    $currentYear = date('Y');
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

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PsychoSocioReports</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/reportforms.css" type="text/css">

    <style>
        .grid-container{
            display: grid;
            grid-template-columns: repeat(6, 8fr);
            padding: 20px 40px;
            grid-gap: 20px;
            height: 75vh;

        }
        .grid-item{
            border: solid thin;
            align-items: center;
            align-content: center;
            padding: 10px 20px;

        }
          #header{
            font-weight: bold;
          }
          h3{
            margin-left: 40px;
          }

    </style>
</head>
<body>
    <div class="header">
        <h2>Republic of Kenya</h2>
        <img src="../assets/images/Government of Kenya.png" width="204" height="154" alt="">
        <h2>Ministry of Health</h2>
        <h3>PSYCHOSOCIAL OUTCOMES REPORT</h3>
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
        <tr class="section-header"><td colspan="4">ATTENDANCE</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Clients 15 - 19 Years</td><td><?php include '../counts/psycho_male15_20.php'; ?></td><td><?php include '../counts/psycho_female15_20.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/psycho_total15_20.php'; ?></td></tr>
        <tr><td>Clients 20 - 24 Years</td><td><?php include '../counts/psycho_male21_24.php'; ?></td><td><?php include '../counts/psycho_female21_24.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/psycho_total21_24.php'; ?></td></tr>
        <tr><td>Clients 25 - 35 Years</td><td><?php include '../counts/psycho_male25_35.php'; ?></td><td><?php include '../counts/psycho_female25_35.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/psycho_total25_35.php'; ?></td></tr>
        <tr><td>Clients 36+ Years</td><td><?php include '../counts/psycho_male36_above.php'; ?></td><td><?php include '../counts/psycho_female36_above.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/psycho_total36_above.php'; ?></td></tr>
    </table>

    <table>
        <tr class="section-header"><td colspan="4">PSYCHO-SOCIO OUTCOMES</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Re-integration</td><td><?php include '../counts/reintegration_maleCount.php'; ?></td><td><?php include '../counts/reintegration_femaleCount.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/reintegration_totalCount.php'; ?></td></tr>
        <tr><td>Employment</td><td><?php include '../counts/employment_maleCount.php'; ?></td><td><?php include '../counts/employment_femaleCount.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/employment_totalCount.php'; ?></td></tr>
        <tr><td>Stable accomodation</td><td><?php include '../counts/accomodation_maleCount.php'; ?></td><td><?php include '../counts/accomodation_femaleCount.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/accomodation_totalCount.php'; ?></td></tr>
        <tr><td>GBV support</td><td><?php include '../counts/gbvsupport_maleCount.php'; ?></td><td><?php include '../counts/gbvsupport_femaleCount.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/gbvsupport_totalCount.php'; ?></td></tr>
    </table>

    <table>
        <tr class="section-header"><td colspan="4">PSYCHOSOCIAL INTERVENETIONS</td></tr>
        <tr style='font-weight: bold;'><td>Data Element</td><td>Male</td><td>Female</td><td>Total</td></tr>
        <tr><td>Clients 15 - 19 Years</td><td><?php include '../counts/linkage_maleCount15_20.php'; ?></td><td><?php include '../counts/linkage_femaleCount15_20.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/linkage_totalCount15_20.php'; ?></td></tr>
        <tr><td>Clients 20 - 24 Years</td><td><?php include '../counts/linkage_maleCount21_24.php'; ?></td><td><?php include '../counts/linkage_femaleCount21_24.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/linkage_totalCount21_24.php'; ?></td></tr>
        <tr><td>Clients 25 - 35 Years</td><td><?php include '../counts/linkage_maleCount25_35.php'; ?></td><td><?php include '../counts/linkage_femaleCount25_35.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/linkage_totalCount25_35.php'; ?></td></tr>
        <tr><td>Clients 36+ Years</td><td><?php include '../counts/linkage_maleCount36_above.php'; ?></td><td><?php include '../counts/linkage_femaleCount36_above.php'; ?></td><td style="color: blue; font-weight: bold;"><?php include '../counts/linkage_totalCount36_above.php'; ?></td></tr>
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
    
