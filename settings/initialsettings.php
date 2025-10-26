<?php
// Start the session and include your database connection
session_start();
include('../includes/config.php'); // adjust path as needed

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $facility_id = $_POST['facility_id'] ?? '';
    $mflcode = $_POST['mflcode'] ?? '';
    $countyname = $_POST['countyname'] ?? '';
    $subcountyname = $_POST['subcountyname'] ?? '';
    $owner = $_POST['owner'] ?? '';
    $sdp = $_POST['sdp'] ?? '';
    $agency = $_POST['agency'] ?? '';
    $emr = $_POST['emr'] ?? '';
    $emrstatus = $_POST['emrstatus'] ?? '';
    $infrastructuretype = $_POST['infrastructuretype'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $facilityincharge = $_POST['facilityincharge'] ?? '';
    $facilityphone = $_POST['facilityphone'] ?? '';
    $email = $_POST['email'] ?? '';

    // Basic validation
    if (empty($facility_id) || empty($facilityincharge) || empty($facilityphone) || empty($email)) {
        $error_message = "Please fill all required fields.";
    } else {
        // Optional: Delete any existing setup for this facility
        $delete_sql = "DELETE FROM facility_settings WHERE facility_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $facility_id);
        $stmt->execute();
        $stmt->close();

        // Insert the new setup record
        $insert_sql = "INSERT INTO facility_settings (
            facility_id, mflcode, countyname, subcountyname, owner, sdp, agency, emr, emrstatus,
            infrastructuretype, latitude, longitude, facilityincharge, facilityphone, email, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($insert_sql);
        if ($stmt === false) {
            $error_message = "Database error: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param(
                "issssssssssssss",
                $facility_id,
                $mflcode,
                $countyname,
                $subcountyname,
                $owner,
                $sdp,
                $agency,
                $emr,
                $emrstatus,
                $infrastructuretype,
                $latitude,
                $longitude,
                $facilityincharge,
                $facilityphone,
                $email
            );

            if ($stmt->execute()) {
                $success_message = "Facility setup has been saved successfully.";
            } else {
                $error_message = "Failed to save setup: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}

// Re-fetch facility details for the dropdown list and pre-fill (optional)
$current_setup_facility_id = $facility_id ?? '';
$current_mflcode = $mflcode ?? '';
$current_county = $countyname ?? '';
$current_subcounty = $subcountyname ?? '';
$current_owner = $owner ?? '';
$current_sdp = $sdp ?? '';
$current_agency = $agency ?? '';
$current_emr = $emr ?? '';
$current_emr_status = $emrstatus ?? '';
$current_infrastructure_type = $infrastructuretype ?? '';
$current_latitude = $latitude ?? '';
$current_longitude = $longitude ?? '';
$current_setup_incharge = $facilityincharge ?? '';
$current_setup_phone = $facilityphone ?? '';
$current_setup_email = $email ?? '';

// Include your HTML form

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Setup</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;}
        .form-container {background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); width: 100%; max-width: 960px;}
        .form-grid {display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 30px;}
        .form-group {margin-bottom: 0;}
        label {font-weight: bold; color: #333; display: block; margin-bottom: 5px;}
        input[type="text"], input[type="email"], input[type="tel"], select {width: 100%; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 1rem; transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;}
        input[type="text"]:focus, input[type="email"]:focus, input[type="tel"]:focus, select:focus {border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);}
        input[readonly] {background-color: #e9ecef; cursor: not-allowed;}
        button[type="submit"] {grid-column: 1 / -1; background-color: #007bff; color: white; border: none; border-radius: 5px; height: 45px; font-size: 1.1rem; cursor: pointer; transition: background-color 0.3s ease; margin-top: 20px;}
        button[type="submit"]:hover {background-color: #0056b3;}
        .message {width: 100%; padding: 15px; text-align: center; font-weight: bold; border-radius: 5px; margin-bottom: 20px; box-sizing: border-box;}
        .success {background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .error {background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .note-message {grid-column: 1 / -1; text-align: center; margin-top: 15px; font-style: italic; color: #666; font-size: 0.9em;}
        @media (max-width: 992px) {.form-grid {grid-template-columns: repeat(2, 1fr);}}
        @media (max-width: 768px) {.form-grid {grid-template-columns: 1fr;} button[type="submit"], .note-message {grid-column: auto;}}
    </style>
</head>
<body>
    <div class="form-container">
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="post" action="initialsettings.php">
            <div class="form-grid">
                <div class="form-group">
                    <label for="facilityname">Select Facility Name:</label>
                    <select name="facilityname" id="facilityname" required>
                        <option value="">-- Select Facility --</option>
                        <?php
                        if (isset($conn) && $conn instanceof mysqli) {
                           $result = $conn->query("SELECT id, facilityname FROM facilities WHERE facilityname LIKE '%MAT%' ");
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($current_setup_facility_id == $row['id']) ? 'selected' : '';
                                    echo "<option value='{$row['id']}' {$selected}>{$row['facilityname']}</option>";
                                }
                            } else {
                                echo "<option value=''>No MAT facilities found</option>";
                            }
                           }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mflcode">MFL Code:</label>
                    <input type="text" name="mflcode" id="mflcode" value="<?php echo htmlspecialchars($current_mflcode); ?>">
                </div>

                <div class="form-group">
                    <label for="countyname">County Name:</label>
                    <input type="text" name="countyname" id="countyname" readonly value="<?php echo htmlspecialchars($current_county); ?>">
                </div>

                <div class="form-group">
                    <label for="subcountyname">Sub-County Name:</label>
                    <input type="text" name="subcountyname" id="subcountyname" readonly value="<?php echo htmlspecialchars($current_subcounty); ?>">
                </div>

                <div class="form-group">
                    <label for="owner">Owner:</label>
                    <input type="text" name="owner" id="owner" readonly value="<?php echo htmlspecialchars($current_owner); ?>">
                </div>

                <div class="form-group">
                    <label for="sdp">SDP:</label>
                    <input type="text" name="sdp" id="sdp" readonly value="<?php echo htmlspecialchars($current_sdp); ?>">
                </div>

                <div class="form-group">
                    <label for="agency">Agency:</label>
                    <input type="text" name="agency" id="agency" readonly value="<?php echo htmlspecialchars($current_agency); ?>">
                </div>

                <div class="form-group">
                    <label for="emr">EMR:</label>
                    <input type="text" name="emr" id="emr" readonly value="<?php echo htmlspecialchars($current_emr); ?>">
                </div>

                <div class="form-group">
                    <label for="emrstatus">EMR Status:</label>
                    <input type="text" name="emrstatus" id="emrstatus" readonly value="<?php echo htmlspecialchars($current_emr_status); ?>">
                </div>

                <div class="form-group">
                    <label for="infrastructuretype">Infrastructure Type:</label>
                    <input type="text" name="infrastructuretype" id="infrastructuretype" readonly value="<?php echo htmlspecialchars($current_infrastructure_type); ?>">
                </div>

                <div class="form-group">
                    <label for="latitude">Latitude:</label>
                    <input type="text" name="latitude" id="latitude" readonly value="<?php echo htmlspecialchars($current_latitude); ?>">
                </div>

                <div class="form-group">
                    <label for="longitude">Longitude:</label>
                    <input type="text" name="longitude" id="longitude" readonly value="<?php echo htmlspecialchars($current_longitude); ?>">
                </div>

                <div class="form-group">
                    <label for="facilityincharge">Name of Facility Incharge:</label>
                    <input type="text" name="facilityincharge" id="facilityincharge" required value="<?php echo htmlspecialchars($current_setup_incharge); ?>">
                </div>

                <div class="form-group">
                    <label for="facilityphone">Facility Phone:</label>
                    <input type="tel" name="facilityphone" id="facilityphone" required value="<?php echo htmlspecialchars($current_setup_phone); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($current_setup_email); ?>">
                </div>

                <div class="form-group note-message">
                    <p>Please be sure because this will replace any existing facility setup.</p>
                </div>

                <button type="submit">Save and Submit</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const facilitySelect = document.getElementById('facility_id');
            const fieldsToPopulate = {
                'mflcode': 'mflcode',
                'countyname': 'countyname',
                'subcountyname': 'subcountyname',
                'owner': 'owner',
                'sdp': 'sdp',
                'agency': 'agency',
                'emr': 'emr',
                'emrstatus': 'emrstatus',
                'infrastructuretype': 'infrastructuretype',
                'latitude': 'latitude',
                'longitude': 'longitude'
            };

            facilitySelect.addEventListener('change', function() {
                var facilityId = this.value;
                if (facilityId) {
                    fetch('fetch_facility_details.php?id=' + facilityId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                // Clear all fields
                                Object.keys(fieldsToPopulate).forEach(field => {
                                    document.getElementById(field).value = '';
                                });
                            } else {
                                // Populate all readonly fields
                                Object.keys(fieldsToPopulate).forEach(field => {
                                    const element = document.getElementById(field);
                                    if (element) {
                                        element.value = data[fieldsToPopulate[field]] || '';
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching facility details:', error);
                            alert('An error occurred while fetching facility details.');
                        });
                } else {
                    // Clear all fields if no facility selected
                    Object.keys(fieldsToPopulate).forEach(field => {
                        document.getElementById(field).value = '';
                    });
                }
            });
        });
    </script>
</body>
</html>