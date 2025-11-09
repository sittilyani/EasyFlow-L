<?php
/**
 * clients_total_today.php
 * Returns number of UNIQUE clients who received Methadone on a SPECIFIC date
 * Usage: clients_total_today.php?date=2025-11-03
 */
ob_start();
include '../includes/config.php';

// Get requested date
$requestedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
    $requestedDate = date('Y-m-d');
}

// Query: count DISTINCT mat_id for Buprenorphine 2mg on that day
$sql = "
    SELECT COUNT(DISTINCT mat_id) AS total_clients
    FROM pharmacy
    WHERE DATE(dispDate) = ?
      AND dosage IS NOT NULL
      AND drugname = 'Buprenorphine 2mg'
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo '0';
    ob_end_flush();
    exit;
}

$stmt->bind_param('s', $requestedDate);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$clients = $row['total_clients'] ?? 0;

$stmt->close();
echo (int)$clients;
ob_end_flush();
?>