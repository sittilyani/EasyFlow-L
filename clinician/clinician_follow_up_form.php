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
    <title>Clinician Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <!-- Add your CSS styling here -->
    <style>


    </style>
</head>
<body>
    <h2 style="color: #2C3162; ">Clinician Follow Up Form</h2>

<!--This is the search Form -->

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">

        <label for="search">Search:</label>
        <input type="text" class="search-entry" id="search" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search" class="search-input">
         <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button> <!-- Add Cancel Button -->
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
            OR dosage LIKE '%$search%'
            OR current_status LIKE '%$search%')
            AND current_status IN ('Active', 'LTFU', 'Defaulted')";
    // Pagination
    $results_per_page = 5;
    $number_of_results = mysqli_num_rows(mysqli_query($conn, $sql));
    $number_of_pages = ceil($number_of_results / $results_per_page);

    $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
    $start_limit = ($current_page - 1) * $results_per_page;

    $sql .= " LIMIT $start_limit, $results_per_page";

    // Fetch data from the database
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Display the data in a table
        echo "<table border='1'>
                <tr>

                    <th>MAT ID</th>
                    <th>MAT Number</th>
                    <th>Client Name</th>
                    <th style='width: 80px;'>Age</th>
                    <th style='width: 80px;'>Sex</th>
                    <th>Drug</th>
                    <th>Dosage</th>
                    <th style='width: 80px;'>Current Status</th>
                    <th>Next Appointment</th>
                    <th>Action</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>

                    <td>" . $row['mat_id'] . "</td>
                    <td>" . $row['mat_number'] . "</td>
                    <td>" . $row['clientName'] . "</td>
                    <td>" . $row['age'] . "</td>
                    <td>" . $row['sex'] . "</td>
                    <td>" . $row['drugname'] . "</td>
                    <td>" . $row['dosage'] . "</td>
                    <td>" . $row['current_status'] . "</td>
                    <td>" . $row['next_appointment'] . "</td>
                    <td>
                        <a href='../patients/view_patient.php?p_id=" . $row['p_id'] . "'>View</a> &#124;
                        <a href='../clinician/clinicianForm-New.php?p_id=" . $row['p_id'] . "'>Check In</a>  &#124;
                        <a href='../referrals/referral.php?mat_id=" . $row['mat_id'] . "'>Refer</a>
                    </td>
                </tr>";
        }
        echo "</table>";}

        // Pagination links

        // Calculate pagination details
        $start_range = max(1, $current_page - 2); // Ensure the start range is at least 1
        $end_range = min($number_of_pages, $start_range + 4); // Ensure the end range is within the total number of pages

        echo "<div>Showing $start_range to $end_range of $number_of_results results</div>";
        echo "<div>";

        if ($current_page > 1) {
            $prev_page = $current_page - 1;
            echo "<a href='?page=$prev_page'>Previous</a> ";
        }

        // Page numbers
        for ($page = $start_range; $page <= $end_range; $page++) {
            echo "<a href='?page=$page'>$page</a> ";
        }

        // Next link
        if ($current_page < $number_of_pages) {
            $next_page = $current_page + 1;
            echo "<a href='?page=$next_page'>Next</a> ";
        }

        echo "</div>";
        ?>

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
</body>
</html>
