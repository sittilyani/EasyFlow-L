<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of consents users from the database
$sql = "SELECT COUNT(*) as consentsCount FROM client_consents WHERE consent_status = 'yes'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$consentsCount = $result['consentsCount'];

// Output the count as plain text
echo $consentsCount;
?>



    <script>
        // Function to update the count of consents users
        function updateconsentsCount() {
            $.ajax({
                url: 'consents_count.php',
                type: 'GET',
                success: function (data) {
                    $('#consentssCount').text('consentss: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching consents count:', error);
                }
            });
        }

        // Call the function initially
        updateconsentsCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateconsentsCount, 300000);
    </script>



