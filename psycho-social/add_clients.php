<?php
session_start();

include('../includes/config.php');
include '../includes/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Define expected fields
    $fields = [
        'reg_facility', 'mflcode', 'county', 'scounty', 'mat_id', 'mat_number', 'clientName',
        'sname', 'nickName', 'nat_id', 'dob', 'sex', 'marital_status', 'residence_scounty',
        'p_address', 'client_phone', 'mat_status', 'transfer_id', 'referral_type',
        'referring_facility', 'reffering_fac_client_number', 'accompanment_type',
        'peer_edu_name', 'peer_edu_phone', 'rx_supporter_name', 'dosage', 'reasons',
        'current_status', 'hcw_name'
    ];

    // Validate and sanitize input
    $formData = [];
    foreach ($fields as $field) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    // Calculate age based on date of birth
    $dob_timestamp = strtotime($formData['dob']);
    $current_timestamp = time();
    $formData['age'] = date('Y', $current_timestamp) - date('Y', $dob_timestamp);
    if (date('md', $current_timestamp) < date('md', $dob_timestamp)) {
        $formData['age']--;
    }

    // Prepare and execute SQL query to insert data into the database
    $sql = "INSERT INTO patients (
        reg_facility, mflcode, county, scounty, mat_id, mat_number, clientName, sname,
        nickName, nat_id, dob, age, sex, marital_status, residence_scounty, p_address,
        client_phone, mat_status, transfer_id, referral_type, referring_facility,
        reffering_fac_client_number, accompanment_type, peer_edu_name, peer_edu_phone,
        rx_supporter_name, drugname, dosage, reasons, current_status, hcw_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {

    $formData['drugname'] = trim($_POST['drugname'] ?? 'buprenorphine');

    $stmt->bind_param(
            "sssssssssssssssssssssssssssssss",
            $formData['reg_facility'],
            $formData['mflcode'],
            $formData['county'],
            $formData['scounty'],
            $formData['mat_id'],
            $formData['mat_number'],
            $formData['clientName'],
            $formData['sname'],
            $formData['nickName'],
            $formData['nat_id'],
            $formData['dob'],
            $formData['age'],
            $formData['sex'],
            $formData['marital_status'],
            $formData['residence_scounty'],
            $formData['p_address'],
            $formData['client_phone'],
            $formData['mat_status'],
            $formData['transfer_id'],
            $formData['referral_type'],
            $formData['referring_facility'],
            $formData['reffering_fac_client_number'],
            $formData['accompanment_type'],
            $formData['peer_edu_name'],
            $formData['peer_edu_phone'],
            $formData['rx_supporter_name'],
            $formData['drugname'],
            $formData['dosage'],
            $formData['reasons'],
            $formData['current_status'],
            $formData['hcw_name']
        );

        if ($stmt->execute()) {
            echo "<div id='successmessage' style='background: #b8fcdf; height: 30px; font-style: italic;'>New client added successfully</div>";
            echo "<script>
                setTimeout(function() {
                    var element = document.getElementById('successmessage');
                    if (element) element.parentNode.removeChild(element);
                }, 3000);
            </script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
}
?>

<?php
include "../includes/config.php";

$host = 'localhost';
$db = 'buprenorphine';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

?>

<?php
// Existing includes and configurations
include "../includes/config.php";

ob_start(); // Ensure sessions are started if not started already
$host = 'localhost';
$db = 'buprenorphine';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$hcw_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $mysqli->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $hcw_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Initial Patient Registration</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.3.min.js"></script>
    <style>
    :root {
            --primary-color: #0056b3; /* Darker blue for primary actions */
            --secondary-color: #6c757d; /* Grey for secondary elements */
            --background-light: #f8f9fa; /* Light background for overall page */
            --card-background: #ffffff; /* White for form background */
            --border-color: #dee2e6;
            --success-color: #28a745;
            --success-bg-color: #d4edda;
            --text-color: #343a40;
            --input-border: #ced4da;
            --input-focus-border: #80bdff;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --font-family: 'Arial', sans-serif; /* Changed from Times New Roman for a modern look */
        }
        .main-content {
            padding: 20px;
            max-width:90%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        #success-message {
            background-color: var(--success-bg-color);
            color: var(--success-color);
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px solid var(--success-color);
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        #success-message .fas {
            font-size: 1.2em;
        }

        form {
            display: grid;
            grid-template-columns: repeat(5, 1fr); /* Three equal columns */
            gap: 25px; /* Spacing between columns and rows */
            padding: 20px;
            background-color: #FFFFE0; /* Original light yellow background */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in width */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .readonly-input {
            background-color: #e9ecef; /* Light gray for readonly fields */
            cursor: not-allowed;
        }

        .custom-submit-btn {
            grid-column: 1 / -1; /* Make the button span all three columns */
            padding: 15px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-submit-btn:hover {
            background-color: #004085; /* Darker shade on hover */
            transform: translateY(-2px); /* Slight lift effect */
        }

        .custom-submit-btn:active {
            transform: translateY(0);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            form {
                grid-template-columns: repeat(2, 1fr); /* Two columns on medium screens */
            }
            .custom-submit-btn {
                grid-column: 1 / -1; /* Still span full width */
            }
        }

        @media (max-width: 768px) {
            form {
                grid-template-columns: 1fr; /* Single column on small screens */
            }
        }
    </style>

</head>
<body>
    <div class="main-content">
        <div class="form-group"><h2>Add new client</h2></div>

    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
      <div class="grid-group">
        <label for="reg_facility">Facility</label><br>
        <select id="reg_facility" name="reg_facility" required>
            <option value="">Select Facility</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM facilities";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['facilityname'] . "'>" . $row['facilityname'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select> <br>

        <label for="mflcode">MFL code</label><br>
        <input type="text" id="mflcode" name="mflcode" pattern="[1-9]\d{0,4}" title="MFL code must not start with 0 and have a maximum of 5 digits." required>

        <label for="county">County</label><br>
        <select id="county" name="county" required>
            <option value="">Select County</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM counties";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['county_name'] . "'>" . $row['county_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select> <br>

        <label for="scounty">Sub County</label><br>
        <select id="scounty" name="scounty" required>
            <option value="">Select Sub County</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM sub_counties";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['sub_county_name'] . "'>" . $row['sub_county_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select> <br>

        <!--<label for="reg_date">Registration Date</label> <br>
            <input type="date" id="reg_date" name="reg_date" required>
        <label for="reg_time">Time</label> <br>
            <input type="time" id="reg_time" name="reg_time" required>-->
        <label for="mat_id">Unique (MAT) ID</label> <br>
            <input type="text" id="mat_id" name="mat_id" required>
        <label for="mat_number">MAT Number</label> <br>
            <input type="text" id="mat_number" name="mat_number">

      </div>
        <div class="grid-item">

        <label for="clientName">Client Name</label> <br>
            <input type="text" id="clientName" name="clientName" required>


        <label for="sname">SurName</label> <br>
            <input type="text" id="sname" name="sname">
        <label for="nickName">Nick Name</label> <br>
            <input type="text" id="nickName" name="nickName" >
        <label for="nat_id">National ID/PPNo.</label>  <br>
            <input type="text" id="nat_id" name="nat_id" >
        <label for="dob">Date of Birth</label> <br>
        <input type="date" id="dob" name="dob" required onchange="calculateAge()">

        <label for="sex">Sex</label> <br>
        <select id="sex" name="sex" required>
            <option value="">Select Sex</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM tblgender";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['gender_name'] . "'>" . $row['gender_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>

        <div class="grid-item">


        <label for="marital_status">Marital Status</label><br>
        <select id="marital_status" name="marital_status" required>
            <option value="">Select marital status</option>
            <?php
            include('../includes/config.php');

            // Fetch marital status from the database
            $sql = "SELECT * FROM marital_status";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['marital_status_name'] . "'>" . $row['marital_status_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>

        <label for="residence_scounty">Residence Sub County</label>  <br>
            <input type="text" id="residence_scounty" name="residence_scounty" > <br>
        <label for="p_address">Physical Address (residence)</label>  <br>
            <input type="text" id="p_address" name="p_address" >
        <label for="client_phone">Phone Number</label> <br>
            <input type="number" id="client_phone" name="client_phone" pattern="0\d{9}" title="Phone number must start with 0 and have 10 digits.">

        <label for="mat_status">Have you ever received MAT services elsewhere?</label><br>
        <select id="mat_status" name="mat_status" required>
            <option value="">Select enrolment status</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM enrolment_status";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['enrolment_status_name'] . "'>" . $row['enrolment_status_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>

        <label for="transfer_id">Transfer in MAT ID</label> <br>
            <input type="text" id="transfer_id" name="transfer_id">



        </div>
        <div class="grid-item">
        <label for="referral_type">Referral Type</label> <br>
        <select id="referral_type" name="referral_type" required>
            <option value="">Select Referral Type</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM tblreferral";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['referralType'] . "'>" . $row['referralType'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>


        <label for="cso">CSO</label><br>
        <select id="cso" name="cso" required>
            <option value="">Select CSO</option>
            <?php
            include('../includes/config.php');

            // Fetch CSO from the database
            $sql = "SELECT * FROM tblcso";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['cso_name'] . "'>" . $row['cso_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>  <br>
        <label for="referring_facility">Referring Facility</label><br>
        <select id="referring_facility" name="referring_facility">
            <option value="">Select Referring Facility</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM facilities";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['facilityname'] . "'>" . $row['facilityname'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select> <br>

        <label for="reffering_fac_client_number">Referring Facility Clients Registration Number</label> <br>
            <input type="text" id="reffering_fac_client_number" name="reffering_fac_client_number">

        <label for="accompanment_type">Accompanied By</label><br>
        <select id="accompanment_type" name="accompanment_type" required>
            <option value="">Select accompanment</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM tblaccompanment";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['accompanmentType'] . "'>" . $row['accompanmentType'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>  <br>
        <label for="peer_edu_name">Peer Educator's/ORW Name</label> <br>
            <input type="text" id="peer_edu_name" name="peer_edu_name">
        <label for="peer_edu_phone">Peer Educator's/ORW phone number</label> <br>
            <input type="number" id="peer_edu_phone" name="peer_edu_phone" pattern="0\d{9}" title="Phone number must start with 0 and have 10 digits.">

        </div>

        <div class="grid-item">
        <label for="rx_supporter_name">Treatment supporter's name</label> <br>
            <input type="text" id="rx_supporter_name" name="rx_supporter_name" >

        <label for="drugname">Drug</label> <br>
            <select id="drugname" name="drugname" required>
                <!--<option value="">Select current status</option> -->
                <?php
                include('../includes/config.php');

                // Fetch drug names from the database
                $sql = "SELECT * FROM drug";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Use htmlspecialchars to prevent potential XSS attacks
                        echo "<option value='" . htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8') . "</option>";
                    }
                } else {
                    echo "<option value=''>No drugs available</option>";
                }

                $conn->close();
                ?>
            </select> <br>


        <label for="dosage">Dosage</label> <br>
            <input type="text" id="dosage" name="dosage" required>

        <label for="reasons">Reasons</label> <br>
            <input type="text" id="reasons" name="reasons" required>

        <label for="current_status">Current Status</label><br>
        <select id="current_status" name="current_status" required>
            <!--<option value="">Select current status</option> -->
            <?php
            include('../includes/config.php');

            // Fetch status from the database
            $sql = "SELECT * FROM status";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['status_name'] . "'>" . $row['status_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>  <br>

        <label for="hcw_name">Name of service provider</label><br>
        <input type="text" name="hcw_name" value="<?php echo htmlspecialchars($hcw_name); ?>" class="readonly-input readonly"><br>

        <div class="form-group">
            <input type="submit" class="custom-submit-btn" value="Submit">
        </div>

        </div>
    </form>
    </div>
</body>

</html>
