<?php
session_start();
// Include the database connection file
include '../includes/config.php';

$error_message = "";
$success_message = "";

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$factor = '400';
$conn->set_charset('utf8mb4');

if(isset($_SESSION['factor'])) $factor = $_SESSION['factor'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mg = $_POST['milligrams'];
    $dev = $_POST['device'];

    // Validate input
    if (!is_numeric($mg) || $mg <= 0 || !is_numeric($dev)) {
        $error_message = "Form submission failed, either milligrams was invalid or device not selected!";
        $form_values = [
            'milligrams' => $mg,
            'device' => $dev
        ];
    } else {
        $conn->begin_transaction();

        # last top up
        $stmtLastTopUp = $conn->prepare("SELECT id FROM pump_reservoir_history WHERE pump_id = ? AND `topup_to` IS NULL ORDER BY created_at DESC LIMIT 1");
        $stmtLastTopUp->bind_param('i', $dev);
        $stmtLastTopUp->execute();
        $stmtLastTopUp->bind_result($lastTopUp);
        $stmtLastTopUp->fetch();
        $stmtLastTopUp->close();

        $stmtUpdate = $conn->prepare("UPDATE pump_reservoir_history SET `topup_to` = NOW() WHERE id = ?");
        $stmtUpdate->bind_param('i', $lastTopUp);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $pumpQuery = "SELECT (
                (SELECT new_milligrams FROM pump_reservoir_history WHERE id = ?) -
                (SELECT COALESCE(SUM(dosage), 0) FROM pharmacy WHERE pump_id = pd.id AND dispDate >= (SELECT `topup_from` FROM pump_reservoir_history WHERE id = ?))
            ) AS rem FROM pump_devices pd WHERE pd.id = ?";
        $pumpStmt = $conn->prepare($pumpQuery);
        $pumpStmt->bind_param('iii', $lastTopUp, $lastTopUp, $dev);
        $pumpStmt->execute();
        $pumpResult = $pumpStmt->get_result();
        $pumpRow = $pumpResult->fetch_assoc();
        $remainingQuantity = $pumpRow['rem'] ?? 0;
        $newTotal = $remainingQuantity + $mg;

        $stmtCreate = $conn->prepare("INSERT INTO pump_reservoir_history (`milligrams`, new_milligrams, `topup_from`, `pump_id`) VALUES (?, ?, NOW(), ?)");
        $stmtCreate->bind_param('idi', $mg, $newTotal, $dev);
        $stmtCreate->execute();
        $stmtCreate->close();

        if ($conn->commit()) {
            $success_message = "Pump content updated successfully.";
        } else {
            $error_message = "Error: " . $conn->error;
        }
    }
}

$stmt_devices = $conn->prepare("SELECT * FROM pump_devices");
$stmt_devices->execute();
$result_devices = $stmt_devices->get_result();
$devices = $result_devices->fetch_all(MYSQLI_ASSOC);

// Pagination defaults
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Query pump_reservoir_history ordered by created_at DESC with pagination
$stmt_history = $conn->prepare("
    SELECT prs.id, prs.milligrams, prs.new_milligrams, prs.topup_from, prs.topup_to, prs.created_at, dev.label AS device_label, dev.port AS device_port,
    (SELECT COUNT(*) FROM pharmacy p WHERE p.pump_id = dev.id AND p.dispDate >= prs.topup_from AND (prs.topup_to IS NULL OR p.dispDate <= prs.topup_to)) AS dispenses
    FROM pump_reservoir_history prs INNER JOIN pump_devices dev ON prs.pump_id = dev.id ORDER BY created_at DESC LIMIT ? OFFSET ?;
");
$stmt_history->bind_param('ii', $limit, $offset);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$pump_history = $result_history->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();

$date_filters = [
    'today' => 'CURDATE()',
    'this_week' => '(CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY)',
    'this_month' => '(CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY)',
    'all_time' => '1800-01-01',
];

$sql_str_builder = [
    "(
        SELECT JSON_OBJECTAGG(id, rem) FROM (
            SELECT
            id,
            (
                (SELECT new_milligrams FROM pump_reservoir_history WHERE pump_id = pd.id AND `topup_to` IS NULL ORDER BY created_at DESC) -
                (SELECT COALESCE(SUM(dosage), 0) FROM pharmacy WHERE pump_id = pd.id AND dispDate >= (SELECT `topup_from` FROM pump_reservoir_history WHERE pump_id = pd.id AND `topup_to` IS NULL ORDER BY created_at DESC))
            ) AS rem
            FROM pump_devices pd GROUP BY id
        ) tbl
    ) AS remaining"
];

foreach ($date_filters as $filter => $date_filter) {
    $sql_str_builder[] = "
        (SELECT JSON_OBJECTAGG(pump, count) FROM
        (SELECT pd.id AS pump, COALESCE(pharmacy_totals.total_dosage, 0) AS count
         FROM pump_devices pd
         LEFT JOIN (
             SELECT pump_id, SUM(dosage) AS total_dosage
             FROM pharmacy
             WHERE dispDate >= $date_filter
             GROUP BY pump_id
         ) AS pharmacy_totals ON pd.id = pharmacy_totals.pump_id) {$filter}_pump)
        AS $filter
    ";
}

$summary = [];
$res = $conn->query('SELECT ' . implode(', ', $sql_str_builder));
if ($res) {
    $summary = $res->fetch_assoc();
    $res->free();
}

$summary = array_map(function ($v) {
    return json_decode($v ?? '{}', true);
}, $summary);

$sub_dir = '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pump Reservoir</title>
    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">-->
    <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
    <style>
        /* ... (Your existing CSS styles) ... */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background-color: #f5f7fa; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h2 { color: #2C3162; margin: 20px 0; text-align: center; }
        .stats-container { display: flex; justify-content: space-between; background-color: #deffee; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-item { text-align: center; padding: 10px; border-radius: 6px; flex: 1; margin: 0 10px; }
        .stat-remaining { background-color: #fff9c4; color: #d32f2f; border: 1px solid #ffd54f; }
        .stat-today { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        .stat-week { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        .stat-month { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        .stat-overral { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        .stat-value { font-size: 20px; font-weight: bold; display: block; }
        .stat-label { font-size: 14px; margin-top: 5px; }
        /* Repositioning the prescriptions-container and submit-btn */
        .prescriptions-container {
            margin-bottom: 20px; /* Space between table and button */
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .prescriptions-table { width: 100%; border-collapse: collapse; }
        .prescriptions-table th, .prescriptions-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .prescriptions-table th { background-color: #f2f2f2; font-weight: bold; }
        .prescriptions-table input[type="number"] { width: 80px; padding: 5px; }
        .prescriptions-table input[type="checkbox"] { transform: scale(1.2); }
        .custom-alert { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: yellow; color: red; border: 2px solid red; padding: 20px; width: 300px; text-align: center; z-index: 1000; border-radius: 8px; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .custom-alert button { margin-top: 10px; padding: 8px 16px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .custom-alert button:hover { background-color: darkred; }
        .missed-appointment { color: red; font-weight: bold; }
    </style>
</head>

<body>
    <div><?php //echo $projectRoot; ?></div>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Pump Reservoir</h2>

            <div class="form-group">
                <label for="filter-select">Filter</label>
                <select class="form-control" id="filter-select" onchange="filterSummaryValues(this.value)">
                    <option selected>All</option>
                    <option value="undefined">Unspecified</option>
                    <?php foreach ($devices as $row): ?>
                        <option value="<?php echo strval($row['id']); ?>"><?php echo $row['label']; ?> (<?php echo $row['port']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-item stat-remaining">
                <span class="stat-value" id="stat-remaining">0</span>
                <span class="stat-label">Millilitres(ML) remaining</span>
            </div>
            
            <div class="stat-item stat-today">
                <span class="stat-value" id="stat-today">0</span>
                <span class="stat-label">Milligrams dispensed today</span>
            </div>

            <div class="stat-item stat-week">
                <span class="stat-value" id="stat-week">0</span>
                <span class="stat-label">Milligrams dispensed this week</span>
            </div>

            <div class="stat-item stat-month">
                <span class="stat-value" id="stat-month">0</span>
                <span class="stat-label">Milligrams dispensed this month</span>
            </div>

            <div class="stat-item stat-overral">
                <span class="stat-value" id="stat-overral">0</span>
                <span class="stat-label">Milligrams dispensed overral</span>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="d-block d-lg-flex justify-content-between align-items-center">
            <h2>History</h2>

            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#pump-content-update">
                Switch/Top up content
            </button>
			
			<button type="button" onclick="pump('D')" class="btn btn-primary">
                Prime 
            </button>
			
			<button type="button" onclick="calibrate()" class="btn btn-primary">
                Calibrate (<?php echo $factor; ?>)
            </button>
			
			<button type="button" onclick="pump('P')" class="btn btn-primary">
                Reverse Prime
            </button>
				

			<script>
				function pump(opt){	
					const xhr = new XMLHttpRequest();
					var port = "&port=<?php echo $row['port'] ?>";
					
					xhr.open("GET","http://localhost/iorpms/iorpms/api.php?cmd="+ opt + port, true);
										
					xhr.onreadystatechange = function () {
						if (xhr.readyState === 4) {
							if (xhr.status === 200) { alert(opt +' = '+ xhr.responseText);
								//console.log("Response:", xhr.responseText);
								
							} else {
								alert("Error:"+ xhr.status +' - '+ xhr.responseText);
							}
							//alert(23);
						}
					};
					//alert(34545);
					xhr.send();
					//alert(opt); /**/
				}
				
				function calibrate(){
					var factor = prompt('Enter the dispenced amount')
					
					if(factor) pump(factor);
				}
			</script>
        </div>
		<br>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Device</th>
                    <th scope="col">Milligrams</th>
                    <th scope="col">New milligrams</th>
                    <th scope="col">From</th>
                    <th scope="col">To</th>
                    <th scope="col">Dispenses</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($pump_history as $row): ?>
                    <tr>
                        <td scope="row"><?php echo htmlspecialchars($row['device_label']); ?> (<?php echo htmlspecialchars($row['device_port']); ?>)</td>
                        <td><?php echo htmlspecialchars($row['milligrams']); ?></td>
                        <td><?php echo htmlspecialchars($row['new_milligrams']); ?></td>
                        <td><?php echo htmlspecialchars($row['topup_from']); ?></td>
                        <td><?php echo htmlspecialchars($row['topup_to'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['dispenses'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pump_history)): ?>
                    <tr>
                        <td scope="row" colspan="5" class="text-center">No history available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="pump-content-update" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Switch/Top up content</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-warning" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <form id="dispenseForm" action="pump_reservoir.php" method="post">
                        <div class="form-group">
                            <label for="device-select">Pump device:</label>
                            <select class="form-control" name="device" id="device-select" required>
                                <?php if (count($devices) !== 1): ?>
                                    <option value="" disabled hidden selected>select device</option>
                                <?php endif; ?>
                                <?php foreach ($devices as $row): ?>
                                    <option value="<?php echo $row['id'] ?>" <?php if (count($devices) === 1 || (isset($form_values['device']) && $form_values['device'] === $row['id'])) echo 'selected' ?>>
                                        <?php echo $row['label'] ?> (<?php echo $row['port'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="milligrams">Milligrams:</label>
                            <input class="form-control" type="number" placeholder="milligrams" name="milligrams" id="milligrams" step="5" min="5" max="12000" required value="<?php echo isset($form_values['milligrams']) ? $form_values['milligrams'] : ''; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>

    <script>
        const jsonData = JSON.parse(`<?php echo json_encode($summary); ?>`);

        function objTotal(obj) {
            return Object.values(obj || {}).reduce((acc, val) => acc + (val ?? 0), 0);
        }

        function filterSummaryValues(filterValue) {
            if (!filterValue) {
                document.getElementById('stat-today').textContent = objTotal(jsonData['today']);
                document.getElementById('stat-week').textContent = objTotal(jsonData['this_week']);
                document.getElementById('stat-month').textContent = objTotal(jsonData['this_month']);
                document.getElementById('stat-overral').textContent = objTotal(jsonData['all_time']);
                document.getElementById('stat-remaining').textContent = objTotal(jsonData['remaining']);
            } else {
                document.getElementById('stat-today').textContent = jsonData['today'][filterValue] || 0;
                document.getElementById('stat-week').textContent = jsonData['this_week'][filterValue] || 0;
                document.getElementById('stat-month').textContent = jsonData['this_month'][filterValue] || 0;
                document.getElementById('stat-overral').textContent = jsonData['all_time'][filterValue] || 0;
                document.getElementById('stat-remaining').textContent = jsonData['remaining'][filterValue] || 0;
            }
        }

        window.addEventListener('DOMContentLoaded', (event) => {
            console.log(jsonData);
            filterSummaryValues();
        });
    </script>

    <?php if(!empty($error_message)): ?>
        <script>
            $('#pump-content-update').modal('show');
        </script>
    <?php endif; ?>
</body>
</html>
