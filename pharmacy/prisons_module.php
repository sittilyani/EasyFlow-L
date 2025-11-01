<?php
session_start();
include '../includes/config.php';

$page_title = 'Routine Dispensing';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$conn->set_charset('utf8mb4');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

// Variable to track if we should redirect
$shouldRedirect = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dispensing'])) {
    $errorMessages = [];
    $successMessages = [];

    try {
        $conn->begin_transaction();

        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $pharm_officer_name = $_POST['pharm_officer_name'];

        // Get all dates in the range
        $dateRange = [];
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);

        while ($currentDate <= $endDateObj) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        // Process each patient's dispensing
        foreach ($_POST['patients'] as $mat_id => $patientData) {
            foreach ($dateRange as $date) {
                if (isset($patientData[$date]) && $patientData[$date] > 0) {
                    $dosage = (float)$patientData[$date];

                    // Get patient info
                    $patientQuery = "SELECT * FROM patients WHERE mat_id = ?";
                    $patientStmt = $conn->prepare($patientQuery);
                    $patientStmt->bind_param('s', $mat_id);
                    $patientStmt->execute();
                    $patientResult = $patientStmt->get_result();
                    $patient = $patientResult->fetch_assoc();
                    $patientStmt->close();

                    // Check if already dispensed for this date
                    $checkQuery = "SELECT * FROM pharmacy WHERE mat_id = ? AND visitDate = ?";
                    $checkStmt = $conn->prepare($checkQuery);
                    $checkStmt->bind_param('ss', $mat_id, $date);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows === 0) {
                        // Insert dispensing record
                        $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertQuery);

                        $drugname = 'Methadone'; // Default drug for prison module
                        $reasons = 'Prison bulk dispensing';

                        $stmt->bind_param('ssssssssssssss',
                            $date,
                            $mat_id,
                            $patient['mat_number'],
                            $patient['clientName'],
                            $patient['nickName'],
                            $patient['age'],
                            $patient['sex'],
                            $patient['p_address'],
                            $patient['cso'],
                            $drugname,
                            $dosage,
                            $reasons,
                            $patient['current_status'],
                            $pharm_officer_name);

                        if ($stmt->execute()) {
                            // Update stock
                            $stockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                            $stockStmt = $conn->prepare($stockQuery);
                            $stockStmt->bind_param('ds', $dosage, $drugname);
                            $stockStmt->execute();
                            $stockStmt->close();

                            $successMessages[] = "Dispensed $dosage mg of $drugname to {$patient['clientName']} (MAT ID: $mat_id) for $date";
                        } else {
                            $errorMessages[] = "Error dispensing to {$patient['clientName']} for $date";
                        }
                        $stmt->close();
                    } else {
                        $errorMessages[] = "{$patient['clientName']} (MAT ID: $mat_id) already dispensed for $date";
                    }
                    $checkStmt->close();
                }
            }
        }

        $conn->commit();

        // Set redirect flag if there are success messages and no errors
        if (!empty($successMessages) && empty($errorMessages)) {
            $shouldRedirect = true;
        }

        // Display success/error messages
        if (!empty($successMessages)) {
            echo '<div class="alert alert-success">';
            echo '<h4>Successfully Dispensed:</h4>';
            echo '<ul>';
            foreach ($successMessages as $msg) {
                echo '<li>' . $msg . '</li>';
            }
            echo '</ul>';
            if ($shouldRedirect) {
                echo '<p><strong>Redirecting to dispensing page in 2 seconds...</strong></p>';
            }
            echo '</div>';
        }

        if (!empty($errorMessages)) {
            echo '<div class="alert alert-danger">';
            echo '<h4>Errors:</h4>';
            echo '<ul>';
            foreach ($errorMessages as $msg) {
                echo '<li>' . $msg . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo '<div class="alert alert-danger">Transaction failed: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prison Module - Bulk Dispensing</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <style>


        .header {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .date-range-form {
            margin-bottom: 30px;
        }

        .prison-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .prison-table th, .prison-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .prison-table th {
            background-color: #2C3162;
            color: white;
        }

        .prison-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .dosage-input {
            width: 60px;
            text-align: center;
        }

        .submit-btn {
            background-color: #2C3162;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #1a1d3d;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .remove-btn:hover {
            background-color: #c82333;
        }

        .alert ul {
            margin-bottom: 0;
        }
    </style>

    <?php if ($shouldRedirect): ?>
    <script>
        // Redirect after 2 seconds
        setTimeout(function() {
            window.location.href = 'dispensing.php';
        }, 2000);
    </script>
    <?php endif; ?>
</head>
<body>
    <h2 style="color: #2C3162;">Prison Module - Bulk Dispensing</h2>

    <div class="date-range-form">
        <form method="GET" action="">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'); ?>">

                <label for="end_date" style="margin-left: 20px;">End Date:</label>
                <input type="date" id="end_date" name="end_date" required
                       value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+6 days')); ?>">

                <button type="submit" class="submit-btn" style="margin-left: 20px;">Generate List</button>
            </div>
        </form>
    </div>

    <?php
    if (isset($_GET['start_date'])) {
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];

        // Validate dates
        if (strtotime($startDate) > strtotime($endDate)) {
            echo '<div class="alert alert-danger">End date must be after start date.</div>';
        } else {
            // Get all dates in the range
            $dateRange = [];
            $currentDate = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);

            while ($currentDate <= $endDateObj) {
                $dateRange[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }

            // Get all inmates
            $inmatesQuery = "SELECT * FROM patients WHERE p_address LIKE '%inmate%' AND current_status IN ('Active', 'LTFU', 'Defaulted')";
            $inmatesResult = $conn->query($inmatesQuery);

            if ($inmatesResult && $inmatesResult->num_rows > 0) {
                echo '<form method="POST" action="">';
                echo '<input type="hidden" name="start_date" value="' . $startDate . '">';
                echo '<input type="hidden" name="end_date" value="' . $endDate . '">';

                echo '<div class="form-group" style="margin-bottom: 20px;">';
                echo '<label for="pharm_officer_name">Pharmacy Officer Name:</label>';
                echo '<input type="text" id="pharm_officer_name" name="pharm_officer_name" required>';
                echo '</div>';

                echo '<table class="prison-table">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>p_ID</th>';
                echo '<th>MAT ID</th>';
                echo '<th>Client Name</th>';
                echo '<th>Sex</th>';
                echo '<th>Physical Address</th>';

                // Add date columns
                foreach ($dateRange as $date) {
                    echo '<th>' . date('m/d/Y', strtotime($date)) . '</th>';
                }

                echo '<th>Current Status</th>';
                echo '<th>History</th>';
                echo '<th>Action</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ($inmate = $inmatesResult->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $inmate['p_id'] . '</td>';
                    echo '<td>' . $inmate['mat_id'] . '</td>';
                    echo '<td>' . $inmate['clientName'] . '</td>';
                    echo '<td>' . $inmate['sex'] . '</td>';
                    echo '<td>' . $inmate['p_address'] . '</td>';

                    // Add dosage inputs for each date
                    foreach ($dateRange as $date) {
                        echo '<td>';
                        echo '<input type="number" class="dosage-input" name="patients[' . $inmate['mat_id'] . '][' . $date . ']"
                              value="' . $inmate['dosage'] . '" min="0" step="0.5">';
                        echo '</td>';
                    }

                    echo '<td>' . $inmate['current_status'] . '</td>';
                    echo '<td>';
                    echo '<center>';
                    echo '<a href="history.php?p_id=' . $inmate['p_id'] . '" style="font-size: 24px; color: brown;"><i class="fa fa-exclamation-circle"></i></a>';
                    echo '</center>';
                    echo '</td>';
                    echo '<td>';
                    echo '<a href="view-missed.php?mat_id=' . $inmate['mat_id'] . '">View</a> | ';
                    echo '<a href="dispensingData.php?mat_id=' . $inmate['mat_id'] . '">DISPENSE</a> | ';
                    echo '<a href="../referrals/referral.php?mat_id=' . $inmate['mat_id'] . '">Refer</a> | ';
                    echo '<button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';

                echo '<button type="submit" name="submit_dispensing" class="submit-btn">Submit Bulk Dispensing</button>';
                echo '</form>';
            } else {
                echo '<div class="alert alert-info">No inmates found in the database.</div>';
            }
        }
    }
    ?>

    <script>
        function removeRow(btn) {
            var row = btn.closest('tr');
            row.style.display = 'none';

            // Disable all inputs in the row so they won't be submitted
            var inputs = row.getElementsByTagName('input');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].disabled = true;
            }
        }

        // Set default end date to 6 days after start date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            var startDate = new Date(this.value);
            var endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 6);

            // Format the date as YYYY-MM-DD
            var endDateStr = endDate.toISOString().split('T')[0];
            document.getElementById('end_date').value = endDateStr;
        });
    </script>
</body>
</html>