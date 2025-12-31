<?php
include('../includes/footer.php');
include('../includes/header.php');

// Read
$sql = "SELECT * FROM status";
$result = $conn->query($sql);
$statuss = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>status List</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">


    <style>
        button {
            background-color: green;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        button:hover {
            background-color: blue;
        }

        .container {
            margin-left: 20px;
            font-size: 16px;

        }

        .status-table th,
        .status-table td {
            width: auto;
            padding: 8px;
        }

        .status-table th:last-child,
        .status-table td:last-child {
            width: 220px;
        }


    </style>
</head>
<body>

<div class="container mt-5">
    <h2>status List</h2>

    <!-- Search Form -->
    <form action="statuslist.php" method="get" class="form-inline mb-3">
        <div class="form-group mx-sm-3">
            <label for="search" class="sr-only">Search</label>
            <input type="text" class="form-control" id="search" name="search" placeholder="Search">
        </div>
        <button type="submit" class="btn btn-outline-primary">Search</button>
    </form>

    <a href="status.php">
        <button>Add New status</button>
    </a>

    <!-- status Table -->
    <table class="table table-bordered status-table">
        <thead>
        <tr>
            <th>Status ID</th>
            <th>Status Name</th>
            <th>Description</th>
            <th>Date Created</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody class="tbody">
        <?php foreach ($statuss as $status): ?>
            <tr>
                <td><?= $status['status_id']; ?></td>
                <td><?= $status['status_name']; ?></td>
                <td><?= $status['descr']; ?></td>
                <td><?= $status['date_created']; ?></td>
                <td>
                    <!-- Add buttons for update, view, and delete with appropriate links -->
                    <a href="../processors/update_status.php?id=<?= $status['status_id']; ?>" class="btn btn-primary btn-sm">Update</a>
                    <a href="view_status.php?id=<?= $status['status_id']; ?>" class="btn btn-info btn-sm">View</a>
                    <a href="../processors/delete_status.php?id=<?= $status['status_id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function () {
        $('#search').on('input', function () {
            var searchTerm = $(this).val();
            $.ajax({
                url: 'search_status.php', // Change this to your search script
                type: 'GET',
                data: {search: searchTerm},
                success: function (response) {
                    $('.status-table tbody').html(response);
                },
                error: function () {
                    console.log('Error during AJAX request');
                }
            });
        });
    });
</script>
</body>
</html>
