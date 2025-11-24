<?php
session_start();
include '../includes/config.php';

// Check if user is logged in to get prescriber name
$prescriber_name = $_SESSION['full_name'] ?? 'Guest';

// Retrieve mat_id from the URL
$mat_id = $_GET['mat_id'] ?? null;

// Initialize variables to avoid PHP notices
$clientName = '';
$sex = '';
$age = '';

// Check if mat_id is provided and fetch patient details
if ($mat_id) {
    // Corrected query to fetch client details from the 'patients' table
    $query = "SELECT clientName, sex, age FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $mat_id); // 's' for string
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $clientName = $row['clientName'];
        $sex = $row['sex'];
        $age = $row['age'];
    }
    $stmt->close();
}

$prescription_date = date('Y-m-d');

// Function to generate the next prescription ID
function generatePrescriptionId($conn) {
    // Get the last prescription number from the database
    $sql = "SELECT prescription_id FROM other_prescriptions ORDER BY prescription_id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $last_prescription = $result->fetch_assoc();
        $last_number = substr($last_prescription['prescription_id'], 7); // Remove 'PRESCR-' prefix
        $next_number = str_pad($last_number + 1, 8, '0', STR_PAD_LEFT);
        return 'PRESCR-' . $next_number;
    } else {
        // If no prescription exists yet, start with PRESCR-00000001
        return 'PRESCR-00000001';
    }
}

// Generate the prescription ID
$prescription_id = generatePrescriptionId($conn);

// Fetch drug names for the dropdown
$drug = [];
$result = $conn->query("SELECT drugname FROM drug ORDER BY drugname ASC");
while ($row = $result->fetch_assoc()) {
    $drug[] = $row['drugname'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Prescription</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container { margin-top: 50px; }
        .form-group label { font-weight: bold; }
        .table thead th { background-color: #f8f9fa; }
        .btn-add-line { background-color: #007bff; color: white; }
        .btn-remove-line { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h2>New Prescription</h2>
        <form id="prescriptionForm" action="process_prescription.php" method="POST">
            <div class="form-group">
                <label>Prescription ID:</label>
                <input type="text" class="form-control" name="prescription_id" value="<?php echo htmlspecialchars($prescription_id); ?>" readonly>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Client Name:</label>
                    <input type="text" class="form-control" name="clientName" value="<?php echo htmlspecialchars($clientName); ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label>MAT ID:</label>
                    <input type="text" class="form-control" name="mat_id" value="<?php echo htmlspecialchars($mat_id); ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Sex:</label>
                    <input type="text" class="form-control" name="sex" value="<?php echo htmlspecialchars($sex); ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Age:</label>
                    <input type="number" class="form-control" name="age" value="<?php echo htmlspecialchars($age); ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Prescriber Name:</label>
                    <input type="text" class="form-control" name="prescriber_name" value="<?php echo htmlspecialchars($prescriber_name); ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Date:</label>
                <input type="date" class="form-control" name="prescription_date" value="<?php echo $prescription_date; ?>" readonly>
            </div>

            <hr>
            <h3>Prescription Details</h3>
            <table class="table table-bordered" id="prescriptionTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Drug Name</th>
                        <th>Dosing</th>
                        <th>Frequency</th>
                        <th>Days</th>
                        <th>Total Dosage</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button type="button" class="btn btn-add-line" id="addDrugLine">Add Drug</button>
            <button type="submit" class="btn btn-primary float-right">Save Prescription</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let rowCount = 0;
            const drug = <?php echo json_encode($drug); ?>;

            function addDrugLine() {
                rowCount++;
                const tableBody = document.getElementById('prescriptionTable').getElementsByTagName('tbody')[0];
                const newRow = tableBody.insertRow();
                newRow.innerHTML = `
                    <td>${rowCount}</td>
                    <td>
                        <select class="form-control" name="drug[]" required>
                            <option value="">--Select Drug--</option>
                            ${drug.map(drug => `<option value="${drug}">${drug}</option>`).join('')}
                        </select>
                    </td>
                    <td><input type="number" class="form-control dosing-input" name="dosings[]" min="0" step="any" required></td>
                    <td><input type="number" class="form-control frequency-input" name="frequencies[]" min="1" required></td>
                    <td><input type="number" class="form-control days-input" name="days[]" min="1" required></td>
                    <td><input type="text" class="form-control total-dosage-input" name="total_dosages[]" readonly></td>
                    <td><button type="button" class="btn btn-remove-line btn-sm">Remove</button></td>
                `;

                // Add event listeners for new row
                const dosingInput = newRow.querySelector('.dosing-input');
                const frequencyInput = newRow.querySelector('.frequency-input');
                const daysInput = newRow.querySelector('.days-input');

                // Attach a single update function to all relevant inputs
                const inputs = [dosingInput, frequencyInput, daysInput];
                inputs.forEach(input => input.addEventListener('input', updateDosage));

                const removeButton = newRow.querySelector('.btn-remove-line');
                removeButton.addEventListener('click', function() {
                    newRow.remove();
                    updateRowNumbers();
                });
            }

            function updateDosage(event) {
                const row = event.target.closest('tr');
                const dosingValue = parseFloat(row.querySelector('.dosing-input').value) || 0;
                const frequencyValue = parseInt(row.querySelector('.frequency-input').value) || 0;
                const daysValue = parseInt(row.querySelector('.days-input').value) || 0;
                const totalDosageInput = row.querySelector('.total-dosage-input');

                const total = dosingValue * frequencyValue * daysValue;
                if (!isNaN(total) && total > 0) {
                    totalDosageInput.value = total;
                } else {
                    totalDosageInput.value = '';
                }
            }

            function updateRowNumbers() {
                const rows = document.getElementById('prescriptionTable').getElementsByTagName('tbody')[0].rows;
                for (let i = 0; i < rows.length; i++) {
                    rows[i].cells[0].innerText = i + 1;
                }
                rowCount = rows.length;
            }

            // Initial row
            addDrugLine();

            // Add new line button listener
            document.getElementById('addDrugLine').addEventListener('click', addDrugLine);
        });
    </script>
</body>
</html>