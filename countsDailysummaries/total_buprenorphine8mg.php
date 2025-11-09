<?php
/**
 * total_methadone.php
 * Returns total dosage dispensed on a SPECIFIC date (e.g. 220.50)
 * Usage: total_methadone.php?date=2025-11-03
 */
ob_start();
include '../includes/config.php';

// Get requested date (fallback to today if invalid)
$requestedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
    $requestedDate = date('Y-m-d');
}

// Query: sum of dosage for Methadone on that exact day
$sql = "
    SELECT COALESCE(SUM(dosage), 0) AS total_dosage
    FROM pharmacy
    WHERE DATE(dispDate) = ?
      AND dosage IS NOT NULL
      AND drugname = 'Buprenorphine 8mg'
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo '0.00';
    ob_end_flush();
    exit;
}

$stmt->bind_param('s', $requestedDate);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total = $row['total_dosage'] ?? 0;

$stmt->close();
echo number_format((float)$total, 2, '.', '');
ob_end_flush();
?>