<?php
// ---------------------------------------------------------------
// 1. CONFIG & SESSION
// ---------------------------------------------------------------
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include '../includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
        die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// ---------------------------------------------------------------
// 2. FACILITY SETTINGS
// ---------------------------------------------------------------
$facilityName = $mflCode = $countyName = $subcountyName = "N/A";
$stmt = $conn->prepare("SELECT facilityname, mflcode, countyname, subcountyname FROM facility_settings LIMIT 1");
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
        $facilityName   = $row['facilityname']   ?? "N/A";
        $mflCode        = $row['mflcode']       ?? "N/A";
        $countyName     = $row['countyname']    ?? "N/A";
        $subcountyName  = $row['subcountyname'] ?? "N/A";
}
$stmt->close();

// ---------------------------------------------------------------
// 3. MONTH SELECTION – DEFAULT = CURRENT MONTH
// ---------------------------------------------------------------
$now = new DateTime();
$defaultYear  = $now->format('Y');
$defaultMonth = $now->format('m');

$selectedYear  = $_GET['year']  ?? $defaultYear;
$selectedMonth = $_GET['month'] ?? $defaultMonth;

$selectedYear  = (int)$selectedYear;
$selectedMonth = str_pad((int)$selectedMonth, 2, '0', STR_PAD_LEFT);

if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $defaultMonth;
if ($selectedYear < 2000 || $selectedYear > 2100) $selectedYear = $defaultYear;

$startDate = "$selectedYear-$selectedMonth-01";
$endDate   = date('Y-m-t', strtotime($startDate));

// ---------------------------------------------------------------
// 4. DRUG SELECTION – HARD-CODED
// ---------------------------------------------------------------
$drugID   = 2;
$drugName = 'Methadone';

// ---------------------------------------------------------------
// 5. SEPARATE QUERIES FOR BETTER ACCURACY
// ---------------------------------------------------------------

// Query for stores inventory (receipts)
$sqlStores = "SELECT
        DATE(transaction_date) AS trans_date,
        SUM(to_dispensing) AS receipt,
        MAX(issued_to_full_name) AS issuer,
        MIN(received_by_full_name) AS receiver
FROM stores_inventory
WHERE DATE(transaction_date) BETWEEN ? AND ?
    AND drugID = ?
GROUP BY DATE(transaction_date)
ORDER BY trans_date";

$stmtStores = $conn->prepare($sqlStores);
$stmtStores->bind_param('ssi', $startDate, $endDate, $drugID);
$stmtStores->execute();
$resultStores = $stmtStores->get_result();

$storesData = [];
while ($row = $resultStores->fetch_assoc()) {
        $storesData[$row['trans_date']] = [
                'receipt' => (float)$row['receipt'],
                'issuer' => $row['issuer'] ?? '',
                'receiver' => $row['receiver'] ?? ''
        ];
}
$stmtStores->close();

// Query for pharmacy (dispensed amounts and client counts)
$sqlPharmacy = "SELECT
        DATE(dispDate) AS disp_date,
        SUM(dosage) AS dispensed,
        COUNT(DISTINCT mat_id) AS client_count
FROM pharmacy
WHERE DATE(dispDate) BETWEEN ? AND ?
    AND drugname = 'Methadone'
    AND dosage IS NOT NULL
GROUP BY DATE(dispDate)
ORDER BY disp_date";

$stmtPharmacy = $conn->prepare($sqlPharmacy);
$stmtPharmacy->bind_param('ss', $startDate, $endDate);
$stmtPharmacy->execute();
$resultPharmacy = $stmtPharmacy->get_result();

$pharmacyData = [];
while ($row = $resultPharmacy->fetch_assoc()) {
        $pharmacyData[$row['disp_date']] = [
                'dispensed' => (float)$row['dispensed'],
                'clients' => (int)$row['client_count']
        ];
}
$stmtPharmacy->close();

// ---------------------------------------------------------------
// 6. BUILD FULL MONTH (1st to last day)
// ---------------------------------------------------------------
$periodEntries = [];
$cur = new DateTime($startDate);
$end = new DateTime($endDate);

while ($cur <= $end) {
        $dateKey = $cur->format('Y-m-d');

        $stores = $storesData[$dateKey] ?? null;
        $pharmacy = $pharmacyData[$dateKey] ?? null;

        $receipt = $stores['receipt'] ?? 0.0;
        $dispensed = $pharmacy['dispensed'] ?? 0.0;
        $closingBal = $receipt - $dispensed;

        $periodEntries[] = [
                'date'          => $dateKey,
                'to_dispensing' => $receipt,
                'dispensed'     => $dispensed,
                'closing_bal'   => $closingBal,
                'issuer'        => $stores['issuer'] ?? '',
                'receiver'      => $stores['receiver'] ?? '',
                'clients'       => $pharmacy['clients'] ?? 0
        ];

        $cur->modify('+1 day');
}

// ---------------------------------------------------------------
// 7. USER NAME
// ---------------------------------------------------------------
$clinician_name = $_SESSION['full_name'] ?? "User #{$loggedInUserId}";
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Form P7 – Daily Summaries (<?= htmlspecialchars($drugName, ENT_QUOTES, 'UTF-8') ?>)</title>
        <style>
                body{font-family:Arial,sans-serif;background:#f8f9fa;color:#333;margin:0;padding:10px;}
                .container{width:85%;margin:auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,.1);}
                .header{display:grid;grid-template-columns:80px 1fr 150px;align-items:center;margin-bottom:10px;}
                .header img{width:80px;height:auto;}
                .form-header {display: grid; grid-template-columns: 20% 60% 20%; align-items: center; margin-bottom: 10px; border: none;  padding: 10px; }
                .form-header .logo-left { text-align: center; }
                .form-header .title-center {text-align: center; }
                .form-header .form-version {text-align: center;}
                h2,h3{text-align:center;margin:5px 0;}
                hr{border:none;height:2px;background:#000;margin:10px 0;}
                .form-group-1{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:15px;}
                .date-controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
                .date-controls label{font-weight:bold;margin-right:5px;}
                .date-controls select, .date-controls input{padding:6px;border:1px solid #ccc;border-radius:4px;}
                .date-controls button{background:#007bff;color:#fff;border:none;padding:8px 15px;border-radius:4px;cursor:pointer;}
                table{width:100%;border-collapse:collapse;margin-top:15px;}
                th,td{border:1px solid #666;padding:6px;font-size:13px;text-align:center;}
                th{background:#efefef;}
                .received{background:#ccffff;}
                .closing{color:red;font-weight:bold;}
                .footer{margin-top:30px;padding-top:10px;border-top:1px solid #ccc;font-size:13px;}
                .blank-row{background:#f9f9f9;}
                select, input { width: 200px; border-radius: 5px; height: 40px; margin-left: 20px;}

        </style>
</head>
<body>
<div class="container">
        <div class="form-header">
            <div class="logo-left">
                <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="">
            </div>
            <div class="title-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <p>CUSTOM DAILY SUMMARIES REPORT – <?= htmlspecialchars($drugName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="form-version">
                <p>VER. SEP. 2025</p>
            </div>
        </div>

        <hr>

        <!-- MONTH SELECTION -->
        <form method="GET">
                <div class="form-group-1">
                        <div><label>Facility Name:</label><input type="text" readonly value="<?= htmlspecialchars($facilityName, ENT_QUOTES, 'UTF-8') ?>"></div>
                        <div><label>MFL Code:</label><input type="text" readonly value="<?= htmlspecialchars($mflCode, ENT_QUOTES, 'UTF-8') ?>"></div>
                        <div><label>County:</label><input type="text" readonly value="<?= htmlspecialchars($countyName, ENT_QUOTES, 'UTF-8') ?>"></div>
                        <div><label>Sub-County:</label><input type="text" readonly value="<?= htmlspecialchars($subcountyName, ENT_QUOTES, 'UTF-8') ?>"></div>

                        <div style="margin-top: 20px;">
                                <label>Month:</label>
                                <select name="month">
                                        <?php for ($m = 1; $m <= 12; $m++):
                                                $monthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
                                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                                $sel = ($monthNum == $selectedMonth) ? 'selected' : '';
                                        ?>
                                                <option value="<?= $monthNum ?>" <?= $sel ?>><?= $monthName ?></option>
                                        <?php endfor; ?>
                                </select>

                                <label>Year:</label>
                                <input type="number" name="year" value="<?= $selectedYear ?>" min="2000" max="2100" style="width:80px;">
                        </div>
                </div>
                <div style="margin-top: 20px;">
                <button type="submit" style ="width: 150px; background: #000099; height: 40px; border: none; border-radius: 5px; color: #FFFFFF;">Update Report</button>
                </div>
        </form>
        <hr>

        <!-- TABLE -->
        <table>
                <thead>
                        <tr>
                                <th>DATE</th>
                                <th>Receipt (from stores – mL)</th>
                                <th>Amount dispensed – mL</th>
                                <th>Closing Bal (mL)</th>
                                <th>Name of Issuer</th>
                                <th>Received By</th>
                                <th>No. of clients</th>
                        </tr>
                </thead>
                <tbody>
                        <?php if (empty($periodEntries)): ?>
                                <tr><td colspan="7" style="color:red;">No data found for <?= date('F Y', strtotime($startDate)) ?>.</td></tr>
                        <?php else: ?>
                                <?php foreach ($periodEntries as $e): ?>
                                        <tr <?= ($e['to_dispensing'] == 0 && $e['dispensed'] == 0) ? 'class="blank-row"' : '' ?>>
                                                <td><?= $e['date'] ?></td>
                                                <td class="received"><?= number_format($e['to_dispensing'], 2) ?></td>
                                                <td><?= number_format($e['dispensed'], 2) ?></td>
                                                <td class="closing"><?= number_format($e['closing_bal'], 2) ?></td>
                                                <td style="color:blue;"><?= htmlspecialchars($e['issuer'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($e['receiver'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= $e['clients'] ?></td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                </tbody>
        </table>

        <!-- FOOTER -->
        <div class="footer">
                <p>Report Generated By: <?= htmlspecialchars($clinician_name, ENT_QUOTES, 'UTF-8') ?> on <?= date('Y-m-d H:i:s') ?></p>
        </div>

</div>
</body>
</html>