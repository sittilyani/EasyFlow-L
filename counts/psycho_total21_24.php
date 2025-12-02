<?php
// Include the database connection file
include '../includes/config.php';

$currentMonth = date('m');
$currentYear = date('Y');

// Define the SQL query to count otherss aged 21 to 24 years
$query = "SELECT COUNT(*) AS others
            FROM psychodar
            WHERE sex IN ('male', 'female')
            AND age BETWEEN 21 AND 24
            AND YEAR(visitDate) = $currentYear
            AND MONTH(visitDate) = $currentMonth";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
        // Fetch the count
        $row = $result->fetch_assoc();
        $othersCount = $row['others'];

        // Output the table
        echo "<table>

                        <tr>
                                <td>$othersCount</td>
                        </tr>
                    </table>";
} else {
        echo "0"; // If no otherss aged 15-20 found in the previous month, display 0
}

// Close the database connection
$conn->close();
?>
