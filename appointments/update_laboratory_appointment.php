<?php
session_start();
require_once('../includes/config.php');

// Validate lab_id
if (!isset($_GET['lab_id'])) {
    die('Appointment ID not specified.');
}

$labId = (int) $_GET['lab_id'];

// Fetch laboratory record
$sql = "SELECT lab_id, mat_id, next_appointment, lab_notes FROM laboratory WHERE lab_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $labId);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();
$stmt->close();

if (!$currentSettings) {
    die('Laboratory record not found.');
}

$mat_id = $currentSettings['mat_id'];

$successMessage = '';
$errorMessage   = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $next_appointment_raw = trim($_POST['next_appointment']);
    $lab_notes            = trim($_POST['lab_notes']);

    // Normalize date (handles DATE or DATETIME)
    $next_appointment = date('Y-m-d', strtotime($next_appointment_raw));

    if (!$next_appointment) {
        $errorMessage = 'Invalid appointment date.';
    }

    if (empty($errorMessage)) {
        try {
            // START TRANSACTION
            $conn->begin_transaction();

            // 1?? Update laboratory table
            $sql1 = "
                UPDATE laboratory
                SET next_appointment = ?, lab_notes = ?
                WHERE lab_id = ?
            ";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param('ssi', $next_appointment, $lab_notes, $labId);
            $stmt1->execute();
            $stmt1->close();

            // 2?? Update patients table
            $sql2 = "
                UPDATE patients
                SET laboratory_tca = ?
                WHERE mat_id = ?
            ";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('ss', $next_appointment, $mat_id);
            $stmt2->execute();
            $stmt2->close();

            // COMMIT TRANSACTION
            $conn->commit();

            header('Location: laboratory_appointments.php?success=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Logged-in lab officer
$lab_officer_name = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $lab_officer_name = trim($user['first_name'] . ' ' . $user['last_name']);
    }
    $stmt->close();
}

$next_appointment = $currentSettings['next_appointment'] ?? '';
$lab_notes        = $currentSettings['lab_notes'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Laboratory Appointment</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Update Laboratory Appointment</h3>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="update" value="1">

        <div class="mb-3">
            <label class="form-label">Next Appointment</label>
            <input type="date" class="form-control" name="next_appointment"
                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($next_appointment))) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Lab Notes</label>
            <textarea class="form-control" name="lab_notes" rows="3"><?= htmlspecialchars($lab_notes) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Lab Officer</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($lab_officer_name) ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary">Update Appointment</button>
        <a href="laboratory_appointments.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
