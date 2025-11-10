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
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);padding:30px 20px;min-height:100vh}
        .container{max-width:90%;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.1);overflow:hidden}
        .form-header{background:#f5dd42;color:#000;padding:30px 40px;display:grid;grid-template-columns:100px 1fr 150px;align-items:center;gap:20px}
        .logo-left img{width:120px;height:120px;object-fit:contain;padding:8px;border-radius:8px}
        .title-center{text-align:center}
        .title-center h2{font-size:28px;font-weight:700;margin-bottom:8px;letter-spacing:1px}
        .title-center p{font-size:16px;opacity:.95;font-weight:500}
        .form-version{text-align:right;font-size:12px;opacity:.9}
        .content-wrapper{padding:40px}
        .form-group-1{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:30px}
        .form-group-1>div{display:flex;flex-direction:column}
        .form-group-1 label{font-weight:600;color:#333;margin-bottom:8px;font-size:14px}
        .form-group-1 input,.form-group-1 select{padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;transition:all .3s ease;background:#f9f9f9}.form-group-1 input:focus,.form-group-1 select:focus{outline:0;border-color:#009;background:#fff}.date-selectors{display:flex;gap:20px;align-items:flex-end;margin-top:20px}.date-field{flex:1}#print-pdf,button{width:180px;background:linear-gradient(135deg,#009 0%,#00c 100%);height:48px;border:none;border-radius:8px;color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:all .3s ease;box-shadow:0 4px 15px rgba(0,0,153,.3)}
        button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,153,.4)}
        button:active{transform:translateY(0)}
        hr{border:none;height:1px;background:linear-gradient(to right,transparent,#e0e0e0,transparent);margin:30px 0}
        .table-wrapper{overflow-x:auto;margin:30px 0;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        table{width:100%;border-collapse:collapse;background:#fff}
        thead{background:#f5dd42;color:#000}
        th{padding:16px 12px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:.5px}
        tbody tr{border-bottom:1px solid #f0f0f0;transition:all .2s ease}
        tbody tr:hover{background:#f8f9ff}
        tbody tr.blank-row{background:#f9f9f9;opacity:.7}
        td{padding:14px 12px;font-size:14px;color:#444}
        td.received{font-weight:600;color:#28a745}
        td.closing{font-weight:600;color:#009}
        .no-data{color:#dc3545!important;text-align:center;font-weight:600;padding:30px!important}.footer{background:#f8f9fa;padding:20px 40px;border-top:3px solid #009;margin-top:30px}
        .footer p{color:#666;font-size:13px;text-align:center}
        @media (max-width:768px){.form-header{grid-template-columns:1fr;text-align:center}
        .logo-left{margin:0 auto}.form-version{text-align:center}
        .form-group-1{grid-template-columns:1fr}
        .content-wrapper{padding:20px}}

        @media print{body{background:#fff;padding:0}
        .container{box-shadow:none}
        button{display:none}}
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <div class="logo-left">
                <img src="../assets/images/Government of Kenya.png" alt="Government of Kenya Logo">
            </div>
            <div class="title-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <p>CUSTOM DAILY SUMMARIES REPORT – <?= htmlspecialchars($drugName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="form-version">
                <p>VER. SEP. 2025</p>
            </div>
        </div>

        <div class="content-wrapper">
            <form method="GET">
                <div class="form-group-1">
                    <div>
                        <label>Facility Name:</label>
                        <input type="text" readonly value="<?= htmlspecialchars($facilityName, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div>
                        <label>MFL Code:</label>
                        <input type="text" readonly value="<?= htmlspecialchars($mflCode, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div>
                        <label>County:</label>
                        <input type="text" readonly value="<?= htmlspecialchars($countyName, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div>
                        <label>Sub-County:</label>
                        <input type="text" readonly value="<?= htmlspecialchars($subcountyName, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="date-selectors">
                    <div class="date-field">
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
                    </div>
                    <div class="date-field">
                        <label>Year:</label>
                        <input type="number" name="year" value="<?= $selectedYear ?>" min="2000" max="2100">
                    </div>
                    <button type="submit">Update Report</button>
                </div>
            </form>

            <hr>

            <div class="table-wrapper">
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
                            <tr><td colspan="7" class="no-data">No data found for <?= date('F Y', strtotime($startDate)) ?>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($periodEntries as $e): ?>
                                <tr <?= ($e['to_dispensing'] == 0 && $e['dispensed'] == 0) ? 'class="blank-row"' : '' ?>>
                                    <td><?= $e['date'] ?></td>
                                    <td class="received"><?= number_format($e['to_dispensing'], 2) ?></td>
                                    <td><?= number_format($e['dispensed'], 2) ?></td>
                                    <td class="closing"><?= number_format($e['closing_bal'], 2) ?></td>
                                    <td style="color:#000099;"><?= htmlspecialchars($e['issuer'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($e['receiver'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $e['clients'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>Report Generated By: <?= htmlspecialchars($clinician_name, ENT_QUOTES, 'UTF-8') ?> on <?= date('Y-m-d H:i:s') ?></p> <button id="print-pdf" onclick="window.print()">Print PDF</button>
        </div>
    </div>
</body>
</html>