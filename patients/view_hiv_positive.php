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
    <title>HIV Positive</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
</head>
<body>
    <h2 style="color: #2C3162;">HIV Positive and Viral Load</h2>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
        <div class="header">
            <label for="search">Search:</label>
            <input type="text" id="search" class="search-entry" name="search" value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>

            <label for="print-pdf"></label>
            <button id="print-pdf" onclick="window.print()">Print PDF</button>

            <label for="export-excel"></label>
            <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

            Positives:&nbsp;&nbsp;<span style="font-weight: normal; color: red;"><?php include '../counts/positive_patient_count.php'; ?></span><br>
            <button
                onclick="window.close()"
                style="background: none; border: none; background: red; color: #ffffff; text-decoration: underline; cursor: pointer; font-size: 16px; padding: 5px;
                "> ← Go Back
            </button>
        </div>
    </form>

    <script>
        function cancelSearch() {
            window.location.href = 'view_hiv_positive.php';
        }

        // Function to export table data to Excel
        function exportToExcel() {
            var table = document.getElementsByTagName("table")[0];
            var html = table.outerHTML;

            // Format HTML for Excel
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);

            // Create temporary link element and trigger download
            var link = document.createElement("a");
            link.href = uri;
            link.style = "visibility:hidden";
            link.download = "data.xls";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>


    <?php
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // SQL to select the most recent entry for each unique mat_id
    $sql = "SELECT t1.*
            FROM viral_load t1
            INNER JOIN (
                SELECT mat_id, MAX(vl_id) AS max_vl_id
                FROM viral_load
                GROUP BY mat_id
            ) t2 ON t1.mat_id = t2.mat_id AND t1.vl_id = t2.max_vl_id
            WHERE t1.hiv_status = 'positive'
            AND (t1.vl_id LIKE ? OR t1.mat_id LIKE ? OR t1.clientName LIKE ? OR
                 t1.dob LIKE ? OR t1.sex LIKE ? OR t1.hiv_status LIKE ? OR t1.regimen_type LIKE ? OR t1.results LIKE ?)";

    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $search . "%";
    $stmt->bind_param("ssssssss",
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam
    );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table>
                <thead>
                    <tr>
                        <th style='width: 80px;'>VL ID</th>
                        <th style='width: 165px;'>MAT ID</th>
                        <th style='width: 165px;'>Enrolment Date</th>
                        <th style='width: 80px;'>Sex</th>
                        <th>HIV Status</th>
                        <th style='width: 200px;'>ART Regimen</th>
                        <th>Regimen Type</th>
                        <th>Last VL Date</th>
                        <th>Last VL Results</th>
                        <th>Next Appointment</th>
                        <th>Last Update</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                        <td>" . htmlspecialchars($row['vl_id']) . "</td>
                        <td>" . htmlspecialchars($row['mat_id']) . "</td>
                        <td>" . htmlspecialchars($row['reg_date']) . "</td>
                        <td>" . htmlspecialchars($row['sex']) . "</td>
                        <td>" . htmlspecialchars($row['hiv_status']) . "</td>
                        <td>" . htmlspecialchars($row['art_regimen']) . "</td>
                        <td>" . htmlspecialchars($row['regimen_type']) . "</td>
                        <td>" . (empty($row['last_vlDate']) ? "NA" : htmlspecialchars($row['last_vlDate'])) . "</td>
                        <td>" . (empty($row['results']) ? "NA" : htmlspecialchars($row['results'])) . "</td>
                        <td>" . htmlspecialchars($row['next_appointment']) . "</td>
                        <td>" . $row['comp_date'] . "</td>
                        <td>
                            <a href='../clinician/view_viral_load_history.php?mat_id=" . htmlspecialchars($row['mat_id']) . "'>View</a> |
                            <a href='../clinician/update.php?mat_id=" . htmlspecialchars($row['mat_id']) . "'>Update</a>
                             | <a href='../referrals/referral.php?mat_id=" . htmlspecialchars($row['mat_id']) . "'>Refer</a>
                        </td>
                    </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div>No results found.</div>";
    }

    $conn->close();
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

<script>
    // Function to export table data to Excel
    function exportToExcel() {
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;

        // Format HTML for Excel
        var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"><meta name=ProgId content=Excel.Sheet><meta name=Generator content="Microsoft Excel 15"><style>td { border: 1px solid black; }</style></head><body>' + html + '</body></html>');

        // Create temporary link element and trigger download
        var link = document.createElement("a");
        link.href = uri;
        link.style = "visibility:hidden";
        link.download = "data.xls";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>
