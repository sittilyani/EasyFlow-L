<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$clinician_id = $_SESSION['user_id'];

// Handle search OR show incomplete forms by default
$results = [];
$show_all = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search_term = $_POST['search_term'] ?? '';
    $show_all = false;

    if (!empty($search_term)) {
        // Search in incomplete triage forms
        $triage_sql = "SELECT t.*, p.clientName, p.mat_id, p.sname
                      FROM triage_services t
                      JOIN patients p ON t.patient_id = p.p_id
                      WHERE (p.clientName LIKE ? OR p.mat_id LIKE ? OR p.sname LIKE ?)
                      AND t.status = 'incomplete' AND t.clinician_id = ?";
        $triage_stmt = $conn->prepare($triage_sql);
        $search_like = "%$search_term%";
        $triage_stmt->bind_param('sssi', $search_like, $search_like, $search_like, $clinician_id);
        $triage_stmt->execute();
        $triage_results = $triage_stmt->get_result();

        while ($row = $triage_results->fetch_assoc()) {
            $results[] = [
                'type' => 'triage',
                'id' => $row['id'],
                'patient_id' => $row['patient_id'],
                'client_name' => $row['clientName'] . ' ' . $row['sname'],
                'mat_id' => $row['mat_id'],
                'status' => $row['status'],
                'updated_at' => $row['updated_at']
            ];
        }
        $triage_stmt->close();
    }
} else {
    // Show all incomplete triage forms by default when page loads (no search)
    $triage_sql = "SELECT t.*, p.clientName, p.mat_id, p.sname
                  FROM triage_services t
                  JOIN patients p ON t.patient_id = p.p_id
                  WHERE t.status = 'incomplete' AND t.clinician_id = ?
                  ORDER BY t.updated_at DESC";
    $triage_stmt = $conn->prepare($triage_sql);
    $triage_stmt->bind_param('i', $clinician_id);
    $triage_stmt->execute();
    $triage_results = $triage_stmt->get_result();

    while ($row = $triage_results->fetch_assoc()) {
        $results[] = [
            'type' => 'triage',
            'id' => $row['id'],
            'patient_id' => $row['patient_id'],
            'client_name' => $row['clientName'] . ' ' . $row['sname'],
            'mat_id' => $row['mat_id'],
            'status' => $row['status'],
            'updated_at' => $row['updated_at']
        ];
    }
    $triage_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Clinical Encounter Forms</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 30px; }
        .search-form { margin-bottom: 30px; }
        .search-input { padding: 12px; width: 300px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; }
        .search-button { padding: 12px 24px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .search-button:hover { background: #2980b9; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .results-table th, .results-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .results-table th { background: #f8f9fa; font-weight: 600; }
        .continue-btn { padding: 8px 16px; background: #e67e22; color: white; text-decoration: none; border-radius: 4px; }
        .continue-btn:hover { background: #d35400; }
        .new-form-btn { padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .new-form-btn:hover { background: #219a52; }
        .type-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .type-triage { background: #fff3cd; color: #856404; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-incomplete { background: #f8d7da; color: #721c24; }
        .no-results { text-align: center; padding: 40px; color: #666; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Clinical Encounter Forms</h1>

        <a href="search_patient.php" class="new-form-btn">Start New Form</a>

        <div class="search-form">
            <form method="POST">
                <input type="text" name="search_term" class="search-input" placeholder="Search by Client Name or MAT ID..."
                       value="<?php echo htmlspecialchars($_POST['search_term'] ?? ''); ?>">
                <button type="submit" name="search" class="search-button">Search</button>
            </form>
        </div>

        <?php if (!empty($results)): ?>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Client Name</th>
                        <th>MAT ID</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td>
                            <span class="type-badge type-<?php echo $result['type']; ?>">
                                <?php echo strtoupper($result['type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($result['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($result['mat_id']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $result['status']; ?>">
                                <?php echo ucfirst($result['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($result['updated_at'])); ?></td>
                        <td>
                            <a href="clinician_initial_encounter_form.php?p_id=<?php echo $result['patient_id']; ?>&triage_id=<?php echo $result['id']; ?>&action=continue" class="continue-btn">
                                Continue Form
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="no-results">
                <p>No incomplete forms found matching your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>No incomplete clinical encounter forms found.</p>
                <p><a href="search_patient.php" style="color: #3498db;">Start a new form</a> to begin a clinical encounter.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>