<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$clinician_id = $_SESSION['user_id'];

// Handle search
$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search_term = $_POST['search_term'] ?? '';

    if (!empty($search_term)) {
        // Search in drafts
        $draft_sql = "SELECT d.*, p.clientName, p.mat_id
                      FROM clinical_encounter_drafts d
                      JOIN patients p ON d.patient_id = p.p_id
                      WHERE (p.clientName LIKE ? OR p.mat_id LIKE ?) AND d.clinician_id = ?";
        $draft_stmt = $conn->prepare($draft_sql);
        $search_like = "%$search_term%";
        $draft_stmt->bind_param('ssi', $search_like, $search_like, $clinician_id);
        $draft_stmt->execute();
        $draft_results = $draft_stmt->get_result();

        while ($row = $draft_results->fetch_assoc()) {
            $results[] = [
                'type' => 'draft',
                'id' => $row['id'],
                'patient_id' => $row['patient_id'],
                'client_name' => $row['clientName'],
                'mat_id' => $row['mat_id'],
                'current_section' => $row['current_section'],
                'updated_at' => $row['updated_at']
            ];
        }
        $draft_stmt->close();

        // Search in incomplete encounters
        $encounter_sql = "SELECT e.*, p.clientName, p.mat_id
                         FROM clinical_encounters e
                         JOIN patients p ON e.patient_id = p.p_id
                         WHERE (p.clientName LIKE ? OR p.mat_id LIKE ?) AND e.status = 'draft'";
        $encounter_stmt = $conn->prepare($encounter_sql);
        $encounter_stmt->bind_param('ss', $search_like, $search_like);
        $encounter_stmt->execute();
        $encounter_results = $encounter_stmt->get_result();

        while ($row = $encounter_results->fetch_assoc()) {
            $results[] = [
                'type' => 'encounter',
                'id' => $row['id'],
                'patient_id' => $row['patient_id'],
                'client_name' => $row['clientName'],
                'mat_id' => $row['mat_id'],
                'current_section' => $row['current_section'],
                'updated_at' => $row['updated_at']
            ];
        }
        $encounter_stmt->close();
    }
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
        .continue-btn { padding: 8px 16px; background: #27ae60; color: white; text-decoration: none; border-radius: 4px; }
        .continue-btn:hover { background: #219a52; }
        .new-form-btn { padding: 12px 24px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .new-form-btn:hover { background: #c0392b; }
        .type-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .type-draft { background: #fff3cd; color: #856404; }
        .type-encounter { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Clinical Encounter Forms</h1>

        <a href="clinician_initial_encounter_form.php" class="new-form-btn">Start New Form</a>

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
                        <th>Last Saved Section</th>
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
                        <td><?php echo htmlspecialchars($result['current_section']); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($result['updated_at'])); ?></td>
                        <td>
                            <?php if ($result['type'] === 'draft'): ?>
                                <a href="clinician_initial_encounter_form.php?p_id=<?php echo $result['patient_id']; ?>&draft_id=<?php echo $result['id']; ?>" class="continue-btn">
                                    Continue Form
                                </a>
                            <?php else: ?>
                                <a href="clinician_initial_encounter_form.php?p_id=<?php echo $result['patient_id']; ?>&encounter_id=<?php echo $result['id']; ?>" class="continue-btn">
                                    Continue Form
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <p>No forms found matching your search criteria.</p>
        <?php endif; ?>
    </div>
</body>
</html>