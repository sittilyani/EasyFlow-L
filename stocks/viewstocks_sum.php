<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include dompdf autoloader and necessary namespaces
require_once '../dompdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include "../includes/config.php";
include "../includes/header.php";

$page_title = "Available Stocks";

// Check for logged-in user
if (!isset($_SESSION['username'])) {
    // Enhanced error handling
    $error_message = 'User not logged in. Please log in to access this page.';
    error_log("Stock Summary Error: " . $error_message);
    header('Location: ../login.php?error=' . urlencode($error_message));
    exit;
}

// Initialize variables
$search = '';
$where_clause = '';
$error = '';
$stocks = [];

// Check if search parameter is provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($conn->real_escape_string($_GET['search']));
    $where_clause = "WHERE (s.productname LIKE '%$search%' OR s.brandname LIKE '%$search%')";
}

try {
    // Fetch available stocks - get the latest stock record for each unique product
    $sql = "SELECT s.*
            FROM stocks s
            INNER JOIN (
                SELECT brandname, MAX(stockID) as max_stockID
                FROM stocks
                GROUP BY brandname
            ) latest ON s.brandname = latest.brandname
                    AND s.stockID = latest.max_stockID
            $where_clause
            ORDER BY s.brandname
            LIMIT 20";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    $stocks = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Stock Summary Error: " . $error);
}

// Handle PDF generation
if (isset($_GET['action']) && $_GET['action'] === 'generate_pdf') {
    if (empty($stocks)) {
        $error_message = "No stock data available to generate PDF.";
        error_log("PDF Generation Error: " . $error_message);
        header('Location: viewstocks_sum.php?error=' . urlencode($error_message));
        exit;
    }

    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Inventory Summary</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 20px; }
                h1 { text-align: center; color: #333; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #333; padding: 6px; text-align: left; }
                th { background-color: #667eea; color: white; }
                .header-img { text-align: center; margin-bottom: 20px; }
                .low-stock { background-color: #ffcccc; }
                .error { color: red; font-weight: bold; text-align: center; margin: 20px; }
            </style>
        </head>
        <body>
            <div class="header-img">
                <img src="../assets/images/JaimoLogo4.png" width="100" height="100" alt="">
            </div>
            <h1>Product Stocks Summary</h1>
            <p style="text-align: center;">Generated on: ' . date('Y-m-d H:i:s') . '</p>';

        if (!empty($error)) {
            $html .= '<div class="error">Error: ' . htmlspecialchars($error) . '</div>';
        } else {
            $html .= '<table>
                <tr>
                    <th>Product Name</th>
                    <th>Brand Name</th>
                    <th>Stock Balance</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                </tr>';

            foreach ($stocks as $stock) {
                $rowClass = ($stock['stockBalance'] <= $stock['reorderLevel']) ? 'class="low-stock"' : '';
                $html .= '
                <tr ' . $rowClass . '>
                    <td>' . htmlspecialchars($stock['productname']) . '</td>
                    <td>' . htmlspecialchars($stock['brandname']) . '</td>
                    <td>' . htmlspecialchars($stock['stockBalance']) . '</td>
                    <td>' . htmlspecialchars($stock['reorderLevel']) . '</td>
                    <td>' . htmlspecialchars($stock['status']) . '</td>
                </tr>';
            }

            $html .= '</table>';
        }

        $html .= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('product_stocks_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);
        exit;

    } catch (Exception $e) {
        $error_message = "PDF generation failed: " . $e->getMessage();
        error_log("PDF Generation Error: " . $error_message);
        header('Location: viewstocks_sum.php?error=' . urlencode($error_message));
        exit;
    }
}

// Handle export to Excel
if (isset($_POST['export_excel'])) {
    if (empty($stocks)) {
        $error_message = "No stock data available to export.";
        error_log("Excel Export Error: " . $error_message);
        header('Location: viewstocks_sum.php?error=' . urlencode($error_message));
        exit;
    }

    try {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="product_stocks_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');

        echo "Product Name\tBrand Name\tStock Balance\tReorder Level\tStatus\n";
        foreach ($stocks as $stock) {
            echo htmlspecialchars($stock['productname']) . "\t" .
                 htmlspecialchars($stock['brandname']) . "\t" .
                 htmlspecialchars($stock['stockBalance']) . "\t" .
                 htmlspecialchars($stock['reorderLevel']) . "\t" .
                 htmlspecialchars($stock['status']) . "\n";
        }
        exit;

    } catch (Exception $e) {
        $error_message = "Excel export failed: " . $e->getMessage();
        error_log("Excel Export Error: " . $error_message);
        header('Location: viewstocks_sum.php?error=' . urlencode($error_message));
        exit;
    }
}

// Check for error passed via URL
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            padding: 30px;
            max-width: 95%; /* Increased width for more content */
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(135deg, #920000 0%, #66FF00 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .controls-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #657786;
        }

        .loading-spinner {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }

        .add-product-btn {
            background: linear-gradient(135deg, #920000 0%, #66FF00 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
        }

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .add-product-btn i {
            margin-right: 8px;
        }

        .products-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 0.9rem;
        }

        thead {
            background: linear-gradient(135deg, #920000 0%, #66FF00 100%);
            color: white;
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            white-space: nowrap;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
        }

        tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }

        .btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .btn-update {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-update:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }

        .btn-view {
            background-color: var(--success-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-header {
                padding: 20px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                max-width: none;
            }

            .table-container {
                font-size: 0.8rem;
            }

            th, td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-row {
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="page-header">
        <h1>Inventory Summary</h1>
        <p>Manage your inventory efficiently</p>
    </div>

    <div class="controls-section">
        <div class="search-container">
            <input type="text" class="search-input" id="product-search" placeholder="Search by product name or ID">
            <span class="search-icon"><i class="fas fa-search"></i></span>
            <span class="loading-spinner"><i class="fas fa-spinner spinner"></i></span>
        </div>
        <div>
            <a href="../stocks/addstocks.php" class="add-product-btn"><i class="fas fa-plus"></i> Add Stocks</a>
            <a href="?action=generate_pdf" class="add-product-btn"><i class="fas fa-print"></i> Print PDF</a>
            <form method="post" style="display: inline;">
                <button type="submit" name="export_excel" class="add-product-btn"><i class="fas fa-file-excel"></i> Export to Excel</button>
            </form>
        </div>
    </div>
    <div class="products-container">
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>

                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Brand Name</th>
                        <th>Stock Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="stocks-table">
                    <?php foreach ($stocks as $stock): ?>
                        <tr class="table-row">

                            <td><?php echo htmlspecialchars($stock['stockID']); ?></td>
                            <td><?php echo htmlspecialchars($stock['productname']); ?></td>
                            <td><?php echo htmlspecialchars($stock['brandname']); ?></td>
                            <td><?php echo htmlspecialchars($stock['stockBalance']); ?></td>
                            <td><?php echo htmlspecialchars($stock['status']); ?></td>
                            <td class="action-buttons">
                                <!--<a href="editstock.php?brandname=<?php echo urlencode($stock['brandname']); ?>" class="btn btn-update"><i class="fas fa-edit"></i> Edit</a>-->
                                <a href="view_transactions.php?brandname=<?php echo urlencode($stock['brandname']); ?>" class="btn btn-view"><i class="fas fa-eye"></i> Bin Card</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
  <script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script>
$(document).ready(function() {
    // Search functionality
    $('#product-search').on('input', function() {
        const search = $(this).val().trim().toLowerCase();
        const $rows = $('#stocks-table tr');

        $rows.each(function() {
            const $row = $(this);
            const id = $row.find('td:eq(0)').text().toLowerCase();
            const name = $row.find('td:eq(1)').text().toLowerCase();

            if (id.includes(search) || name.includes(search)) {
                $row.show();
            } else {
                $row.hide();
            }
        });

        // Show/hide loading spinner
        $('.loading-spinner').css('display', search.length > 0 ? 'block' : 'none');
        $('.search-icon').css('display', search.length > 0 ? 'none' : 'block');
    });
});
</script>
</body>
</html>