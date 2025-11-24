<?php
// Start session and configure database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (NO role restriction)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
// include '../includes/header.php'; // Include your standard header here if needed

$transfer_forms = [];
$error = '';

// --- DATABASE QUERY ---
// SQL to fetch ALL records for any logged-in user (NO WHERE clause for restriction)
$sql = "SELECT id, facilityname, clientName, mat_id, referral_date, type_of_movement, from_site, to_site, pdf_filename, created_at FROM transfer_forms ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    // No bind_param needed as there are no parameters
    $stmt->execute();
    $result = $stmt->get_result();
    $transfer_forms = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Exception $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transfer Forms</title>
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
    <h2 class="mb-4">Client Transfer Forms Documentation</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Forms List ??</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Form Facility</th>
                            <th>Client Name</th>
                            <th>MAT ID</th>
                            <th>Transfer Type</th>
                            <th>From Site</th>
                            <th>To Site</th>
                            <th>Referral Date</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transfer_forms)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No transfer forms found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transfer_forms as $index => $form):
                                $pdf_filename = $form['pdf_filename'] ?? '';
                                // Fallback/Cleaning Logic (kept for robustness)
                                if (empty($pdf_filename) && !empty($form['mat_id']) && !empty($form['created_at'])) {
                                    $date_part = date('Y-m-d', strtotime($form['created_at']));
                                    $pdf_filename = $form['mat_id'] . '_' . $date_part . '.pdf';
                                } else {
                                     // Ensure the .pdf extension is added if the column is just the base name
                                    if (!empty($pdf_filename) && pathinfo($pdf_filename, PATHINFO_EXTENSION) !== 'pdf') {
                                        $pdf_filename .= '.pdf';
                                    }
                                }

                                // The base path to the PDF files
                                $file_path_base = '../transferforms/';
                            ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($form['facilityname']); ?></td>
                                    <td><?= htmlspecialchars($form['clientName']); ?></td>
                                    <td><?= htmlspecialchars($form['mat_id']); ?></td>
                                    <td><?= htmlspecialchars($form['type_of_movement']); ?></td>
                                    <td><?= htmlspecialchars($form['from_site']); ?></td>
                                    <td><?= htmlspecialchars($form['to_site']); ?></td>
                                    <td><?= htmlspecialchars($form['referral_date']); ?></td>
                                    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($form['created_at']))); ?></td>
                                    <td class="action-btns">
                                        <?php if (!empty($pdf_filename)): ?>
                                            <a href="<?= $file_path_base . urlencode($pdf_filename); ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-info"
                                               title="View PDF">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="download_handler.php?file=<?= urlencode($pdf_filename); ?>"
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
// Close connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>