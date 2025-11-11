<?php
// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and header/footer files
include('../includes/config.php'); // Ensure this file contains the `$conn` variable

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch the logged-in user's role
$userrole = $_SESSION['userrole'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <style>
        .container-table {

            width: 80%;
            margin-left: 60px;
            padding: 20px;
        }
        h3, tr {
            font-family: "Times New Roman", Times, serif;
        }
        table {
            width: 90%;
        }
    </style>
</head>
<body>
<div class="container-table">
    <h3>Referrals for action</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 120px;">Referral Date</th>
                <th style="width: 150px;">MAT ID</th>
                <th style="width: 200px;">Client Name</th>
                <th style="width: 150px;">Refer To</th>
                <th style="width: 350px;">Referral Notes</th>
                <th style="width: 150px;">Status</th>
                <th style="width: 250px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($userrole === 'Admin') {
                $sql = "SELECT * FROM referral WHERE status != 'completed'";
                $stmt = $conn->prepare($sql);
            } else {
                $sql = "SELECT * FROM referral WHERE refer_to = ? AND status != 'completed'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $userrole);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['referral_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['mat_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['clientName']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['refer_to']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['referral_notes']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                    echo "<td>";
                    echo "<a href='view_referral.php?mat_id=" . urlencode($row['mat_id']) . "' class='btn btn-primary btn-sm'>View</a> ";
                    echo "<a href='edit_referral_pending.php?referral_id=" . urlencode($row['referral_id']) . "' class='btn btn-warning btn-sm'>Re-assign</a>";
                    echo "<a href='delete_referral.php?mat_id=" . urlencode($row['mat_id']) . "' class='btn btn-danger btn-sm'>Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No referrals found.</td></tr>";
            }

            $stmt->close();
            ?>
        </tbody>
    </table>



    <div class="action-buttons">
        <a href="referral_dashboard.php" class="btn-custom btn-primary-custom">
            ← Back to Dashboard
        </a>
        <a href="edit_referral_pending.php?referral_id=<?php echo urlencode($referral_id); ?>" class="btn-custom btn-secondary-custom">
            Edit Referral →
        </a>
    </div>
</div>
</body>
</html>