<?php
session_start();
require_once('../includes/config.php');

// Valdar_idate dar_id
if (!isset($_GET['dar_id'])) {
    die('Appointment dar_id not specified.');
}

$dar_id = (int) $_GET['dar_id'];

// Fetch psychodar record
$sql = "SELECT dar_id, mat_id, next_appointment, therapists_notes FROM psychodar WHERE dar_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $labdar_id);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();
$stmt->close();

if (!$currentSettings) {
    die('psychodar record not found.');
}

$mat_id = $currentSettings['mat_id'];

$successMessage = '';
$errorMessage   = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $next_appointment_raw = trim($_POST['next_appointment']);
    $therapists_notes            = trim($_POST['therapists_notes']);

    // Normalize date (handles DATE or DATETIME)
    $next_appointment = date('Y-m-d', strtotime($next_appointment_raw));

    if (!$next_appointment) {
        $errorMessage = 'Invaldar_id appointment date.';
    }

    if (empty($errorMessage)) {
        try {
            // START TRANSACTION
            $conn->begin_transaction();

            // 1?? Update psychodar table
            $sql1 = "
                UPDATE psychodar
                SET next_appointment = ?, therapists_notes = ?
                WHERE dar_id = ?
            ";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param('ssi', $next_appointment, $therapists_notes, $labdar_id);
            $stmt1->execute();
            $stmt1->close();

            // 2?? Update patients table
            $sql2 = "
                UPDATE patients
                SET nursing_tca = ?
                WHERE mat_id = ?
            ";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('ss', $next_appointment, $mat_id);
            $stmt2->execute();
            $stmt2->close();

            // COMMIT TRANSACTION
            $conn->commit();

            header('Location: psychodar_appointments.php?success=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Logged-in lab officer
$therapists_name = 'Unknown';
if (isset($_SESSION['user_dar_id'])) {
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_dar_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $_SESSION['user_dar_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $therapists_name = trim($user['first_name'] . ' ' . $user['last_name']);
    }
    $stmt->close();
}

$next_appointment = $currentSettings['next_appointment'] ?? '';
$therapists_notes        = $currentSettings['therapists_notes'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Nursing Appointment</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Update Nursing Appointment</h3>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hdar_idden" name="update" value="1">

        <div class="mb-3">
            <label class="form-label">Next Appointment</label>
            <input type="date" class="form-control" name="next_appointment"
                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($next_appointment))) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nursing Notes</label>
            <textarea class="form-control" name="therapists_notes" rows="3"><?= htmlspecialchars($therapists_notes) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Nursing Officer</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($therapists_name) ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary">Update Appointment</button>
        <a href="nursing_appointments.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
