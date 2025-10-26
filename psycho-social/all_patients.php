<?php
include "../includes/header.php";

?>

<!DOCTYPE html>
<html>
<head>
    <title>All Staff</title>
    <style>
        .navheader{

            background-color: none;
            display: flex;
            width: auto; /* Change width to auto */
            width: 100%;
            height: 30px;
            padding: 10px;
            align-items: center;
            align-content: center;
            font-size: 20px;
            margin-top: 10px;
            margin-bottom: 20px;
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


    </style>
</head>
<body>

<div class="navheader">

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
        // Your existing exportToExcel() function
    }
</script>

<?php
include('../includes/config.php');
/*include ('../includes/header.php');*/

// Retrieve year and month from the form and convert them to integers
$year = isset($_POST['year']) ? intval($_POST['year']) : date("Y");
$month = isset($_POST['month']) ? intval($_POST['month']) : date("n");

// Generate dates for the selected month and year
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dates = [];
for ($i = 1; $i <= $num_days; $i++) {
    $dates[] = "$year-$month-" . str_pad($i, 2, '0', STR_PAD_LEFT); // Ensure two digits for the day
}

// Retrieve data from patients table
$sql = "SELECT * FROM patients";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output table header
    echo "<table>
        <tr>
            <th>#</th>
            <th>client ID</th>
            <th>MAT ID</th>
            <th>MAT number</th>
            <th>Client Name</th>
            <th>Nick Name</th>
            <th>National ID</th>
            <th>Date of Birth</th>
            <th>Age</th>
            <th>Sex</th>
            <th>Registering Facility</th>
            <th>MFL Code</th>
            <th>County</th>
            <th>Sub County</th>
            <th>Registration Date</th>
            <th>Residence Sub County</th>
            <th>Current Residence</th>
            <th>Client Phone</th>
            <th>mat_status</th>
            <th>transfer_id</th>
            <th>referral_type</th>
            <th>referring_facility</th>
            <th>reffering_fac_client_number</th>
            <th>accompanment_type</th>
            <th>peer_edu_name</th>
            <th>peer_edu_phone</th>
            <th>rx_supporter_name</th>
            <th>rx_supporter_phone</th>
            <th>dosage</th>
            <th>current_status</th>
            <th>hcw_name</th>
            <th>hcw_sign</th>
        </tr>";
    $rowCounter = 1;
    // Loop through the result set and output each row
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>" . $rowCounter . "</td>
            <td>{$row['p_id']}</td>
            <td>{$row['mat_id']}</td>
            <td>{$row['mat_number']}</td>
            <td>{$row['clientName']}</td>
            <td>{$row['nickName']}</td>
            <td>{$row['nat_id']}</td>
            <td>{$row['dob']}</td>
            <td>{$row['age']}</td>
            <td>{$row['sex']}</td>
            <td>{$row['reg_facility']}</td>
            <td>{$row['mflcode']}</td>
            <td>{$row['county']}</td>
            <td>{$row['scounty']}</td>
            <td>{$row['reg_date']}</td>
            <td>{$row['residence_scounty']}</td>
            <td>{$row['p_address']}</td>
            <td>{$row['client_phone']}</td>
            <td>{$row['mat_status']}</td>
            <td>{$row['transfer_id']}</td>
            <td>{$row['referral_type']}</td>
            <td>{$row['referring_facility']}</td>
            <td>{$row['reffering_fac_client_number']}</td>
            <td>{$row['accompanment_type']}</td>
            <td>{$row['peer_edu_name']}</td>
            <td>{$row['peer_edu_phone']}</td>
            <td>{$row['rx_supporter_name']}</td>
            <td>{$row['rx_supporter_phone']}</td>
            <td>{$row['dosage']}</td>
            <td>{$row['current_status']}</td>
            <td>{$row['hcw_name']}</td>
            <td>{$row['hcw_sign']}</td>
        </tr>";

    $rowCounter++; // Increment the row counter
    }

    echo "</table>";
} else {
    echo "No records found.";
}

$conn->close();
?>
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

<!--Script to updates and run time automatically -->

<script>
    function updateDateTime() {
      var dateTimeElement = document.getElementById('date-time');
      var currentDateTime = new Date();
      var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric', timeZoneName: 'short' };
      var formattedDateTime = currentDateTime.toLocaleDateString('en-US', options);
      dateTimeElement.textContent = formattedDateTime;
    }

    // Update date and time every second
    setInterval(updateDateTime, 1000);

    // Initial update
    updateDateTime();
  </script>
</body>
</html>