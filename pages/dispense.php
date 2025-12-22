<?php
session_start();

    // Check if the user is not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("location: ../index.php");
        exit;
    }
// Include config file
include '../includes/config.php';
include '../includes/footer.php';

// Read
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM patient";
if (!empty($searchTerm)) {
    // If there is a search term, add a WHERE clause
    $sql .= " WHERE mat_id LIKE '%$searchTerm%' OR fname LIKE '%$searchTerm%' OR lname LIKE '%$searchTerm%' OR residence LIKE '%$searchTerm%'";
}

$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    $patients = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Handle the error, for example:
    die("Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/LogoMSA.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/LogoMSA.ico" type="image/x-icon">

    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <style>
         .container{
             margin-right: 20px;
             margin-left: 20px;
             font-size: 14px;
             width: 98%;
             overflow-x: auto;
         }

         #patientTable {
        width: calc(100% - 40px); /* 100% width minus 40px for the margins */

        white-space: wrap; /* Prevent text wrapping in cells */
    }
          #clearFilter{
              background-color: blue;
              height: 40px;
              width: 100px;
              color: white;
              border: none;
              border-radius: 5px;
              padding: auto;
              margin-right: 20px;
          }
          #search{
              background-color: #D1E9EB;
              height: 30px;
              width: 200px;


          }
         .headers {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }

            .headers .form-group {
                margin-right: 30px; /* Adjust the margin as needed */
            }

            .headers #new_btn {
                background-color: red;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s;
                width: 100px;
                height: 30px;
                text-decoration: none;
            }

            .headers #new_btn:hover {
                background-color: darkgreen;
            }
            /* Style for the dispensed button */
            .dispensed {
                background-color: green;
                color: white;
            }


    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Patient List</h2>
    <!-- Search Form -->
    <form action="patientlist.php" method="get" class="form-inline mb-3">
        <div class="headers">

        <div class="form-group mx-sm-3">
            <label for="search" class="sr-only">Search</label>
            <input type="text" class="form-control" id="search" name="search" placeholder="Search" value="<?= $searchTerm ?>">
        </div>
        <a href="dispense.php">
            <button type="button" id="clearFilter">Clear Filter</button>
        </a>
    </form>

    <a id="new_btn" href="../public/index.php">SignOut</a>
     </div>

         <!-- Patient Table -->
    <table id="patientTable" class="table table-bordered">
        <thead>
        <tr>
                    <th>pt ID</th>
                    <th>MAT ID</th>
                    <th>MAT Number</th>
                    <th>First Name</th>
                    <th>Surname</th>
                    <th>Residence</th>
                    <th>DOB</th>
                    <th>Dosage</th>
                    <th>Phone</th>
                    <th>Sex</th>
                    <th>Dispense</th>
                </tr>
        </thead>
        <tbody>

        <!--Fetch and display table data  -->
                <?php foreach ($patients as $patient): ?>
                   <tr>
                    <td><?= $patient['p_id']; ?></td>
                    <td><?= $patient['mat_id']; ?></td>
                    <td><?= $patient['nat_id']; ?></td>
                    <td><?= $patient['fname']; ?></td>
                    <td><?= $patient['sname']; ?></td>
                    <td><?= $patient['residence']; ?></td>
                    <td><?= $patient['dob']; ?></td>
                    <td><?= $patient['dosage']; ?></td>
                    <td><?= $patient['phone']; ?></td>
                    <td><?= $patient['sex']; ?></td>

                   <td>
                    <!-- Add buttons for update, view, and delete with appropriate links -->
                    <!--<a href="dispenze2.php?mat_id=<?= $patient['mat_id']; ?>" class="btn btn-primary btn-sm">Dispense</a>  -->
                    <!--<a id="dispenseBtn" href="dispenze2.php?mat_id=<?= $patient['mat_id']; ?>" class="btn btn-primary btn-sm">Dispense</a> -->
                    <a href="dispenze3.phpdispensingData.php?mat_id=<?= $patient['mat_id']; ?>" id="dispenseBtn_<?= $patient['mat_id']; ?>" class="btn btn-primary btn-sm" onclick="handleDispense('<?= $patient['mat_id']; ?>', this)">Dispense</a>
                    <a href="../views/view_patient.php?mat_id=<?= $patient['mat_id']; ?>" class="btn btn-info btn-sm">View</a>
                    <a href="errorpage.php" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <script>
    $(document).ready(function () {
        var dataTable = $('#patientTable').DataTable({
            "pageLength": 5,
            "lengthMenu": [5, 10, 25, 50, 75, 100],
            "columnDefs": [
                {"width": "150px", "targets": -1} // Set width for the last column (Actions)
            ]
        });

        // Live filtering as you type
        $('#search').on('input', function () {
            dataTable.search(this.value).draw();
        });

        // Clear filter button
        $('#clearFilter').on('click', function () {
            $('#search').val('');
            dataTable.search('').draw();
        });
    });
</script>

<script>
    // Function to get the current date in YYYY-MM-DD format
    function getCurrentDate() {
        var today = new Date();
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var day = String(today.getDate()).padStart(2, '0');
        var year = today.getFullYear();
        return year + '-' + month + '-' + day;
    }

    // Check if the dispense state is stored in localStorage for each mat_id
    var dispenseStates = JSON.parse(localStorage.getItem('dispenseStates')) || {};

    // Function to handle dispense button click
    function handleDispense(matId, element) {
        // Toggle the dispense state for this mat_id
        dispenseStates[matId] = !dispenseStates[matId];

        // Update the dispense states in localStorage
        localStorage.setItem('dispenseStates', JSON.stringify(dispenseStates));

        // Toggle the CSS class based on the dispense state
        if (dispenseStates[matId]) {
            element.classList.add('dispensed'); // Apply the dispensed style
        } else {
            element.classList.remove('dispensed'); // Remove the dispensed style
        }

        // Redirect to the dispense page
        window.location.href = element.href;
    }

    // Apply the dispensed style for each mat_id if the dispense state is true
    for (var matId in dispenseStates) {
        if (dispenseStates.hasOwnProperty(matId)) {
            var dispenseBtn = document.getElementById('dispenseBtn_' + matId);
            if (dispenseBtn) {
                if (dispenseStates[matId]) {
                    dispenseBtn.classList.add('dispensed');
                }
            }
        }
    }
</script>

</body>
</html>
