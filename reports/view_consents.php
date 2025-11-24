<?php
// view_consents.php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$status = $_GET['status'] ?? '';
$filename_msg = $_GET['file'] ?? '';

// Fetch all consent records
$sql = "SELECT id, client_name, mat_id, mat_facility, visit_date, clinician_name, counselor_name, pdf_filename, created_at FROM client_consents ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$consents = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
$error = mysqli_error($conn);

// Directory where PDFs are stored (relative to this file)
$pdf_path_base = '../consentsforms/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Client Consents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .table thead th {
            background-color: #34495e;
            color: white;
        }
        .action-btns a {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <h2 class="mb-4">Client Consent Forms ??</h2>

    <?php if ($status === 'success'): ?>
        <div class="alert alert-success">
            ? Form submitted and PDF **<?= htmlspecialchars($filename_msg); ?>** successfully saved!
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Consent Forms List </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>MAT ID</th>
                            <th>Facility</th>
                            <th>Visit Date</th>
                            <th>Clinician</th>
                            <th>Counselor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consents)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No client consent forms found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consents as $index => $form):
                                $pdf_filename = $form['pdf_filename'];
                            ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($form['client_name']); ?></td>
                                    <td><?= htmlspecialchars($form['mat_id']); ?></td>
                                    <td><?= htmlspecialchars($form['mat_facility']); ?></td>
                                    <td><?= htmlspecialchars($form['visit_date']); ?></td>
                                    <td><?= htmlspecialchars($form['clinician_name']); ?></td>
                                    <td><?= htmlspecialchars($form['counselor_name']); ?></td>
                                    <td class="action-btns">
                                        <?php if (!empty($pdf_filename)): ?>
                                            <a href="<?= $pdf_path_base . urlencode($pdf_filename); ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-info"
                                               title="View PDF">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="download_consent_handler.php?file=<?= urlencode($pdf_filename); ?>"
                                               class="btn btn-sm btn-success"
                                               title="Download PDF">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="text-danger">File missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
if (isset($conn)) {
    mysqli_close($conn);
}
?>