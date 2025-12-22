<?php
$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = 'sittilyani';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the user_id from the query parameter
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user
$query = "SELECT * FROM patients WHERE p_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
        $mat_id = $_POST['mat_id'];
        $mat_number = $_POST['mat_number'];
        $clientName = $_POST['clientName'];
        $motherName = $_POST['motherName'];
        $sname = $_POST['sname'];
        $nickName = $_POST['nickName'];
        $nat_id = $_POST['nat_id'];
        $dob = $_POST['dob'];
        /*$age = $_POST['age']; */
        $sex = $_POST['sex'];
        $reg_facility = $_POST['reg_facility']; // Assuming the form field name is reg facility
        $mflcode = $_POST['mflcode'];
        $county = $_POST['county'];
        $scounty = $_POST['scounty'];
        $residence_scounty = $_POST['residence_scounty'];
        $p_address = $_POST['p_address'];
        $reg_date = $_POST['reg_date'];
        $client_phone = $_POST['client_phone'];
        $mat_status = $_POST['mat_status'];
        $transfer_id = $_POST['transfer_id'];
        $referral_type = $_POST['referral_type'];
        $referring_facility = $_POST['referring_facility'];
        $reffering_fac_client_number = $_POST['reffering_fac_client_number'];
        $accompanment_type = $_POST['accompanment_type'];
        $peer_edu_name = $_POST['peer_edu_name'];
        $peer_edu_phone = $_POST['peer_edu_phone'];
        $rx_supporter_name = $_POST['rx_supporter_name'];
        $rx_supporter_phone = $_POST['rx_supporter_phone'];
        $dosage = $_POST['dosage'];
        $current_status = $_POST['current_status'];
        $hcw_name = $_POST['hcw_name'];
        $hcw_sign = $_POST['hcw_sign'];

    $reg_date = date('Y-m-d', strtotime($reg_date));

    $query = "UPDATE patients SET mat_id = ?, mat_number = ?, clientName = ?, motherName = ?, sname = ?, nickName = ?, nat_id = ?, dob = ?, sex = ?, reg_facility = ?, mflcode = ?, county = ?, scounty = ?, reg_date = ?, residence_scounty = ?, p_address = ?,  client_phone = ?, mat_status = ?, transfer_id = ?, referral_type = ?, referring_facility = ?, reffering_fac_client_number = ?, accompanment_type = ?, peer_edu_name = ?, peer_edu_phone = ?, rx_supporter_name = ?, rx_supporter_phone = ?, dosage = ?, current_status = ?, hcw_name = ?, hcw_sign = ? WHERE p_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssssssssssssssssssssssssssssi', $mat_id, $mat_number, $clientName, $motherName, $sname, $nickName, $nat_id, $dob, $sex, $reg_facility, $mflcode, $county, $scounty, $reg_date, $residence_scounty, $p_address, $client_phone, $mat_status, $transfer_id, $referral_type, $referring_facility, $reffering_fac_client_number, $accompanment_type, $peer_edu_name, $peer_edu_phone, $rx_supporter_name, $rx_supporter_phone, $dosage, $current_status, $hcw_name, $hcw_sign, $userId);
    $stmt->execute();
    $successMessage = "Patient Profile updated successfully";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Update</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <style>
          .grid-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-gap: 10px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .grid-item {
            grid-column: span 1;
            border: solid thin;
            padding: 20px;
            background-color: #FFE5FF;

        }
         input, select{
            width: 100%;
            margin-bottom: 10px;
            margin-top: 10px;
            height: 25px;
         }
            input[type="submit"] {
            background-color: #1A5276; /* Green background */
            color: white; /* White text */
            padding: 10px 20px; /* Padding */
            border: none; /* No border */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer; /* Cursor style */
            transition: background-color 0.3s; /* Smooth transition for background color */
        }

        /* Change the background color on hover */
        input[type="submit"]:hover {
            background-color: #45a049; /* Darker green background */
        }
          label{
              font-size: 14px;
          }
        .readonly-input{
            background-color: #94C9FF;
            cursor: not-allowed;
        }
        select[disabled] {
            background-color: #94C9FF; /* Light gray background */
            color: #495057; /* Dark text color */
            cursor: not-allowed; /* Show the cursor as not-allowed */
        }
        .submit-btn{
               height: 50px;
               font-size: 18px;
           }
        h2{
          margin-left: 40px;
        }

    </style>
</head>
<body>
    <h2>Update Patient Information</h2>
<form method="post">
    <?php if (!empty($successMessage)) { ?>
        <div style="color: green;"><?= $successMessage ?></div>
    <?php } ?>

    <?php if (!empty($errorMessage)) { ?>
        <div style="color: red;"><?= $errorMessage ?></div>
    <?php } ?>
 <div class="grid-container">
    <div class="grid-item">
    User ID: <input type="text" name="userId" value="<?= $userId ?>" class="readonly-input" readonly><br>
    <label for="mat_id">MAT ID</label> <br>
    <input type="text" name="mat_id" value="<?= $currentSettings['mat_id'] ?>" required><br>
    MAT Nummber: <input type="text" name="mat_number" value="<?= $currentSettings['mat_number'] ?>" required><br>
    Client Name: <input type="text" name="clientName" value="<?= $currentSettings['clientName'] ?>" required><br>
    Mothers' Name: <input type="text" name="motherName" value="<?= $currentSettings['motherName'] ?>" ><br>
    Sur Namer: <input type="text" name="sname" value="<?= $currentSettings['sname'] ?>" ><br>
    NickName: <input type="text" name="nickName" value="<?= $currentSettings['nickName'] ?>" ><br>


    </div>
    <div class="grid-item">
    National ID: <input type="text" name="nat_id" value="<?= $currentSettings['nat_id'] ?>" ><br>
    Date of Birth: <input type="text" name="dob" value="<?= $currentSettings['dob'] ?>" required><br>
    Sex: <input type="text" name="sex" value="<?= $currentSettings['sex'] ?>" class="readonly-input" readonly><br>
    Current Residence: <input type="text" name="p_address" value="<?= $currentSettings['p_address'] ?>" class="readonly-input" ><br>
    Enrolment Facility: <input type="text" name="reg_facility" value="<?= $currentSettings['reg_facility'] ?>" class="readonly-input" readonly><br>
    MFL Code: <input type="text" name="mflcode" value="<?= $currentSettings['mflcode'] ?>" class="readonly-input" readonly><br>

    <label>County:</label><br>
        <select name="county" class="form-control">
            <?php
            // Fetch sub county names from the database
            $sql = "SELECT county_name FROM counties";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each sub county name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['county_name'] == $currentSettings['county']) ? 'selected' : ''; // Mark the selected sub county
                    echo "<option value='" . $row['county_name'] . "' $selected>" . $row['county_name'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No counties found</option>";
            }
            ?>
        </select> <br>

    </div>
    <div class="grid-item">
    <label>Sub County:</label><br>
        <select name="scounty" class="form-control">
            <?php
            // Fetch sub county names from the database
            $sql = "SELECT sub_county_name FROM sub_counties";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each sub county name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['sub_county_name'] == $currentSettings['scounty']) ? 'selected' : ''; // Mark the selected sub county
                    echo "<option value='" . $row['sub_county_name'] . "' $selected>" . $row['sub_county_name'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No sub counties found</option>";
            }
            ?>
        </select> <br>
    Enrolment Date: <input type="text" name="reg_date" value="<?= $currentSettings['reg_date'] ?>" class="readonly-input" readonly><br>
    Residence Sub County: <input type="text" name="residence_scounty" value="<?= $currentSettings['residence_scounty'] ?>" ><br>
    Current Residence: <input type="text" name="p_address" value="<?= $currentSettings['p_address'] ?>" required><br>
    Client Phone: <input type="text" name="client_phone" value="<?= $currentSettings['client_phone'] ?>" ><br>
    Enrolment Status: <input type="text" name="mat_status" value="<?= $currentSettings['mat_status'] ?>" class="readonly-input"><br>
    <label>Enrolment Status(TI or New):</label><br>
        <select name="mat_status" class="form-control">
            <?php
            // Fetch sub county names from the database
            $sql = "SELECT sub_county_name FROM sub_counties";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each sub county name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['sub_county_name'] == $currentSettings['mat_status']) ? 'selected' : ''; // Mark the selected sub county
                    echo "<option value='" . $row['sub_county_name'] . "' $selected>" . $row['sub_county_name'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No sub counties found</option>";
            }
            ?>
        </select> <br>

    Tranfer Id: <input type="text" name="transfer_id" value="<?= $currentSettings['transfer_id'] ?>" ><br>

    </div>
    <div class="grid-item">


    <label>Referral Type:</label><br>
        <select name="referral_type" class="form-control">
            <?php
            // Fetch sub referral_type names from the database
            $sql = "SELECT referralType FROM tblreferral";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each referral_type name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['referralType'] == $currentSettings['referral_type']) ? 'selected' : ''; // Mark the selected sub referral_type
                    echo "<option value='" . $row['referralType'] . "' $selected>" . $row['referralType'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No counties found</option>";
            }
            ?>
        </select> <br>

    <label>CSO Name:</label><br>
        <select name="cso" class="form-control" disabled>
            <?php
            // Fetch sub referral_type names from the database
            $sql = "SELECT cso_name FROM tblcso";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each referral_type name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['cso_name'] == $currentSettings['cso']) ? 'selected' : ''; // Mark the selected sub referral_type
                    echo "<option value='" . $row['cso_name'] . "' $selected>" . $row['cso_name'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No counties found</option>";
            }
            ?>
        </select> <br>

    <label>Referring Facility:</label><br>
        <select name="referring_facility" class="form-control">
            <?php
            // Fetch sub referral_type names from the database
            $sql = "SELECT facility_name FROM facilities";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each referral_type name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['facility_name'] == $currentSettings['referring_facility']) ? 'selected' : ''; // Mark the selected sub referral_type
                    echo "<option value='" . $row['facility_name'] . "' $selected>" . $row['facility_name'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No counties found</option>";
            }
            ?>
        </select> <br>
    Reffering Fac Number: <input type="text" name="reffering_fac_client_number" value="<?= $currentSettings['reffering_fac_client_number'] ?>"><br>

    <label>Accompanment Type:</label><br>
        <select name="accompanment_type" class="form-control">
            <?php
            // Fetch sub referral_type names from the database
            $sql = "SELECT accompanmentType FROM tblaccompanment";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each referral_type name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['accompanmentType'] == $currentSettings['accompanment_type']) ? 'selected' : ''; // Mark the selected sub referral_type
                    echo "<option value='" . $row['accompanmentType'] . "' $selected>" . $row['accompanmentType'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No counties found</option>";
            }
            ?>
        </select> <br>
    Peer Educator Name: <input type="text" name="peer_edu_name" value="<?= $currentSettings['peer_edu_name'] ?>" ><br>
    Peer Educator Phone: <input type="text" name="peer_edu_phone" value="<?= $currentSettings['peer_edu_phone'] ?>" ><br>

    </div>
    <div class="grid-item">

    Treatment Supporter Name: <input type="text" name="rx_supporter_name" value="<?= $currentSettings['rx_supporter_name'] ?>" ><br>
    Treatment Supporter Phone: <input type="text" name="rx_supporter_phone" value="<?= $currentSettings['rx_supporter_phone'] ?>" ><br>
    Start Dosage: <input type="text" name="dosage" value="<?= $currentSettings['dosage'] ?>" required><br>

    <label>Current Status:</label><br>
        <select name="current_status" class="form-control">
            <?php
            // Fetch sub referral_type names from the database
            $sql = "SELECT status_name FROM status";
            $result = $mysqli->query($sql);

            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Loop through the rows and display each referral_type name as an option
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['status_name'] == $currentSettings['current_status']) ? 'selected' : ''; // Mark the selected sub referral_type
                    echo "<option value='" . $row['status_name'] . "' $selected>" . $row['status_name'] . "</option>";
                }
            } else {
                // If no sub counties are found in the database, display a default option
                echo "<option value=''>No counties found</option>";
            }
            ?>
        </select> <br>
    Health Care Worker: <input type="text" name="hcw_name" value="<?= $currentSettings['hcw_name'] ?>" required><br>
    Health Care Sign: <input type="text" name="hcw_sign" value="<?= $currentSettings['hcw_sign'] ?>" required><br>

    <input type="submit" name="update" class="submit-btn" value="Update">
    </div>
 </div>
</form>
<?php
    include "../includes/footer.php";
?>
</body>
</html>


