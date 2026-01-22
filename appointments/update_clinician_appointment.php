<?php
session_start();
require_once('../includes/config.php');

// Validate ID
if (!isset($_GET['id'])) {
    die('Appointment ID not specified.');
}

$clinicalId = (int)$_GET['id'];

// Fetch medical history record
$sql = "SELECT id, mat_id, next_appointment, clinical_notes FROM medical_history WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $clinicalId);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();
$stmt->close();

if (!$currentSettings) {
    die('Clinical record not found.');
}

$mat_id = $currentSettings['mat_id'];

$successMessage = '';
$errorMessage = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $next_appointment = trim($_POST['next_appointment']);
    $clinical_notes   = trim($_POST['clinical_notes']);

    if (empty($next_appointment) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment)) {
        $errorMessage = 'Invalid next appointment date.';
    }

    if (empty($errorMessage)) {
        try {
            // START TRANSACTION
            $conn->begin_transaction();

            // Update medical_history
            $sql1 = "
                UPDATE medical_history
                SET next_appointment = ?, clinical_notes = ?
                WHERE id = ?
            ";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param('ssi', $next_appointment, $clinical_notes, $clinicalId);
            $stmt1->execute();
            $stmt1->close();

            // Update patients table
            $sql2 = "
                UPDATE patients
                SET next_appointment = ?
                WHERE mat_id = ?
            ";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('ss', $next_appointment, $mat_id);
            $stmt2->execute();
            $stmt2->close();

            // COMMIT
            $conn->commit();

            header('Location: clinician_appointments.php?success=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Logged-in clinician
$clinician_name = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $clinician_name = trim($user['first_name'] . ' ' . $user['last_name']);
    }
    $stmt->close();
}

$next_appointment = $currentSettings['next_appointment'] ?? '';
$clinical_notes   = $currentSettings['clinical_notes'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Clinical Appointment</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Update Clinical Appointment</h3>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="update" value="1">

        <div class="mb-3">
            <label class="form-label">Next Appointment</label>
            <input type="date" class="form-control" name="next_appointment"
                   value="<?= htmlspecialchars($next_appointment) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Clinical Notes</label>
            <textarea class="form-control" name="clinical_notes" rows="3"><?= htmlspecialchars($clinical_notes) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Clinician</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($clinician_name) ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary">Update Appointment</button>
        <a href="clinician_appointments.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
