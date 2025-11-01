<?php
include '../includes/config.php';

$results_per_page = 30;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

$dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '';
$dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT * FROM pharmacy WHERE 1=1";

if ($dateFrom && $dateTo) {
    $query .= " AND visitDate BETWEEN '$dateFrom' AND '$dateTo'";
}
if ($searchTerm) {
    $query .= " AND (clientName LIKE '%$searchTerm%' OR drugname LIKE '%$searchTerm%' OR mat_id LIKE '%$searchTerm%')";
}

// Get total rows for pagination
$total_rows_query = "SELECT COUNT(*) as count FROM ($query) AS filtered_rows";
$total_rows_result = $conn->query($total_rows_query);
$total_rows = $total_rows_result->fetch_assoc()['count'];
$total_pages = ceil($total_rows / $results_per_page);

// Add sorting and pagination to main query
$query .= " ORDER BY visitDate DESC LIMIT $results_per_page OFFSET $offset";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drugs Dispensed</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/edit_dispense.css" type="text/css">

</head>
<body>

<div class="filter-form-container">
    <h3 style='color: black; margin-bottom: 10px;'>Drugs Dispensed By Date</h3>
    <form method="get">
        <label for="dateFrom">Date From:</label>
        <input type="date" class="date-input" style="margin-left: 10px; margin-right: 10px;" name="dateFrom" value="<?php echo htmlspecialchars($dateFrom); ?>">
        <label for="dateTo">Date To:</label>
        <input type="date" class="date-input" style="margin-left: 10px; margin-right: 10px;" name="dateTo" value="<?php echo htmlspecialchars($dateTo); ?>">
        <label for="search">Search:</label>
        <input type="text" style="margin-left: 10px; margin-right: 10px;" class="search-input" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Enter drug name or client name">
        <input type="submit" class="custom-search-btn" value="Search" >
        <button type="button" onclick="window.location.href='dispenseddrug-linelist.php';" style='background-color: #F74343; width: 140px; color: white; padding: 10px 20px; color: black; margin-bottom: 20px; margin-left: 10px; border-radius: 5px; border: none; padding: 8px 16px; cursor: pointer;'>Clear</button>
    </form>

    <table class="table">
        <tr>
            <th>Disp_ID</th>
            <th>Client Name</th>
            <th>MAT_ID</th>
            <th>DrugName</th>
            <th>Dosage</th>
            <th>Date of Dispensing</th>
            <th>Sex</th>
            <th>Current Status</th>
            <th>Dispensed By</th>
            <th>Action</th>
        </tr>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['disp_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['clientName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mat_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['drugname']) . "</td>";
            echo "<td>" . htmlspecialchars($row['dosage']) . "</td>";
            echo "<td>" . htmlspecialchars($row['visitDate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
            echo "<td>" . htmlspecialchars($row['current_status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pharm_officer_name']) . "</td>";
            echo "<td>";
            
            echo "<button onclick=\"confirmDelete(" . htmlspecialchars($row['disp_id']) . ", '" . htmlspecialchars($row['drugname']) . "', " . htmlspecialchars($row['dosage']) . ")\" class='btn btn-danger btn-sm'>Delete</button>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <?php
    // Display current range and total
    $current_range_start = min($offset + 1, $total_rows);
    $current_range_end = min($offset + $results_per_page, $total_rows);
    echo "<div style='margin: 10px 0;'>";
    echo "Displaying $current_range_start-$current_range_end of $total_rows rows";
    echo "</div>";

    // Simplified pagination
    echo "<div class='pagination'>";
    if ($page > 1) {
        echo "<a href='?page=" . ($page - 1) . "&dateFrom=$dateFrom&dateTo=$dateTo&search=$searchTerm' class='btn btn-primary'>Previous</a> ";
    }

    // Calculate range of pages to show
    $start_page = max(1, min($page - 1, $total_pages - 2));
    $end_page = min($start_page + 2, $total_pages);

    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i == $page) ? 'active' : '';
        echo "<a href='?page=$i&dateFrom=$dateFrom&dateTo=$dateTo&search=$searchTerm' class='btn btn-primary $active_class'>$i</a> ";
    }

    if ($page < $total_pages) {
        echo "<a href='?page=" . ($page + 1) . "&dateFrom=$dateFrom&dateTo=$dateTo&search=$searchTerm' class='btn btn-primary'>Next</a>";
    }
    echo "</div>";

    $conn->close();
    ?>
</div>

<script>
    function confirmDelete(disp_id, drugname, dosage) {
        if (confirm('Are you sure you want to delete this record?')) {
            window.location.href = 'delete_dispensed_record.php?id=' + disp_id + '&drugname=' + encodeURIComponent(drugname) + '&dosage=' + dosage;
        }
    }
</script>

</body>
</html>