<?php
session_start();
include '../includes/config.php';

// Check the logged-in user's role
$loggedInUserRole = $_SESSION['userrole'] ?? '';

// Check if a user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Prescription updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescriptions</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <style>
        .close-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin: 10px;
        }

        .close-btn:hover {
            background-color: #c82333;
        }

        .close-btn:active {
            transform: scale(0.98);
        }

        .search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <div class="content-main">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="search-filters">
            <div class="filter-group">
                <label for="min-date">Start Date:</label>
                <input type="date" id="min-date" name="min_date">
            </div>
            <div class="filter-group">
                <label for="max-date">End Date:</label>
                <input type="date" id="max-date" name="max_date">
            </div>
            <div class="filter-group">
                <label for="client-name-filter">Client Name:</label>
                <input type="text" id="client-name-filter" placeholder="Filter by Client Name">
            </div>
            <div class="filter-group">
                <label for="mat-id-filter">MAT ID:</label>
                <input type="text" id="mat-id-filter" placeholder="Filter by MAT ID">
            </div>
            <div class="filter-group">
                <label for="prescriber-filter">Prescriber:</label>
                <input type="text" id="prescriber-filter" placeholder="Filter by Prescriber">
            </div>
            <div class="filter-group">
                <label for="status-filter">Status:</label>
                <input type="text" id="status-filter" placeholder="Filter by Status">
            </div>
        </div>

        <table id="prescriptionsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Prescription ID</th>
                    <th>Client Name</th>
                    <th>MAT ID</th>
                    <th>Date</th>
                    <th>Prescriber</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>

        <button class="close-btn" onclick="closePage()">Close Page</button>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#prescriptionsTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "fetch_prescriptions.php",
                    "type": "POST",
                    "data": function(d) {
                        d.min_date = $('#min-date').val();
                        d.max_date = $('#max-date').val();
                        d.client_name = $('#client-name-filter').val();
                        d.mat_id = $('#mat-id-filter').val();
                        d.prescriber_name = $('#prescriber-filter').val();
                        d.prescr_status = $('#status-filter').val();
                    }
                },
                "columns": [
                    { "data": "prescription_id" },
                    { "data": "clientName" },
                    { "data": "mat_id" },
                    { "data": "prescription_date" },
                    { "data": "prescriber_name" },
                    { "data": "prescr_status" },
                    {
                        "data": null,
                        "render": function(data, type, row) {
                            var viewUrl = "view_done_prescription_details.php?id=" + encodeURIComponent(row.prescription_id);
                            var dispenseUrl = "dispense_prescription.php?id=" + encodeURIComponent(row.prescription_id);
                            var deleteUrl = "delete_prescription.php?id=" + encodeURIComponent(row.prescription_id);

                            var buttons = `<a href="${viewUrl}" class="btn btn-info btn-sm">View</a>
                                           <a href="${dispenseUrl}" class="btn btn-primary btn-sm">&gt;&gt;Next</a>`;

                            var loggedInUserRole = "<?php echo $loggedInUserRole; ?>";
                            if (loggedInUserRole === 'Admin' || loggedInUserRole === 'Pharmacist') {
                                buttons += `<a href="${deleteUrl}" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this prescription?');">Delete</a>`;
                            }
                            return buttons;
                        },
                        "orderable": false
                    }
                ],
                "pageLength": 15, // Limit to 15 records per page
                "searching": false // Disable default search as we're using our own
            });

            // Re-draw table on input change
            $('.search-filters input').on('keyup change', function() {
                table.draw();
            });
        });

        function closePage() {
            window.close();
            setTimeout(function() {
                if (!window.closed) {
                    window.location.href = 'about:blank';
                }
            }, 100);
        }
    </script>
</body>
</html>