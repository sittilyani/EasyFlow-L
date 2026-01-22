<?php
include('../includes/config.php');

// Check if the delete action is triggered
if(isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Delete the record from the database
    $sql_delete = "DELETE FROM photos WHERE p_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $delete_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Redirect back to the page after deletion
    header("Location: read.php");
    exit;
}

$sql = "SELECT * FROM photos";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Information</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .image-display{
            margin-left: 80px;
        }
        img {
            width: 100px;
            height: auto;
        }
        .btn {
            padding: 5px 10px;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="image-display">
        <h1>Client Information</h1>
        <table border="1">
            <tr>
                <th>Client Name</th>
                <th>MAT Number</th>
                <th>Photo</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo $row['clientName']; ?></td>
                    <td><?php echo $row['mat_id']; ?></td>
                    <td><img src="<?php echo $row['Image']; ?>" alt="<?php echo $row['clientName']; ?>"></td>
                    <td>
                      <a href='update.php?p_id=" . $row['p_id'] . "' class="btn btn-warning">Update</a> &#124;
                        <a href='delete.php?p_id=" . $row['p_id'] . "' class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>

                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
