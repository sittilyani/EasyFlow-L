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

$page_title = "Available stock_movements";

// Check for logged-in user
if (!isset($_SESSION['username'])) {
    $error_message = 'User not logged in. Please log in to access this page.';
    error_log("Stock Summary Error: " . $error_message);
    header('Location: ../login.php?error=' . urlencode($error_message));
    exit;
}

// Initialize variables
$search = '';
$where_clause = '';
$error = '';
$stock_movements = [];

// --- Database Query Logic ---
// Use prepared statements to prevent SQL injection
if (isset($_GET['search']) && !empty($_GET['search'])) {
    // Sanitize the search term for SQL LIKE operator
    $search = $_GET['search'];
    $search_term = '%' . $search . '%';
    // Use `s.drugID` and `s.drugname` for searching
    $where_clause = "WHERE (s.drugID LIKE ? OR s.drugname LIKE ?)";
}

try {
    // Corrected SQL query to get the latest stock for each drugname (latest trans_id)
    $sql = "SELECT s.*
            FROM stock_movements s
            INNER JOIN (
                SELECT drugname, MAX(trans_id) as max_trans_id
                FROM stock_movements
                GROUP BY drugname
            ) latest ON s.drugname = latest.drugname AND s.trans_id = latest.max_trans_id
            $where_clause
            ORDER BY s.drugname
            LIMIT 20";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if ($where_clause) {
        $stmt->bind_param("ss", $search_term, $search_term);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    $stock_movements = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Stock Summary Error: " . $error);
}

// --- AJAX Response for Live Search ---
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // This is an AJAX request, so we only return the table rows
    $html = '';
    if (!empty($stock_movements)) {
        foreach ($stock_movements as $stock) {
            $html .= '<tr class="table-row">';
            $html .= '<td>' . htmlspecialchars($stock['trans_id']) . '</td>';
            // Need drugID in the AJAX response too, as the main table expects it
            $html .= '<td>' . htmlspecialchars($stock['drugID']) . '</td>';
            $html .= '<td>' . htmlspecialchars($stock['drugname']) . '</td>';
            $html .= '<td>' . htmlspecialchars($stock['total_qty']) . '</td>';
            // Action buttons for display/AJAX are included here
            $html .= '<td class="action-buttons">';
            $html .= '<a href="view_transactions.php?drugname=' . urlencode($stock['drugname']) . '" class="btn btn-view"><i class="fas fa-eye"></i> View Bin Card</a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    } else {
        // Updated colspan to 5 for consistency with the table header
        $html .= '<tr><td colspan="5" class="text-center">No results found.</td></tr>';
    }
    echo $html;
    exit;
}

// --- PDF Generation Logic (Needs more context, but template for exclusion is applied below) ---
if (isset($_GET['action']) && $_GET['action'] === 'generate_pdf') {
    // NOTE: For a server-side Dompdf generation, you would need to regenerate the
    // HTML without the 'Action' column and its contents here.
    // Since you are using a client-side print (window.print()), this block is
    // only necessary if you implement server-side PDF generation.
    // Assuming your current logic relies on client-side functions for simplicity.
}

if (isset($_POST['export_excel'])) {
    // Same as above for server-side Excel export.
}

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
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <style>
        /* ... (Your original CSS styles remain here) ... */
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
            background: #000099;
            color: white; /* White text color */
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
            opacity: 0.9;   font-size: 1.1rem;  }

        .controls-section {  display: flex;   justify-content: space-between;   align-items: center;  margin-bottom: 25px;  flex-wrap: wrap;  gap: 15px; }
        .search-container {  position: relative;  flex: 1;  max-width: 800px; /* Increased max width to fit buttons */
            display: flex;  gap: 10px; }
        .search-input {
            width: 100%;
            padding: 12px 15px; /* Reduced right padding as icon is removed */
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
            flex: 1; /* Allow input to grow */
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Removed search-icon CSS as the icon is now in the button */

        .loading-spinner {
            /* Position spinner over the table while loading */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 100;
            font-size: 2rem;
            color: var(--primary-color);
            display: none; /* Hidden by default */
        }

        .add-product-btn {  background: #000099;  color: white; border: none;  padding: 12px 25px;    border-radius: 25px;
            font-weight: 600;             display: inline-flex;             align-items: center;             transition: all 0.3s ease;
            text-decoration: none;             font-size: 1rem;         }
        .add-product-btn:hover { transform: translateY(-2px);  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);  color: white;  }
        .add-product-btn i {  margin-right: 8px;  }
        .products-container { position: relative; /* Needed for spinner positioning */  background: white;  border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            overflow: hidden;   }

        /* ... (Rest of the CSS styles remain here) ... */
         .btn {  padding: 8px 15px;  border-radius: 20px; font-size: 0.8rem; font-weight: 500; margin-right: 5px;  margin-bottom: 5px;
            transition: all 0.2s ease;  display: inline-flex; align-items: center;  justify-content: center;  border: none;  cursor: pointer; }
        .btn i {  margin-right: 5px; font-size: 0.8rem; }
        .btn-update { background-color: var(--warning-color); color: white;  }
        .btn-update:hover { background-color: #e67e22;  transform: translateY(-2px);  box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3); }
        .btn-view {  background-color: var(--success-color);  color: white; }
        .btn-view:hover { background-color: #27ae60; transform: translateY(-2px);  box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3); }
        .action-buttons { display: flex; flex-wrap: wrap;  gap: 5px;   }

        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .page-header { padding: 20px;  text-align: center; }
            .page-header h1 {font-size: 2rem; }
            .controls-section {flex-direction: column;  align-items: stretch; }
            .search-container { max-width: none; flex-wrap: wrap; }
            .search-input {flex: 1 1 100%; }
            #search-btn, #clear-search-btn {flex: 1 1 auto;justify-content: center;             }
            .table-container { font-size: 0.8rem; }
            th, td {padding: 10px 8px; }
            .action-buttons {  flex-direction: column; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-row { animation: fadeIn 0.3s ease forwards; }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {animation: spin 1s linear infinite; }

        /* CSS for print media to hide action column */
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="page-header">
        <h1>Inventory Summary</h1>
        <p>Manage your inventory efficiently</p>
    </div>
    <form method="post" style="display: inline;">
    <div class="controls-section">

        <div class="search-container">
            <input type="text" class="search-input" id="product-search" placeholder="Search by product name or ID">

            <button type="button" id="search-btn" class="add-product-btn" style="padding: 12px 15px; border-radius: 25px;">
                <i class="fas fa-search"></i> Search
            </button>

            <button type="button" id="clear-search-btn" class="btn btn-update" style="padding: 12px 15px; border-radius: 25px; background-color: var(--danger-color);">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
        <div>
            <button type="button" id="print-pdf" onclick="exportToPDF()" class="add-product-btn"><i class="fas fa-file-pdf"></i> Print PDF</button>
            <button type="button" id="export-excel" onclick="exportToExcel()" class="add-product-btn"><i class="fas fa-file-excel"></i> Export to Excel</button>
        </div>
    </div>
     </form>
     <div class="products-container">
        <span class="loading-spinner" id="main-spinner"><i class="fas fa-spinner spinner"></i></span>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Trans ID</th>
                        <th>Drug ID</th>
                        <th>Drug Name</th>
                        <th>Stock Balance</th>
                        <th class="action-column no-print">Action</th>
                    </tr>
                </thead>
                <tbody id="stock_movements-table">
                    <?php if (empty($stock_movements)): ?>
                        <tr><td colspan="5" class="text-center">No results found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stock_movements as $stock): ?>
                            <tr class="table-row">
                                <td><?php echo htmlspecialchars($stock['trans_id']); ?></td>
                                <td><?php echo htmlspecialchars($stock['drugID']); ?></td>
                                <td><?php echo htmlspecialchars($stock['drugname']); ?></td>
                                <td><?php echo htmlspecialchars($stock['total_qty']); ?></td>
                                <td class="action-buttons action-cell no-print">
                                    <a href="view_transactions.php?drugname=<?php echo urlencode($stock['drugname']); ?>" class="btn btn-view"><i class="fas fa-eye"></i> View Bin Card</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
    // --- UTILITY FUNCTIONS ---

    // Note: The Excel export function is retained as it does not rely on jQuery/CDNs
    function createExcelData() {
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;
        var excelHtml = '<html><head><meta charset="UTF-8"><style>td { border: 1px solid black; }</style></head><body>' + html + '</body></html>';
        return excelHtml;
    }

    function exportToExcel() {
        var excelData = createExcelData();
        var blob = new Blob([excelData], { type: 'application/vnd.ms-excel' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.href = url;
        link.download = "inventory_summary.xls";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Custom Print PDF function to hide action column before printing
    function exportToPDF() {
        document.querySelectorAll('.action-column, .action-cell, .no-print').forEach(el => el.style.display = 'none');

        window.print();

        // Restore visibility after printing
        setTimeout(() => {
            document.querySelectorAll('.action-column, .action-cell, .no-print').forEach(el => el.style.display = '');
        }, 500);
    }

    // --- SEARCH LOGIC (VANILLA JAVASCRIPT) ---
    const searchInput = document.getElementById('product-search');
    const tableBody = document.getElementById('stock_movements-table');
    const spinner = document.getElementById('main-spinner');
    const searchBtn = document.getElementById('search-btn');
    const clearBtn = document.getElementById('clear-search-btn');

    let typingTimer;
    const doneTypingInterval = 500; // time in ms for type-ahead delay

    // Function to perform the AJAX search
    function performSearch(search) {
        // Show spinner while searching
        spinner.style.display = 'block';

        // Construct the URL for the AJAX request
        const url = `<?php echo basename(__FILE__); ?>?search=${encodeURIComponent(search)}`;

        // Use the Fetch API for modern, CDN-free AJAX
        fetch(url, {
            method: 'GET',
            headers: {
                // Important: Include this header so PHP knows it's an AJAX request
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            // Update the table body with the received HTML rows
            tableBody.innerHTML = html;
        })
        .catch(error => {
            console.error("AJAX Error:", error);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error fetching data. Please try again.</td></tr>';
        })
        .finally(() => {
            // Hide spinner regardless of success or failure
            spinner.style.display = 'none';
        });
    }

    // Event listener for type-ahead (Input event)
    searchInput.addEventListener('input', function() {
        clearTimeout(typingTimer);
        const search = this.value.trim();

        if (search.length > 0) {
            // Start the timer to perform search after delay
            typingTimer = setTimeout(() => performSearch(search), doneTypingInterval);
        } else {
            // If the input is cleared, fetch all records immediately
            performSearch('');
        }
    });

    // Event listener for the explicit Search button click
    searchBtn.addEventListener('click', function() {
        clearTimeout(typingTimer); // Cancel any pending type-ahead search
        performSearch(searchInput.value.trim());
    });

    // Event listener for the Clear button
    clearBtn.addEventListener('click', function() {
        clearTimeout(typingTimer);
        searchInput.value = ''; // Clear the input field
        performSearch(''); // Fetch all results
    });

    // Initial load/cancellation logic (since cancelSearch is no longer needed)
    /* function cancelSearch() {
        window.location.href = window.location.pathname;
    } */
</script>
</body>
</html>