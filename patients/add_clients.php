<?php
session_start();

// 1. Include the config file ONCE at the top
include('../includes/config.php');

// Initialize $conn and check if it's available before proceeding to use it in other blocks
if (!isset($conn) || $conn->connect_error) {
    // Handle database connection error if necessary
    // die("Connection failed: " . $conn->connect_error);
}

// --- PHP Processing Block for Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Define expected fields (including reg_date)
    $fields = [
        'reg_facility', 'mflcode', 'county', 'scounty', 'mat_id', 'mat_number', 'clientName',
        'sname', 'nickName', 'nat_id', 'dob', 'sex', 'marital_status', 'residence_scounty',
        'p_address', 'client_phone', 'mat_status', 'transfer_id', 'referral_type',
        'referring_facility', 'reffering_fac_client_number', 'accompanment_type',
        'peer_edu_name', 'peer_edu_phone', 'rx_supporter_name', 'dosage', 'reasons',
        'current_status', 'next_appointment', 'hcw_name', 'reg_date' // Correctly included
    ];

    // Validate and sanitize input
    $formData = [];
    foreach ($fields as $field) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    // --- START: SERVER-SIDE VALIDATION ---
    $dob_date = new DateTime($formData['dob']);
    $reg_date = new DateTime($formData['reg_date']);
    $cutoff_date = new DateTime('-15 years'); // Calculates the date 15 years ago

    $validation_error = '';

    // Check 1: DOB must be before the 15-year cutoff date (client must be >= 15 years old)
    if ($dob_date >= $cutoff_date) {
        $validation_error = "Client's Date of Birth must be before " . $cutoff_date->format('Y-m-d') . " (Client must be at least 15 years old).";
    }

    // Check 2: Registration date cannot be before Date of Birth
    if (empty($validation_error) && $reg_date < $dob_date) {
        $validation_error = "Registration Date cannot be before the Date of Birth.";
    }

    if (!empty($validation_error)) {
        echo "<div id='errormessage' style='background: #ffcccc; color: red; height: 30px; font-style: italic; padding: 5px;'>Validation Error: $validation_error</div>";
        // Do NOT proceed with insertion
    } else {
        // --- PROCEED WITH INSERTION ONLY IF VALIDATION PASSES ---

        // Calculate age based on date of birth
        $dob_timestamp = $dob_date->getTimestamp();
        $current_timestamp = time();
        $formData['age'] = date('Y', $current_timestamp) - date('Y', $dob_timestamp);
        if (date('md', $current_timestamp) < date('md', $dob_timestamp)) {
            $formData['age']--;
        }

        // Set drugname
        $formData['drugname'] = trim($_POST['drugname'] ?? 'Methadone');

        // Prepare and execute SQL query to insert data into the database
        $sql = "INSERT INTO patients (
            reg_facility, mflcode, county, scounty, reg_date, mat_id, mat_number, clientName, sname,
            nickName, nat_id, dob, age, sex, marital_status, residence_scounty, p_address,
            client_phone, mat_status, transfer_id, referral_type, referring_facility,
            reffering_fac_client_number, accompanment_type, peer_edu_name, peer_edu_phone,
            rx_supporter_name, drugname, reasons, current_status, hcw_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt) {

            // 33 parameters in total (32 string fields + 1 integer field for 'age')
            $type_string = "ssssssssssssissssssssssssssssss";

            $stmt->bind_param(
                $type_string,
                $formData['reg_facility'],
                $formData['mflcode'],
                $formData['county'],
                $formData['scounty'],
                $formData['reg_date'], // Correctly captured
                $formData['mat_id'],
                $formData['mat_number'],
                $formData['clientName'],
                $formData['sname'],
                $formData['nickName'],
                $formData['nat_id'],
                $formData['dob'],
                $formData['age'], // Bound as integer 'i'
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
                $formData['reasons'],
                $formData['current_status'],
                $formData['hcw_name']
            );

            if ($stmt->execute()) {
                echo "<div id='successmessage' style='background: #b8fcdf; height: 30px; font-style: italic; padding: 5px;'>New client added successfully</div>";
                echo "<script>
                    setTimeout(function() {
                        var element = document.getElementById('successmessage');
                        if (element) element.parentNode.removeChild(element);
                    }, 3000);
                </script>";
            } else {
                echo "Error executing statement: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }
}

// --- PHP Block for Fetching User/Settings Data (Unchanged) ---

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId && isset($conn)) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Check if the user is logged in and fetch their user_id
$hcw_name = 'Unknown';
if (isset($_SESSION['user_id']) && isset($conn)) {
    $loggedInUserId = $_SESSION['user_id'];

    // Fetch the logged-in user's name from tblusers
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hcw_name = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// --- START HTML ---
?>

<!DOCTYPE html>
<html>
<head>
    <title>Initial Patient Registration</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>

    <style>
    form {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* Five equal columns for dense form */
        gap: 15px; /* Added gap for better spacing */
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .main-content {
        font-family: Arial, Helvetica, sans-serif;
        padding: 20px;
        max-width: 95%;
        margin: 20px auto; /* Center the main content */
        background-color: var(--card-background, #fff); /* Use CSS variable or default */
        border-radius: 8px;
        box-shadow: 0 4px 10px var(--shadow-light, rgba(0,0,0,0.1));
    }
    /* Style for readonly/disabled inputs */
    .readonly-input {
        background-color: #f0f0f0;
        cursor: not-allowed;
    }
    </style>

</head>
<body>
    <div class="main-content">
        <div class="form-group"><h2>Initial client registration form</h2></div>

    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" onsubmit="return validateDates()">

        <div class="form-group">
            <label for="reg_facility">Facility <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="reg_facility" name="reg_facility" required>
                <option value="">Select Facility</option>
                <?php
                // Fetch facilities and required details for data attributes
                // Rely on the single connection opened at the top
                if (isset($conn)) {
                    $sql = "SELECT facilityname, mflcode, countyname, subcountyname FROM facilities WHERE facilityname LIKE '%MAT clinic%' ORDER BY facilityname ASC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Store MFL, County, and SubCounty data in the option's data attributes
                            echo "<option
                                        value='" . htmlspecialchars($row['facilityname']) . "'
                                        data-mfl='" . htmlspecialchars($row['mflcode']) . "'
                                        data-county='" . htmlspecialchars($row['countyname']) . "'
                                        data-scounty='" . htmlspecialchars($row['subcountyname']) . "'
                                    >" . htmlspecialchars($row['facilityname']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="mflcode">MFL code <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="mflcode" name="mflcode" required class="readonly-input" readonly>
        </div>

        <div class="form-group">
            <label for="county">County <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="county" name="county" required class="readonly-input" readonly>
        </div>

        <div class="form-group">
            <label for="scounty">Sub County <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="scounty" name="scounty" required class="readonly-input" readonly>
        </div>

        <div class="form-group">
            <label for="reg_date">Registration Date<span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="date" id="reg_date" name="reg_date" required>
        </div>

        <div class="form-group">
            <label for="mat_id">Unique (MAT) ID <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="mat_id" name="mat_id" required>
        </div>
        <div class="form-group">
            <label for="mat_number">MAT Number</label>
            <input type="text" id="mat_number" name="mat_number">
        </div>
        <div class="form-group">
            <label for="clientName">Client Name<span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="clientName" name="clientName" placeholder="(e.g John Doe)" required>
        </div>
        <div class="form-group">
            <label for="sname">SurName</label>
            <input type="text" id="sname" name="sname">
        </div>
        <div class="form-group">
            <label for="nickName">Nick Name</label>
            <input type="text" id="nickName" name="nickName" >
        </div>
        <div class="form-group">
            <label for="nat_id">National ID/PPNo.</label>
            <input type="text" id="nat_id" name="nat_id" >
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="date" id="dob" name="dob" required>
        </div>

        <div class="form-group">
            <label for="sex">Sex <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="sex" name="sex" required>
                <option value="">Select Sex</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT gender_name FROM tblgender";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['gender_name']) . "'>" . htmlspecialchars($row['gender_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="marital_status">Marital Status</label>
            <select id="marital_status" name="marital_status">
                <option value="">Select marital status</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT marital_status_name FROM marital_status";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['marital_status_name']) . "'>" . htmlspecialchars($row['marital_status_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="residence_scounty">Residence Sub County</label>
            <input type="text" id="residence_scounty" name="residence_scounty" >
        </div>
        <div class="form-group">
            <label for="p_address">Physical Address (residence)</label>
            <input type="text" id="p_address" name="p_address" placeholder="Inmate: if in PRISON">
        </div>
        <div class="form-group">
            <label for="client_phone">Phone Number</label>
            <input type="number" id="client_phone" name="client_phone" pattern="0\d{9}" title="Phone number must start with 0 and have 10 digits.">
        </div>

        <div class="form-group">
            <label for="mat_status">Status at enrolment? <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="mat_status" name="mat_status" required>
                <option value="">Select enrolment status</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT enrolment_status_name FROM enrolment_status";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['enrolment_status_name']) . "'>" . htmlspecialchars($row['enrolment_status_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="transfer_id">Transfer in MAT ID</label>
            <input type="text" id="transfer_id" name="transfer_id">
        </div>
        <div class="form-group">
            <label for="referral_type">Referral Type</label>
            <select id="referral_type" name="referral_type">
                <option value="">Select Referral Type</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT referralType FROM tblreferral";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['referralType']) . "'>" . htmlspecialchars($row['referralType']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="cso">CSO</label>
            <select id="cso" name="cso">
                <option value="">Select CSO</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT cso_name FROM tblcso";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['cso_name']) . "'>" . htmlspecialchars($row['cso_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="referring_facility">Referring Facility</label>
            <select id="referring_facility" name="referring_facility">
                <option value="">Select Referring Facility</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT facilityname FROM facilities";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['facilityname']) . "'>" . htmlspecialchars($row['facilityname']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="reffering_fac_client_number">Referring Facility MAT ID</label>
            <input type="text" id="reffering_fac_client_number" name="reffering_fac_client_number">
        </div>

        <div class="form-group">
            <label for="accompanment_type">Accompanied By</label>
            <select id="accompanment_type" name="accompanment_type">
                <option value="">Select accompanment</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT accompanmentType FROM tblaccompanment";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['accompanmentType']) . "'>" . htmlspecialchars($row['accompanmentType']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="peer_edu_name">Peer Educator's/ORW Name</label>
            <input type="text" id="peer_edu_name" name="peer_edu_name">
        </div>
        <div class="form-group">
            <label for="peer_edu_phone">Peer Educator's/ORW phone</label>
            <input type="number" id="peer_edu_phone" name="peer_edu_phone" pattern="0\d{9}" title="Phone number must start with 0 and have 10 digits.">
        </div>
        <div class="form-group">
            <label for="rx_supporter_name">Treatment supporter's name</label>
            <input type="text" id="rx_supporter_name" name="rx_supporter_name" >
        </div>

        <div class="form-group"  style="display: none;">
            <label for="drugname">Drug</label>
            <input type="text" name="drugname">
        </div>
        <div class="form-group" style="display: none;">
            <label for="dosage">Dosage</label>
            <input type="number" id="dosage" name="dosage" step="0.01" min="0" max="999.99">
        </div>
        <div class="form-group" style="display: none;">
            <label for="reasons">Reasons</label>
            <input type="text" id="reasons" name="reasons">
        </div>

        <div class="form-group">
            <label for="current_status">Current Status</label>
            <select id="current_status" name="current_status" required>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT status_name FROM status";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['status_name']) . "'>" . htmlspecialchars($row['status_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group" style="display: none;">
            <label for="next_appointment">Next appointment</label>
            <input type="text" name="next_appointment">
        </div>
        <div class="form-group">
            <label for="hcw_name">Name of service provider <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" name="hcw_name" value="<?php echo htmlspecialchars($hcw_name); ?>" class="readonly-input" readonly>
        </div>
        <div class="form-group">
            <input type="submit" class="custom-submit-btn" value="Submit">
        </div>
    </form>
</div>

<?php
// Only close the connection once, at the very end of the file.
if (isset($conn)) {
    // If the connection was used and not closed inside the POST block, close it here.
    // If you plan to use $conn in the footer/scripts, keep it open, but generally, close it.
    // Since the main logic is done, we close it now.
    $conn->close();
}
?>
<script src="../assets/js/bootstrap.bundle.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>

<script>
    // --- CLIENT-SIDE DATE VALIDATION FUNCTION ---
    function validateDates() {
        const dobInput = document.getElementById('dob');
        const regDateInput = document.getElementById('reg_date');

        if (!dobInput.value || !regDateInput.value) {
            // Let the 'required' attribute handle empty fields
            return true;
        }

        const dob = new Date(dobInput.value);
        const regDate = new Date(regDateInput.value);
        const today = new Date();

        // Calculate the cutoff date: 15 years ago from today
        const cutoffDate = new Date(today.getFullYear() - 15, today.getMonth(), today.getDate());

        // 1. Check if DOB is before 15 years from today (Client must be >= 15 years old)
        if (dob >= cutoffDate) {
            const cutoffStr = cutoffDate.toISOString().split('T')[0];
            alert(`Validation Error: Client must be at least 15 years old. Date of Birth must be before ${cutoffStr}.`);
            dobInput.focus();
            return false;
        }

        // 2. Check if Registration date is before Date of Birth
        if (regDate < dob) {
            alert('Validation Error: Registration Date cannot be before the Date of Birth.');
            regDateInput.focus();
            return false;
        }

        return true; // All client-side checks passed
    }
    // --- END CLIENT-SIDE DATE VALIDATION FUNCTION ---


    document.addEventListener('DOMContentLoaded', function() {
        // Get the facility select element
        const facilitySelect = document.getElementById('reg_facility');
        const mflcodeInput = document.getElementById('mflcode');
        const countyInput = document.getElementById('county');
        const scountyInput = document.getElementById('scounty');

        // Set the max date for DOB to the cutoff date (15 years ago)
        const today = new Date();
        const maxDobDate = new Date(today.getFullYear() - 15, today.getMonth(), today.getDate());
        document.getElementById('dob').max = maxDobDate.toISOString().split('T')[0];

        // Set the max date for Registration Date to today
        document.getElementById('reg_date').max = today.toISOString().split('T')[0];


        // Listener for facility change
        facilitySelect.addEventListener('change', function() {
            // Get the selected option element
            const selectedOption = this.options[this.selectedIndex];

            if (selectedOption.value) {
                // Retrieve data attributes from the selected option
                const mfl = selectedOption.getAttribute('data-mfl');
                const county = selectedOption.getAttribute('data-county');
                const scounty = selectedOption.getAttribute('data-scounty');

                // Populate the readonly fields
                mflcodeInput.value = mfl || '';
                countyInput.value = county || '';
                scountyInput.value = scounty || '';
            } else {
                // Clear the fields if "Select Facility" is chosen
                mflcodeInput.value = '';
                countyInput.value = '';
                scountyInput.value = '';
            }
        });

        // This is the correct, simple method for auto-populating fields
        // using data already loaded in the HTML.
    });
</script>
</body>
</html>