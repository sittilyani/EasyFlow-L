<?php
ob_start();
session_start();
require_once '../includes/config.php';

$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('+1 day'));
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d', strtotime('+1 day'));

// Initialize appointments as an empty array to prevent undefined variable errors
$appointments = [];

try {
    $sql = "SELECT id, visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex, hiv_status, marital_status,
            art_regimen, regimen_type, tb_status, hepc_status, other_status, clinical_notes, current_status,
            last_vlDate, results, clinician_name, next_appointment, rx_date, appointment_status
            FROM patients
            WHERE next_appointment BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

function formatDate($dateString) {
    if (!$dateString) return 'N/A';
    return date('M d, Y', strtotime($dateString));
}

// Check for success message from update
$successMessage = isset($_GET['success']) && $_GET['success'] == 1 ? 'Appointment updated successfully!' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinicians Appointment Schedule</title>
    <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">-->
    <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/appointments.css" type="text/css">
    <!--<script src="https://cdn.jsdelivr.net/npm/sheetjs/xlsx.full.min.js"></script> -->
    <style>
          .status-scheduled {
            background-color: #FF6600;
            color: #ffffff;
        }
        .status-done {
            background-color: #33FF33;
            color: #000000;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="bi bi-calendar-check me-3"></i>Clinicians Appointment Schedule</h1>
            <p class="lead">Manage and track patient appointments</p>
        </div>
    </div>

    <div class="container-one">
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="POST" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" id="filterBtn">
                            <i class="bi bi-funnel me-2"></i>Filter Appointments
                        </button>
                    </div>
                </div>
            </form>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="appointment-count">
                        <span id="appointmentCount"><?php echo count($appointments); ?></span> appointments found
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    <button id="print-pdf" onclick="window.print()">Print PDF</button>
                    <button class="btn export-btn" id="exportBtn">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Visit Date</th>
                            <th>MAT ID</th>
                            <th>Client Name</th>
                            <th>Sex</th>
                            <th>Marital Status</th>
                            <th>Clinical Notes</th>
                            <th>Current Status</th>
                            <th>Clinician Name</th>
                            <th>Next Appointment</th>
                            <th>Appointment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['id']); ?></td>
                                <td><?php echo htmlspecialchars(formatDate($app['visitDate'])); ?></td>
                                <td><?php echo htmlspecialchars($app['mat_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['clientName']); ?></td>
                                <td><?php echo htmlspecialchars($app['sex']); ?></td>
                                <td><?php echo htmlspecialchars($app['marital_status']); ?></td>
                                <td><?php echo htmlspecialchars($app['clinical_notes']); ?></td>
                                <td><?php echo htmlspecialchars($app['current_status']); ?></td>
                                <td><?php echo htmlspecialchars($app['clinician_name']); ?></td>
                                <td><?php echo htmlspecialchars(formatDate($app['next_appointment'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $app['appointment_status'] === 'scheduled' ? 'status-scheduled' : 'status-done'; ?>">
                                        <?php echo $app['appointment_status'] === 'scheduled' ? 'Scheduled' : 'Done'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="update_clinician_appointment.php?id=<?php echo htmlspecialchars($app['id']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View/Update
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>-->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Appointment data from PHP
        const appointments = <?php echo json_encode($appointments); ?>;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('exportBtn').addEventListener('click', exportToExcel);
        });

        // Export to Excel
        function exportToExcel() {
            const exportData = appointments.map(app => {
                return {
                    'ID': app.id,
                    'Visit Date': app.visitDate,
                    'MAT ID': app.mat_id,
                    'Client Name': app.clientName,
                    'Sex': app.sex,
                    'Marital Status': app.marital_status,
                    'Clinical Notes': app.clinical_notes,
                    'Current Status': app.current_status,
                    'Clinician Name': app.clinician_name,
                    'Next Appointment': app.next_appointment,
                    'Appointment Status': app.appointment_status === 'scheduled' ? 'Scheduled' : 'Done'
                };
            });

            const ws = XLSX.utils.json_to_sheet(exportData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Appointments');

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const fileName = `appointments_${startDate}_to_${endDate}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }
    </script>
</body>
</html>