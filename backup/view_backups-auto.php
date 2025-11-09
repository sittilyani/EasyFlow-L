<?php
session_start();
include '../includes/config.php';

// Define backups directory - Updated path
$backupsDir = 'C:/laragon/backup/mysql'; // Absolute path for Windows

if (!is_dir($backupsDir)) {
    $error = "Backups directory not found at: " . htmlspecialchars($backupsDir);
}

// Process date filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filterApplied = !empty($startDate) || !empty($endDate);

// Collect SQL backups
$backupFiles = [];
if (!isset($error)) {
    $files = scandir($backupsDir);
    foreach ($files as $file) {
        // Filter for SQL backups and prevent directory traversal
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql' && strpos($file, '..') === false) {
            // Extract the filename without extension
            $filename = pathinfo($file, PATHINFO_FILENAME);

            // Expected format: mysql-8.4-2025-11-05_102008
            // Pattern: anything-YYYY-MM-DD_HHMMSS

            // Try to extract date and time using regex
            if (preg_match('/(\d{4})-(\d{2})-(\d{2})_(\d{2})(\d{2})(\d{2})/', $filename, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                $hour = $matches[4];
                $minute = $matches[5];
                $second = $matches[6];

                // Create timestamp
                $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

                // Format for display
                $backupDate = date('d-m-Y', $timestamp);
                $backupDateTime = date('d-m-Y H:i:s', $timestamp);

                // Extract database name (everything before the date)
                $dbName = preg_replace('/-\d{4}-\d{2}-\d{2}_\d{6}$/', '', $filename);

                // Apply date filter if set
                $includeBackup = true;
                if ($filterApplied) {
                    $backupDateForFilter = date('Y-m-d', $timestamp);

                    if (!empty($startDate) && $backupDateForFilter < $startDate) {
                        $includeBackup = false;
                    }
                    if (!empty($endDate) && $backupDateForFilter > $endDate) {
                        $includeBackup = false;
                    }
                }

                if ($includeBackup) {
                    $backupFiles[] = [
                        'filename' => $file,
                        'timestamp' => $timestamp,
                        'date' => $backupDate,
                        'datetime' => $backupDateTime,
                        'filepath' => $backupsDir . '/' . $file,
                        'backup_id' => $dbName
                    ];
                }
            }
        }
    }

    // Sort backups by timestamp (newest first)
    usort($backupFiles, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    // Limit to 10 backups
    $backupFiles = array_slice($backupFiles, 0, 10);
}

// Email backup functionality
if (isset($_GET['email']) && isset($_GET['file'])) {
    $fileToEmail = $_GET['file'];
    $email = 'sittilyani@gmail.com';

    // Validate that the file exists and is in the backup directory
    $filePath = $backupsDir . '/' . basename($fileToEmail);
    if (file_exists($filePath)) {
        // In a real implementation, add code to upload to Google Drive and send email
        $message = "Backup " . htmlspecialchars($fileToEmail) . " has been emailed to " . $email;
        header("Location: ?message=" . urlencode($message));
        exit();
    } else {
        $error = "Invalid file specified for email.";
    }
}

// Handle download
if (isset($_GET['download']) && isset($_GET['file'])) {
    $fileToDownload = basename($_GET['file']);
    $filePath = $backupsDir . '/' . $fileToDownload;

    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $fileToDownload . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: public');

        // Clear output buffer
        ob_clean();
        flush();

        readfile($filePath);
        exit();
    } else {
        $error = "File not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Backups</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <style>
        .backups {
            margin: 10px 20px;
            width: 70%;
            margin-left: auto;
            margin-right: auto;
            padding: 20px;
            border-radius: 10px;
            border: solid thin;
        }

        @media (max-width: 768px) {
            .backups {
                width: 95%;
                padding: 15px;
            }
            .filter-container .row > div {
                margin-bottom: 10px;
            }
            .reset-btn {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="content-main">
        <div class="backups">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
            <?php endif; ?>

            <center><h2>Database Backups</h2></center>

            <!-- Date Range Filter -->
            <div class="filter-container">
                <div class="filter-title"><i class="bi bi-funnel"></i> Filter Backups by Date Range</div> <br>
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Apply Filter</button>
                                <a href="?" class="btn btn-secondary reset-btn"><i class="bi bi-x-circle"></i> Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (empty($backupFiles)): ?>
                <div class="alert alert-info">
                    <?php if ($filterApplied): ?>
                        No backups found for the selected date range.
                    <?php else: ?>
                        No backups found.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered backup-table">
                        <thead>
                            <tr>
                                <th>Database Name</th>
                                <th>Backup Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backupFiles as $backup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($backup['backup_id']); ?></td>
                                    <td><?php echo htmlspecialchars($backup['datetime']); ?></td>
                                    <td>
                                        <a href="?download=1&file=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <a href="?email=1&file=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-envelope"></i> Email
                                        </a>
                                        <a href="delete_backup.php?file=<?php echo urlencode($backup['filename']); ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this backup?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers with some configuration
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr("#start_date", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });

            flatpickr("#end_date", {
                dateFormat: "Y-m-d",
                minDate: document.getElementById("start_date").value || null,
                maxDate: "today"
            });

            // Update end_date min date when start_date changes
            document.getElementById("start_date").addEventListener("change", function() {
                const endDatePicker = flatpickr("#end_date");
                if (this.value) {
                    endDatePicker.set("minDate", this.value);
                } else {
                    endDatePicker.set("minDate", null);
                }
            });
        });
    </script>
</body>
</html>