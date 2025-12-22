<?php
session_start();
include('../includes/config.php');

?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div>" . $message . "</div>";
}

if (isset($_SESSION['successMessages'])) {
    $successMessages = $_SESSION['successMessages'];
    unset($_SESSION['successMessages']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy DAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <!-- Add your CSS styling here -->
    <style>


    </style>
</head>
<body>
    <?php if (isset($successMessages)): ?>
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <?php foreach ($successMessages as $success): ?>
                <p><?php echo htmlspecialchars($success); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <h2 style="color: #2C3162; ">Controlled Drugs Dispensing Form</h2>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
    <div class="header">
        <label for="search">Search:</label>
        <input type="text" id="search" class="search-entry"name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search" class="search-input">
        <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button> <!-- Add Cancel Button -->
        <a href="edit_dispensed_dose.php" style="background: red; color: #ffffff; text-decoration: none; height: 40px; border-radius: 5px; align-text: center;">Delete Dispensed Record</a>
    </div>

    </form>
    <!-- Display Data -->
<?php
// Handle search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM patients WHERE (mat_id LIKE '%$search%'
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
        OR drugname LIKE '%$search%'
        OR dosage LIKE '%$search%'
        OR current_status LIKE '%$search%')
        AND current_status IN ('Active', 'LTFU', 'Defaulted')";
// Pagination setup
$results_per_page = 10; // Number of results per page
$number_of_results = mysqli_num_rows(mysqli_query($conn, $sql)); // Total number of results
$number_of_pages = ceil($number_of_results / $results_per_page); // Total pages

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page, default to 1
$start_limit = ($current_page - 1) * $results_per_page; // Start limit for SQL query
$start_range = $start_limit + 1; // Start range for display
$end_range = min($start_limit + $results_per_page, $number_of_results); // End range for display

$sql .= " LIMIT $start_limit, $results_per_page"; // Append LIMIT to SQL query

// Fetch data from the database
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Display the data in a table
    echo "<table>
            <thead>
                <tr>
                <th style='width: 60px;'>ID</th>
                <th>MAT ID</th>
                <th>Client Name</th>
                <th style='width: 80px;'>Age</th>
                <th style='width: 80px;'>Sex</th>
                <th>Drug</th>
                <th style='width: 80px;'>Dosage</th>
                <th style='width: 120px;'>Current Status</th>
                <th style='width: 80px;'>History</th>
                <th>Action</th>
            </tr>
        </thead>
    <tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['p_id'] . "</td>
                <td>" . $row['mat_id'] . "</td>
                <td>" . $row['clientName'] . "</td>
                <td>" . $row['age'] . "</td>
                <td>" . $row['sex'] . "</td>
                <td style ='color: blue;'>" . $row['drugname'] . "</td>
                <td>" . $row['dosage'] . "</td>
                <td>" . $row['current_status'] . "</td>
                <td>
                    <center>
                    <a href='history.php?p_id=" . $row['p_id'] . "' style='font-size: 24px; color: brown;'><i class='fa fa-exclamation-circle'></i></a>
                    </center>

                </td>
                <td>
                    <a href='../pharmacy/view-missed.php?mat_id=" . $row['mat_id'] . "'>View</a> &#124;
                    <a href='dispensingData_pump.php?mat_id=" . $row['mat_id'] . "'>DISPENSE</a> &#124;
                    <a href='multi_dispensing.php?mat_id=" . $row['mat_id'] . "'>MDD</a>
                    <a href='../referrals/referral.php?mat_id=" . $row['mat_id'] . "'Refer</a>
                </td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results found.</p>";
}

// Display range of current rows
echo "<div>Showing $start_range-$end_range of $number_of_results results</div>";

// Pagination links
$max_links = 5; // Maximum number of page links to display
$start_page = max(1, $current_page - floor($max_links / 2)); // Start page for links
$end_page = min($number_of_pages, $start_page + $max_links - 1); // End page for links

echo "<div>";
if ($current_page > 1) {
    $prev_page = $current_page - 1;
    echo "<a href='?page=$prev_page&search=$search'>Previous</a> ";
}

for ($page = $start_page; $page <= $end_page; $page++) {
    $active = $page == $current_page ? "style='font-weight:bold;'" : '';
    echo "<a href='?page=$page&search=$search' $active>$page</a> ";
}

if ($current_page < $number_of_pages) {
    $next_page = $current_page + 1;
    echo "<a href='?page=$next_page&search=$search'>Next</a> ";
}
echo "</div>";
?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>

<script>
        function cancelSearch() {
            document.getElementById("search").value = ''; // Clear search input
            document.getElementById("searchForm").submit(); // Submit form to reset
        }

        // Real-Time Filtering
        document.getElementById("search").addEventListener("input", function() {
            setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 3000); // Submit form after a brief delay (e.g., 500 milliseconds)
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
            setTimeout(hideMessageContainer, 3000); // Hide message after 5 seconds (5000 milliseconds)
        }
    });
</script>
</body>
</html>
