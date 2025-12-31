<?php
 session_start();

    // Check if the user is not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("location: ../public/index.php");
        exit;
    }
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
include '../includes/footer.php';
include ("../includes/header.php");

// Read
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM patients";
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
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <script type="text/javascript" charset="utf8" src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <style>
         .container{
             margin-right: 20px;
             margin-left: 20px;
             font-size: 14px;
             width: 100%;
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
                background-color: green;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s;
                width: 200px;
                height: 40px;
            }

            .headers #new_btn:hover {
                background-color: darkgreen;
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
        <a href="patientlist.php">
            <button type="button" id="clearFilter">Clear Filter</button>
        </a>
    </form>

    <a href="../processors/addpatient.php">
        <button id="new_btn">Register New patient</button>
    </a>
     </div>
    <!-- Patient Table -->
    <table id="patientTable" class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>MAT ID</th>
            <!--<th>National ID</th>-->
            <th>Client Name</th>
            <th>Sur Name</th>
            <!--<th>SurName</th> -->
            <!--<th>Nick Name</th> -->
            <th>Residence</th>
            <th>Date of Birth</th>
            <th>Date of Enrolment</th>
            <th>CSO</th>
            <th>Dosage</th>
            <!--<th>Phone</th> -->
            <th>Sex</th>
            <th>Status</th>
            <!--<th>Image</th> -->
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($patients as $patients): ?>
            <tr>
                <td><?= $patients['p_id']; ?></td>
                <td><?= $patients['mat_id']; ?></td>
                <!--<td><?= $patient['nat_id']; ?></td>-->
                <td><?= $patients['clientName']; ?></td>
                <td><?= $patients['sname']; ?></td>
                <!--<td><?= $patient['sname']; ?></td>  -->
                <!--<td><?= $patient['nname']; ?></td>  -->
                <td><?= $patients['p_address']; ?></td>
                <td><?= $patients['dob']; ?></td>
                <td><?= $patients['reg_date']; ?></td>
                <td><?= $patients['cso']; ?></td>
                <td><?= $patients['dosage']; ?></td>
                <!--<td><?= $patient['phone']; ?></td>-->
                <td><?= $patients['sex']; ?></td>
                <td><?= $patients['current_status']; ?></td>
                <!--<td><?= $patient['image']; ?></td> -->
                <td>
                    <!-- Add buttons for update, view, and delete with appropriate links -->
                    <a href="../processors/update_patient.php?id=<?= $patients['p_id']; ?>" class="btn btn-primary btn-sm">Update</a>
                    <a href="view_patient.php?id=<?= $patients['p_id']; ?>" class="btn btn-info btn-sm">View</a>
                    <a href="../processors/delete_patient.php?id=<?= $patients['p_id']; ?>" class="btn btn-danger btn-sm">Delete</a>
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

</body>
</html>
