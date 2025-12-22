<?php
include '../includes/config.php';

// Read
$sql = "SELECT * FROM drug LIMIT 10";
$result = $conn->query($sql);
$drugs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug List</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">

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
            margin-left: 30px;
        }

        .drug-table th,
        .drug-table td {
            width: auto;
            padding: 8px;
        }

        .drug-table th:last-child,
        .drug-table td:last-child {
            width: 220px;
        }
    </style>
</head>
<body>

<div class="content-main">
    
    <a href="../pharmacy/adddrug.php">
        <button>Register New Drug</button>
    </a>

    <!-- Drug Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>Drug ID</th>
                    <th style="width: 220px;">Drug Name</th>
                    <th>Drug Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
        <tbody>
        <?php foreach ($drugs as $drug): ?>
            <tr>
                <td><?= $drug['drugID']; ?></td>
                <td><?= $drug['drugName']; ?></td>
                <td><?= $drug['drugCategory']; ?></td>
                <td><?= $drug['price']; ?></td>
                <td>
                    <!-- Add buttons for update, view, and delete with appropriate links -->
                    <a href="../pharmacy/update_drug.php?id=<?= $drug['drugID']; ?>" class="btn btn-primary btn-sm">Update</a>
                    <a href="../pharmacy/view_drug.php?id=<?= $drug['drugID']; ?>" class="btn btn-info btn-sm">View</a>
                    <a href="../pharmacy/delete_dda_drug.php?id=<?= $drug['drugID']; ?>" class="btn btn-danger btn-sm">Delete</a>
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
                url: 'search_drug.php', // Change this to your search script
                type: 'GET',
                data: {search: searchTerm},
                success: function (response) {
                    $('.drug-table tbody').html(response);
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
