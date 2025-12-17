<?php
ob_start();
include '../includes/config.php';
include ("../includes/header.php");

if (isset($_GET['brandname'])) {
    $brandname = urldecode($_GET['brandname']);

    // Fetch transactions for the specific brandname, now including productname
    $sql = "SELECT stockID, id, transactionType, brandname, productname, reorderLevel, openingBalance, quantityin, batch, expiryDate, receivedFrom, quantityOut, stockBalance, status, transBy, transDate FROM stocks WHERE brandname = ? ORDER BY transDate DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $brandname);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Safely get the productname from the first transaction for the title
    $productname = !empty($transactions) ? $transactions[0]['productname'] : '';
} else {
    // Redirect if brandname parameter is not provided
    header("Location: viewstocks_sum.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions - <?= htmlspecialchars($brandname); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        .container-mt {
            max-width: 95%;
            margin-top: 60px;
            margin-left: auto;
            margin-right: auto;
            font-size: 20px;
        }
        .product-name {
            color: black;
            font-weight: bold;
            font-size: 1.2em;
        }
        .brand-name {
            color: red;
            font-weight: bold;
        }
        .bin-card-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

<div class="content-main">
    <h2>Bin Card</h2>
    <div class="bin-card-header">
        <div>
            Product: <span class="product-name"><?= htmlspecialchars($productname); ?></span> &nbsp;&nbsp;
            Brand: <span class="brand-name"><?= htmlspecialchars($brandname); ?></span>
        </div>
    </div>

    <!-- Transactions Table -->
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
        <tr>
            <th>TransID</th>
            <th>Transaction Type</th>
            <th>Opening Balance</th>
            <th>Quantity Received</th>
            <th>Batch No</th>
            <th>Expiry Date</th>
            <th>Received From</th>
            <th>Quantity Out</th>
            <th>Transaction Date</th>
            <th>Stock on Hand</th>
            <th>Status</th>
            <th>Transacted By</th>
        </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="12" class="text-center">No transactions found for this product.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['stockID'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($transaction['transactionType'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['openingBalance'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['quantityin'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['batch'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['expiryDate'] ?? ''); ?></td>
                        <td>
                            <?php
                            if (($transaction['receivedFrom'] ?? '') === '(adjustments)') {
                                echo '<span style="color:red;">' . htmlspecialchars($transaction['receivedFrom']) . '</span>';
                            } else {
                                echo htmlspecialchars($transaction['receivedFrom'] ?? '');
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($transaction['quantityOut'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['transDate'] ?? ''); ?></td>
                        <td><b><?= htmlspecialchars($transaction['stockBalance'] ?? 0); ?></b></td>
                        <td><?= htmlspecialchars($transaction['status'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['transBy'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>