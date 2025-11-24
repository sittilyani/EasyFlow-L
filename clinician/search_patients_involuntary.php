<?php
session_start();
include('../includes/config.php');
?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div>" . htmlspecialchars($message) . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Patients</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>

    </style>
</head>
<body>
    <h2 style="color: #2C3162; ">Involuntary discontinuation updates</h2>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
        <div class="header">
            <label for="search">Search:</label>
            <input type="text" class="search-entry" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>

            <button id="print-pdf" onclick="window.print()">Print PDF</button>
            <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

            <span style="font-weight: bold;">All: <span style="font-weight: normal; color: green;"><?php include '../counts/new_patient_count.php'; ?></span></span>
        </div>
    </form>

    <?php
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $records_per_page = 15;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Build the WHERE clause
    $where_clause = " WHERE (mat_id LIKE '%$search%' OR mat_number LIKE '%$search%' OR clientName LIKE '%$search%' OR reg_date LIKE '%$search%' OR nickName LIKE '%$search%' OR dob LIKE '%$search%' OR age LIKE '%$search%' OR sex LIKE '%$search%' OR p_address LIKE '%$search%' OR peer_edu_name LIKE '%$search%' OR peer_edu_phone LIKE '%$search%' OR cso LIKE '%$search%' OR dosage LIKE '%$search%' AND current_status IN ('ltfu', 'defaulted', 'involuntary discontinuation'))";

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
                        <th style='width: 100px;'>MAT Number</th>
                        <th style='width: 200px;'>Client Name</th>
                        <th style='width: 100px;'>Nick Name</th>
                        <th style='width: 50px;'>Age</th>
                        <th style='width: 50px;'>Sex</th>
                        <th style='width: 100px;'>Enrolment Date</th>
                        <th style='width: 70px;'>CSO</th>
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
                    <td>" . htmlspecialchars($row['mat_number']) . "</td>
                    <td>" . htmlspecialchars($row['clientName']) . "</td>
                    <td>" . htmlspecialchars($row['nickName']) . "</td>
                    <td>" . htmlspecialchars($row['age']) . "</td>
                    <td>" . htmlspecialchars($row['sex']) . "</td>
                    <td>" . htmlspecialchars($row['reg_date']) . "</td>
                    <td>" . $row['cso'] . "</td>
                    <td>" . htmlspecialchars($row['drugname']) . "</td>
                    <td>" . htmlspecialchars($row['dosage']) . "</td>
                    <td>" . htmlspecialchars($row['current_status']) . "</td>
                    <td>
                        <a href='../patients/view_patient.php?p_id=" . htmlspecialchars($row['p_id']) . "'>View</a> |
                        <a href='involuntary_discontinuation.php?p_id=" . htmlspecialchars($row['p_id']) . "'>Check In</a> |
                        <a href='../patients/update.php?p_id=" . htmlspecialchars($row['p_id']) . "'>Update</a>
                    </td>
                </tr>";
        }
        echo "</tbody></table>";

        // Pagination Links
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            echo "<a href='?page=" . ($current_page - 1) . "&search=" . urlencode($search) . "'>Previous</a>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $current_page) {
                echo "<span class='active'>$i</span>";
            } else {
                echo "<a href='?page=$i&search=" . urlencode($search) . "'>$i</a>";
            }
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
            link.download = "data.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function cancelSearch() {
            window.location.href = window.location.pathname;
        }

    </script>
    <script>
        function cancelSearch() {
            document.getElementById("search").value = ''; // Clear search input
            document.getElementById("searchForm").submit(); // Submit form to reset
        }

        // Real-Time Filtering
        document.getElementById("search").addEventListener("input", function() {
            setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 2000); // Submit form after a brief delay (e.g., 500 milliseconds)
        });
    </script>
    <script>
    // Function to remove the message container
        function hideMessageContainer() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.style.display = 'none';
            }
        }

        // Check if the message container exists and hide it after 5 seconds
        window.addEventListener('load', function() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                setTimeout(hideMessageContainer, 5000); // Hide message after 5 seconds (5000 milliseconds)
            }
        });
    </script>
</body>
</html>