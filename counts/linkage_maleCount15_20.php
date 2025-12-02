<?php
// Include the database connection file
include '../includes/config.php';

$currentMonth = date('m');
$currentYear = date('Y');

// Define the SQL query to count males not reintehgrated
$query = "SELECT COUNT(*) AS count
                        FROM psychodar
                        WHERE sex = 'male' AND age between 15 and 20
                        AND linkage != 'none'
                        AND YEAR(visitDate) = $currentYear
                        AND MONTH(visitDate) = $currentMonth";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
        // Fetch the count
        $row = $result->fetch_assoc();
        $maleCount = $row['count'];

        // Output the table
        echo "<table>

                        <tr>
                                <td>$maleCount</td>
                        </tr>
                    </table>";
} else {
        echo "0"; // If no females aged 15-20 found in the previous month, display 0
}

// Close the database connection
$conn->close();
?>
