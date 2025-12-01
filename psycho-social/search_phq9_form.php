<?php
session_start();
include('../includes/config.php');
?>

<?php
if (isset($_GET['message'])) {
    // Added a container ID for the message to allow the JS function to hide it
    $message = urldecode($_GET['message']);
    echo "<div id='message-container' style='color: green; font-weight: bold; padding: 10px; border: 1px solid green; background-color: #e6ffe6; margin-bottom: 15px;'>" . htmlspecialchars($message) . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHQ-9 Search</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>
        /* Add CSS for pagination styling if not in tables.css */
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 4px;
        }
        .pagination a:hover:not(.active) {
            background-color: #f4f4f4;
        }
        .pagination span.active {
            background-color: #2C3162;
            color: white;
            border: 1px solid #2C3162;
        }
    </style>
</head>
<body>
    <h2 style="color: #2C3162; ">PHQ - 9 form search</h2>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
        <div class="header">
            <label for="search">Search:</label>
            <input type="text" class="search-entry" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>

            <button type="button" id="print-pdf" onclick="window.print()">Print PDF</button>
            <button type="button" id="export-excel" onclick="exportToExcel()">Export to Excel</button>
        </div>
    </form>
    <hr>
    <?php
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $records_per_page = 15;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Build the WHERE clause (Note: Using $conn->real_escape_string is better than nothing,
    // but prepared statements are far superior for security, even for LIKE clauses.)
    $where_clause = " WHERE (mat_id LIKE '%$search%' OR mat_number LIKE '%$search%' OR clientName LIKE '%$search%' OR nickName LIKE '%$search%' OR dob LIKE '%$search%' OR age LIKE '%$search%' OR sex LIKE '%$search%' OR p_address LIKE '%$search%' OR peer_edu_name LIKE '%$search%' OR peer_edu_phone LIKE '%$search%' OR cso LIKE '%$search%' OR dosage LIKE '%$search%')";

    // Count total rows for pagination
    $count_sql = "SELECT COUNT(*) AS total_rows FROM patients" . $where_clause;
    $count_result = $conn->query($count_sql);
    $total_rows = $count_result->fetch_assoc()['total_rows'];
    $total_pages = ceil($total_rows / $records_per_page);

    // Fetch the data with LIMIT and OFFSET
    $sql = "SELECT * FROM patients" . $where_clause . " LIMIT $records_per_page OFFSET $offset";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table>
                <thead>
                    <tr>
                        <th style='width: 140px;'>MAT ID</th>
                        <th style='width: 200px;'>Client Name</th>
                        <th style='width: 50px;'>Age</th>
                        <th style='width: 50px;'>Sex</th>
                        <th style='width: 100px;'>Residence</th>
                        <th style='width: 120px;'>Drug</th>
                        <th style='width: 100px;'>Dosage</th>
                        <th style='width: 120px;'>Current Status</th>
                        <th style='width: 200px;'>Action</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                        <td>" . htmlspecialchars($row['mat_id']) . "</td>
                        <td>" . htmlspecialchars($row['clientName']) . "</td>
                        <td>" . htmlspecialchars($row['age']) . "</td>
                        <td>" . htmlspecialchars($row['sex']) . "</td>
                        <td>" . htmlspecialchars($row['p_address']) . "</td>
                        <td>" . htmlspecialchars($row['drugname']) . "</td>
                        <td>" . htmlspecialchars($row['dosage']) . "</td>
                        <td>" . htmlspecialchars($row['current_status']) . "</td>
                        <td>
                            <a href='../patients/view_patient.php?p_id=" . htmlspecialchars($row['p_id']) . "'>View</a> |
                            <a href='phq9_form.php?mat_id=" . htmlspecialchars($row['mat_id']) . "'>Check In (PHQ-9)</a>
                        </td>
                    </tr>";
        }
        echo "</tbody></table>";

        // Pagination Links
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            echo "<a href='?page=" . ($current_page - 1) . "&search=" . urlencode($search) . "'>Previous</a>";
        }
        // Only show links for pages near the current page for better UI
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        if ($start_page > 1) {
            echo "<a href='?page=1&search=" . urlencode($search) . "'>1</a>";
            if ($start_page > 2) {
                echo "<span>...</span>";
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                echo "<span class='active'>$i</span>";
            } else {
                echo "<a href='?page=$i&search=" . urlencode($search) . "'>$i</a>";
            }
        }

        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo "<span>...</span>";
            }
            echo "<a href='?page=$total_pages&search=" . urlencode($search) . "'>$total_pages</a>";
        }

        if ($current_page < $total_pages) {
            echo "<a href='?page=" . ($current_page + 1) . "&search=" . urlencode($search) . "'>Next</a>";
        }
        echo "</div>";

    } else {
        echo "<div>No results found.</div>";
    }
    ?>

    <script>
        function exportToExcel() {
            var table = document.getElementsByTagName("table")[0];
            var html = table.outerHTML;
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"><style>td { border: 1px solid black; }</style></head><body>' + html + '</body></html>');
            var link = document.createElement("a");
            link.href = uri;
            link.download = "phq9_search_data.xls"; // Changed filename
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function cancelSearch() {
            // Clears the search field and redirects to the base page URL to remove all GET parameters
            window.location.href = window.location.pathname;
        }

        // Message hiding script
        function hideMessageContainer() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.style.display = 'none';
            }
        }

        window.addEventListener('load', function() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                setTimeout(hideMessageContainer, 5000); // Hide message after 5 seconds
            }
        });
    </script>
</body>
</html>