<?php
ob_start();
session_start();
require_once '../includes/config.php';

/**
 * Default dates:
 * - If no filter submitted → tomorrow
 * - If filter submitted → user-selected range
 */
$startDate = $_POST['startDate'] ?? date('Y-m-d', strtotime('+1 day'));
$endDate   = $_POST['endDate']   ?? date('Y-m-d', strtotime('+1 day'));

$appointments = [];
$error = '';

try {

    $sql = "
        SELECT
            dar_id, visitDate, mat_id , clientName, living_conditions,
            sex, therapists_name, therapists_notes, next_appointment,

            /*Clinical notes from psychodar*/

            COALESCE(NULLIF(therapists_notes, ''), 'no previous_visit') AS therapists_notes,

            /* Derived appointment status */
            CASE
                WHEN DATE(next_appointment) = CURDATE() + INTERVAL 1 DAY
                THEN 'scheduled'
                ELSE 'unscheduled'
            END AS appointment_status

        FROM psychodar
        WHERE next_appointment >= CONCAT(?, ' 00:00:00')
          AND next_appointment <= CONCAT(?, ' 23:59:59')

        ORDER BY next_appointment ASC
    ";

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
    if (empty($dateString)) return 'N/A';
    return date('M d, Y', strtotime($dateString));
}

$successMessage = (isset($_GET['success']) && $_GET['success'] == 1)
    ? 'Appointment updated successfully!'
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>psychodar Appointment Schedule</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
    <style>
        .status-scheduled {
            background:#FF6600;
            color:#fff;
            padding:5px 10px;
            border-radius:5px;
        }
        .status-unscheduled {
            background:#6c757d;
            color:#fff;
            padding:5px 10px;
            border-radius:5px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="container">
        <h1 class="display-5 fw-bold">Psychiatric Appointment Schedule</h1>
        <p class="lead">Manage and track patient appointments</p>
    </div>
</div>

<div class="container-one">

    <!-- FILTER -->
    <div class="filter-section">
        <form method="POST">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label>Start Date</label>
                    <input type="date" class="form-control" name="startDate"
                           value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-4">
                    <label>End Date</label>
                    <input type="date" class="form-control" name="endDate"
                           value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Filter Appointments</button>
                </div>
            </div>
        </form>

        <div class="row mt-3">
            <div class="col-md-6">
                <strong><?= count($appointments) ?></strong> appointments found
            </div>
            <div class="col-md-6 text-end">
                <?php if ($successMessage): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>DAR</th>
                <th>Last Visit Date</th>
                <th>MAT ID</th>
                <th>Client Name</th>
                <th>Clinic Notes</th>
                <th>Officer Name</th>
                <th>Next Appointment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>

            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted">
                        No appointments found for the selected date range.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['dar_id']) ?></td>
                        <td><?= formatDate($app['visitDate']) ?></td>
                        <td><?= htmlspecialchars($app['mat_id']) ?></td>
                        <td><?= htmlspecialchars($app['clientName']) ?></td>
                        <td><?= htmlspecialchars($app['therapists_notes']) ?></td>
                        <td><?= htmlspecialchars($app['therapists_name']) ?></td>
                        <td><?= formatDate($app['next_appointment']) ?></td>
                        <td>
                            <span class="<?= $app['appointment_status'] === 'scheduled'
                                ? 'status-scheduled'
                                : 'status-unscheduled' ?>">
                                <?= ucfirst($app['appointment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="update_psychodar_appointment.php?dar_id=<?= $app['dar_id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                View / Update
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>