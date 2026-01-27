<?php
session_start();
include('../includes/config.php');

?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div>" . $message . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transit patients</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <!-- Add your CSS styling here -->
    <style>

    </style>
</head>
<body>
    <h2 style="color: #2C3162; ">Transit Clients</h2>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
    <div class="header">
        <label for="search">Search:</label>
        <input type="text" id="search" class="search-entry" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search" class="search-input">
        <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button> <!-- Add Cancel Button -->

        <!-- Label for printing to PDF -->
        <label for="print-pdf"></label>
        <button id="print-pdf" onclick="window.print()">Print PDF</button>

        <!-- Label for exporting to Excel -->
        <label for="export-excel"></label>
        <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

        Transit clients:&nbsp;&nbsp;<span style="font-weight: normal; color: red;" ><?php include '../counts/transit_patient_count.php'; ?></span><br>
        <button
            onclick="window.close()"
            style="background: none; border: none; background: red; color: #ffffff; text-decoration: underline; cursor: pointer; font-size: 16px; padding: 5px;
            "> ? Go Back
        </button>
    </div>
    </form>

    <script>
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


    <!-- Display Data -->
    <?php
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sql = "
    SELECT
        patients.*,
        (SELECT DATE_FORMAT(MAX(visitDate), '%Y-%m-%d') FROM pharmacy WHERE pharmacy.mat_id = patients.mat_id) AS visitDate,
        COALESCE(DATEDIFF(CURDATE(), (SELECT MAX(visitDate) FROM pharmacy WHERE pharmacy.mat_id = patients.mat_id)), 0) AS days_ltfu /*if not visitDate found in pharmacy table, return, 0*/
    FROM
        patients
    WHERE
        (mat_id LIKE '%$search%'
        OR mat_number LIKE '%$search%'
        OR clientName LIKE '%$search%'
        OR nickName LIKE '%$search%'
        OR dob LIKE '%$search%'
        OR age LIKE '%$search%'
        OR sex LIKE '%$search%'
        OR p_address LIKE '%$search%'
        OR peer_edu_name LIKE '%$search%'
        OR peer_edu_phone LIKE '%$search%'
        OR cso LIKE '%$search%'
        OR dosage LIKE '%$search%')
        AND mat_status = 'transit'
";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table>
                <thead>
                    <tr>
                        <th style='width: 50px;'>Pt ID</th>
                        <th style='width: 140px;'>MAT ID</th>
                        <th style='width: 200px;'>Client Name</th>
                        <th style='width: 100px;'>Date of Birth</th>
                        <th style='width: 120px;'>Date of Enrolment</th>
                        <th style='width: 50px;'>Age</th>
                        <th style='width: 50px;'>Sex</th>
                        <th style='width: 100px;'>Residence</th>
                        <th style='width: 70px;'>CSO</th>
                        <th style='width: 120px;'>Drug</th>
                        <th style='width: 100px;'>Dosage</th>
                        <th style='width: 200px;'>Action</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['p_id']) . "</td>
                    <td>" . htmlspecialchars($row['mat_id']) . "</td>
                    <td>" . htmlspecialchars($row['clientName']) . "</td>
                    <td>" . htmlspecialchars($row['dob']) . "</td>
                    <td>" . htmlspecialchars($row['reg_date']) . "</td>
                    <td>" . htmlspecialchars($row['age']) . "</td>
                    <td>" . htmlspecialchars($row['sex']) . "</td>
                    <td>" . htmlspecialchars($row['p_address']) . "</td>
                    <td>" . $row['cso'] . "</td>
                    <td>" . htmlspecialchars($row['drugname']) . "</td>
                    <td>" . htmlspecialchars($row['dosage']) . "</td>
                    <td>
                        <a href='../patients/view_patient.php?p_id=" . htmlspecialchars($row['p_id']) . "'>View</a> |
                        <a href='../patients/update.php?p_id=" . htmlspecialchars($row['p_id']) . "'>Update</a>
                     | <a href='../referrals/refer.php?mat_id=" . htmlspecialchars($row['mat_id']) . "'>Refer</a>
                     </td>
                </tr>";
        }
        echo "</tbody></table>";
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
    </script>
</body>
</html>

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
