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
    <title>Photos</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <!-- Add your CSS styling here -->
    <style>

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            margin-left: 10px;
            margin-right: 20px;
        }

        .header{
            margin-bottom: 20px;
        }

        .register-input{
            display: in-line block;
            background-color: #2C3162;
            color: white;
            align-content: center;
            align-items: center;
            padding: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancel-input{
            background-color: green;
            color: white;
            width: 80px;
            margin-right: 40px;
            margin-left: 40px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
        }
         .search-input{
            background-color: grey;
            color: white;
            width: 100px;
            margin-right: 10px;
            margin-left: 20px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
        }

         #sign-input{
            background-color: red;
            color: white;
            width: 120px;
            margin-right: 10px;
            margin-left: 100px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
            padding: 5px;
        }
         h2{
             margin-top: 30px;
             margin-bottom: 20px;
         }
         th, td{
             padding: 10px 10px;
         }

    </style>
</head>
<body>
    <h2 style="color: #2C3162; ">Capture Clients Photos</h2>

<!--This is the search Form -->

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
    <div class="header">
        <label for="search">Search:</label>
        <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search" class="search-input">
         <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button> <!-- Add Cancel Button -->
         <a href="read.php">Display all clients</a>
    </form>

    </div>

    <!-- Display Data -->
    <?php
    // Handle search functionality
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sql = "SELECT * FROM patients WHERE mat_id LIKE '%$search%' OR mat_number LIKE '%$search%' OR clientName LIKE '%$search%' OR nickName LIKE '%$search%' OR dob LIKE '%$search%' OR age LIKE '%$search%' OR sex LIKE '%$search%' OR p_address LIKE '%$search%' OR peer_edu_name LIKE '%$search%' OR peer_edu_phone LIKE '%$search%' OR cso LIKE '%$search%' OR dosage LIKE '%$search%' OR current_status LIKE '%$search%'";

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
                    <th>p_ID</th>
                    <th>MAT ID</th>
                    <th>MAT Number</th>
                    <th>Client Name</th>
                    <th>Nick Name</th>
                    <th>Date of Birth</th>
                    <th>Sex</th>
                    <th>Current Status</th>
                    <th>Action</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['p_id'] . "</td>
                    <td>" . $row['mat_id'] . "</td>
                    <td>" . $row['mat_number'] . "</td>
                    <td>" . $row['clientName'] . "</td>
                    <td>" . $row['nickName'] . "</td>
                    <td>" . $row['dob'] . "</td>
                    <td>" . $row['sex'] . "</td>
                    <td>" . $row['current_status'] . "</td>
                    <td>
                        <a href='../psycho-social/view.php?p_id=" . $row['p_id'] . "'>View</a> &#124;
                        <a href='photoData.php?p_id=" . $row['p_id'] . "'>Capture Photo</a> &#124;
                        <a href='deletePhoto.php?p_id=" . $row['p_id'] . "'>Delete</a>
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
            }, 4000); // Submit form after a brief delay (e.g., 500 milliseconds)
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
