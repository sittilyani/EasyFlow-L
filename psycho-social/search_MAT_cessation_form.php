<?php
session_start();
include('../includes/config.php');

// Only proceed if database connection exists
if (!$conn) {
    die("Database connection failed.");
}

// Handle success/error message from URL
$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Cessation Search</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; }
        h2 { color: #2C3162; margin-bottom: 20px; }
        .header {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-entry {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-input, .cancel-input {
            padding: 10px 15px;
            background: #2C3162;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-input { background: #dc3545; }
        #print-pdf, #export-excel {
            padding: 10px 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #export-excel { background: #007bff; }
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            animation: fadeOut 5s forwards;
        }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #2C3162; color: white; }
        tr:hover { background-color: #f5f5f5; }
        a { color: #2C3162; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
        .pagination {
            margin-top: 30px;
            text-align: center;
        }
        .pagination a, .pagination span {
            padding: 10px 16px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #2C3162;
        }
        .pagination a:hover { background-color: #f0f0f0; }
        .pagination .active {
            background-color: #2C3162;
            color: white;
            border-color: #2C3162;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        @media print {
            .header, .pagination, #print-pdf, #export-excel, .cancel-input { display: none; }
            body { background: white; }
        }
    </style>
</head>
<body>

    <h2>MAT Cessation Form Search</h2>

    <?php if ($message): ?>
        <div id="message-container" class="message success">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
        <div class="header">
            <label for="search"><strong>Search:</strong></label>
            <input type="text" class="search-entry" id="search" name="search"
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                   placeholder="Enter MAT ID, Name, Age, Dosage, etc...">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>
            <button type="button" id="print-pdf" onclick="window.print()">Print PDF</button>
            <button type="button" id="export-excel" onclick="exportToExcel()">Export to Excel</button>
        </div>
    </form>

    <hr>

    <?php
    // Sanitize and prepare search term
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
    $records_per_page = 15;
    $current_page = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Use prepared statement-like escaping with LIKE
    $search_like = '%' . $conn->real_escape_string($search_term) . '%';

    // Base WHERE condition
    $where_conditions = "WHERE (mat_id LIKE ? OR mat_number LIKE ? OR clientName LIKE ? OR nickName LIKE ?
                         OR dob LIKE ? OR age LIKE ? OR sex LIKE ? OR p_address LIKE ?
                         OR peer_edu_name LIKE ? OR peer_edu_phone LIKE ? OR cso LIKE ? OR dosage LIKE ?)";

    // Count total records
    $count_sql = "SELECT COUNT(*) AS total FROM patients $where_conditions";
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param("ssssssssssss", $search_like, $search_like, $search_like, $search_like,
                            $search_like, $search_like, $search_like, $search_like,
                            $search_like, $search_like, $search_like, $search_like);
    $stmt_count->execute();
    $total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $records_per_page);

    // Fetch current page data
    $sql = "SELECT mat_id, p_id, clientName, age, sex, p_address, drugname, dosage, current_status, reg_date, dob
            FROM patients $where_conditions
            ORDER BY mat_id DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssii", $search_like, $search_like, $search_like, $search_like,
                      $search_like, $search_like, $search_like, $search_like,
                      $search_like, $search_like, $search_like, $search_like,
                      $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>MAT ID</th>
                    <th>Client Name</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Residence</th>
                    <th>Drug</th>
                    <th>Dosage</th>
                    <th>Current Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $cessation_url = 'mat_cessation_form.php?' . http_build_query([
                        'mat_id' => $row['mat_id'],
                        'name' => $row['clientName'],
                        'age' => $row['age'],
                        'sex' => $row['sex'],
                        'dob' => $row['dob'],
                        'enroll_date' => $row['reg_date'],
                        'current_dose' => $row['dosage'],
                        'drugname' => $row['drugname']
                    ]);
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['mat_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['clientName']); ?></td>
                        <td><?php echo htmlspecialchars($row['age']); ?></td>
                        <td><?php echo htmlspecialchars($row['sex']); ?></td>
                        <td><?php echo htmlspecialchars($row['p_address']); ?></td>
                        <td><?php echo htmlspecialchars($row['drugname']); ?></td>
                        <td><?php echo htmlspecialchars($row['dosage']); ?></td>
                        <td><?php echo htmlspecialchars($row['current_status']); ?></td>
                        <td>
                            <a href='../patients/view_patient.php?p_id=<?php echo urlencode($row['p_id']); ?>'>View</a> |
                            <a href='<?php echo htmlspecialchars($cessation_url); ?>'>Check In (Cessation)</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_term); ?>">« Previous</a>
                <?php endif; ?>

                <?php
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);

                if ($start > 1) {
                    echo '<a href="?page=1&search=' . urlencode($search_term) . '">1</a> ... ';
                }

                for ($i = $start; $i <= $end; $i++):
                    if ($i == $current_page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                    <?php endif;
                endfor;

                if ($end < $total_pages):
                    echo ' ... <a href="?page=' . $total_pages . '&search=' . urlencode($search_term) . '">' . $total_pages . '</a>';
                endif;

                if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_term); ?>">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p><strong>No results found.</strong> Try adjusting your search terms.</p>
    <?php endif; ?>

    <script>
        function cancelSearch() {
            window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
        }

        function exportToExcel() {
            const table = document.querySelector('table');
            if (!table) return alert('No data to export');

            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
                <head><meta charset="UTF-8">[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
                <x:Name>MAT Cessation Data</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]</head>
                <body>${table.outerHTML}</body></html>
            `;

            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'MAT_Cessation_Search_<?php echo date('Y-m-d'); ?>.xls';
            a.click();
            URL.revokeObjectURL(url);
        }

        // Auto-hide message after 5 seconds
        setTimeout(() => {
            const msg = document.getElementById('message-container');
            if (msg) msg.style.display = 'none';
        }, 5000);
    </script>

    <!-- Bootstrap JS (optional if using full Bootstrap) -->
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>