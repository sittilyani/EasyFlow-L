<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search_term = $_POST['search_term'] ?? '';

    if (!empty($search_term)) {
        $sql = "SELECT p_id, clientName, sname, mat_id, age, sex, reg_date
                FROM patients
                WHERE clientName LIKE ? OR mat_id LIKE ? OR sname LIKE ?";
        $stmt = $conn->prepare($sql);
        $search_like = "%$search_term%";
        $stmt->bind_param('sss', $search_like, $search_like, $search_like);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patient</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 30px; }
        .search-form { margin-bottom: 30px; }
        .search-input { padding: 12px; width: 400px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; }
        .search-button { padding: 12px 24px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .search-button:hover { background: #2980b9; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .results-table th, .results-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .results-table th { background: #f8f9fa; font-weight: 600; }
        .select-btn { padding: 8px 16px; background: #27ae60; color: white; text-decoration: none; border-radius: 4px; }
        .select-btn:hover { background: #219a52; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Search Patient for Clinical Encounter</h1>

        <div class="search-form">
            <form method="POST">
                <input type="text" name="search_term" class="search-input" placeholder="Search by Client Name, Surname or MAT ID..."
                       value="<?php echo htmlspecialchars($_POST['search_term'] ?? ''); ?>">
                <button type="submit" name="search" class="search-button">Search</button>
            </form>
        </div>

        <?php if (!empty($results)): ?>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>MAT ID</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Registration Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['clientName'] . ' ' . $result['sname']); ?></td>
                        <td><?php echo htmlspecialchars($result['mat_id']); ?></td>
                        <td><?php echo htmlspecialchars($result['age']); ?></td>
                        <td><?php echo htmlspecialchars($result['sex']); ?></td>
                        <td><?php echo htmlspecialchars($result['reg_date']); ?></td>
                        <td>
                            <a href="clinician_initial_encounter_form.php?p_id=<?php echo $result['p_id']; ?>" class="select-btn">
                                Select Patient
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <p>No patients found matching your search criteria.</p>
        <?php endif; ?>
    </div>
</body>
</html>