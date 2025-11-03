<?php
session_start(); // Add this at the very beginning

include '../includes/config.php';

// --- START: Cache/Session Logic ---

// 1. Check if the form was submitted. If so, update session variables.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['year'])) {
    $_SESSION['selected_year'] = intval($_POST['year']);
    $_SESSION['selected_month'] = intval($_POST['month']);
}

// 2. Retrieve the year and month. This will be used for both the form's default
//    selection AND for running the report logic at the bottom of the page.
//    Priority: POST > SESSION > Current Date
$year = isset($_POST['year']) ? intval($_POST['year'])
        : (isset($_SESSION['selected_year']) ? $_SESSION['selected_year']
        : date("Y"));

$month = isset($_POST['month']) ? intval($_POST['month'])
         : (isset($_SESSION['selected_month']) ? $_SESSION['selected_month']
         : date("n"));

// --- END: Cache/Session Logic ---
?>


<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <title>Form P5</title>

    <style>
        .navheader{

            background-color: none;
            display: flex;
            width: auto; /* Change width to auto */
            width: 100%;
            height: 60px;
            padding: 10px;
            align-items: center;
            align-content: center;
            font-size: 20px;
        }
         #print-pdf{
           background-color: grey;
           color: white;
           width: 100px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }

          #export-excel{
           background-color: green;
           color: white;
           width: 140px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }
          #submit{
           background-color: #000099;
           color: white;
           width: 100px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }

         #year, #month{
           background-color: #979797;
           color: white;
           width: 100px;
           height: 35px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           text-align: center;
           font-size: 18px;
         }

        table {
            border-collapse: collapse;
            width: 100%;
            background-color: none;
            font-size: 14px;


        }
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            white-space: nowrap; /* Prevent text wrapping */

        }
        th {
            background-color: #f2f2f2;
            border: 1px solid black;
            padding: 8px;
            text-align: center;


        }

        .total-dosage {
            color: blue;
        }
        .zero-dosage {
            color: red;
        }
         th:nth-child(2),
        td:nth-child(2) {
            width: auto; /* Set the width to auto for the second th and td elements */
        }
    </style>
</head>
<body>
<div class="navheader">
<form action="process_form.php" method="post">
    <label for="year">Year:</label>
    <select name="year" id="year">
    <?php
    $current_year = date("Y");
    $start_year = $current_year - 1;
    for ($i = $start_year; $i <= $current_year + 3; $i++) {
        $selected = ($i == $current_year) ? "selected" : ""; // Default selection for current year
        echo "<option value='$i' $selected>$i</option>";
    }
    ?>
    </select>
    <label for="month">Month:</label>
    <select name="month" id="month">
        <?php
            // Use the $month variable set in Step 1 for the default selection
            $current_month = date("n");
            // Note: We use the $month variable set below the form for the selected state
            for ($i = 1; $i <= 12; $i++) {
                // Check against the $month variable which holds the cached or submitted value
                $selected = ($i == $month) ? "selected" : "";
                echo "<option value='$i' $selected>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>"; // Added month name for clarity
            }
        ?>
    </select>



    <input type="submit" id="submit" value="Submit">
</form>

<!-- Label for printing to PDF -->
<label for="print-pdf"></label>
<button id="print-pdf" onclick="window.print()">Print PDF</button>

<!-- Label for exporting to Excel -->
<label for="export-excel"></label>
<button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

</div>

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

<?php
include('../includes/config.php');

// Retrieve year and month
$year = isset($_POST['year']) ? intval($_POST['year']) : date("Y");
$month = isset($_POST['month']) ? intval($_POST['month']) : date("n");

// Generate dates for the selected month and year
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dates = [];
for ($i = 1; $i <= $num_days; $i++) {
    $dates[] = "$year-$month-" . str_pad($i, 2, '0', STR_PAD_LEFT);
}

// Retrieve data
$sql = "SELECT
            pharmacy.mat_id,
            pharmacy.clientName,
            patients.sname,
            patients.dosage,
            pharmacy.visitDate,
            pharmacy.cso,
            patients.current_status,
            pharmacy.drugname
        FROM pharmacy
        INNER JOIN patients ON pharmacy.mat_id = patients.mat_id
        WHERE patients.current_status IN ('Active', 'Defaulted')
        ORDER BY patients.current_status, pharmacy.visitDate";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $prev_mat_id = null;

    echo "<table>
        <tr>
            <th>Mat ID</th>
            <th>Client Name</th>
            <th>SurName</th>
            <th>CSO</th>
            <th>Current Status</th>
            <th>Drug Name</th>";

    foreach ($dates as $date) {
        echo "<th>$date</th>";
    }

    echo "<th>Total</th></tr>";

    $processed_mat_ids = [];

    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['mat_id'], $processed_mat_ids)) {
            $processed_mat_ids[] = $row['mat_id'];

            echo "<tr>
                <td>{$row['mat_id']}</td>
                <td>{$row['clientName']}</td>
                <td>{$row['sname']}</td>
                <td>{$row['cso']}</td>
                <td>{$row['current_status']}</td>
                <td>{$row['drugname']}</td>";

            $total_dosage = 0;

            foreach ($dates as $date) {
                $dosage = 0;
                $sql_dosage = "SELECT dosage FROM pharmacy WHERE mat_id = '{$row['mat_id']}' AND visitDate = '$date'";
                $dosage_result = $conn->query($sql_dosage);
                if ($dosage_result->num_rows > 0) {
                    $dosage_row = $dosage_result->fetch_assoc();
                    $dosage = $dosage_row['dosage'];
                    $total_dosage += $dosage;
                }

                $is_before_current_date = ($date < date("Y-m-d"));
                $is_zero_dosage = ($dosage == 0);
                $class = ($is_before_current_date && $is_zero_dosage) ? "zero-dosage" : "";

                echo "<td class='$class'>$dosage</td>";
            }

            echo "<td class='total-dosage'>$total_dosage</td></tr>";
        }
    }

    echo "</table>";
} else {
    echo "No records found.";
}

$conn->close();
?>
<script src="../assets/js/bootstrap.min.js"></script>
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

<?php
// $year and $month are already defined and hold the cached/submitted values
// No need to redefine them based on POST/SESSION again.

// Generate dates for the selected month and year
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

?>