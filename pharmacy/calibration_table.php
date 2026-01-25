<?php
include '../includes/config.php';
// Get calibration history
$calHistoryQuery = "SELECT pc.*, pd.label as pump_label, pd.port as pump_port
                    FROM pump_calibration pc
                    JOIN pump_devices pd ON pc.pump_id = pd.id
                    ORDER BY pc.calibrated_at DESC LIMIT 10";
$calHistoryResult = $conn->query($calHistoryQuery);
$calibration_history = $calHistoryResult->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pump Calibration History</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2C3162 0%, #4B0082 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header .icon {
            font-size: 2.5rem;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .content {
            padding: 30px;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .stat-info h3 {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }

        .stat-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 900px;
        }

        thead {
            background: linear-gradient(135deg, #2C3162 0%, #4B0082 100%);
            color: white;
        }

        thead th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #f8f9ff 0%, #fff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        tbody td {
            padding: 15px;
            font-size: 0.95rem;
            color: #333;
        }

        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .pump-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .concentration-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #28a745;
            color: white;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .tubing-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #17a2b8;
            color: white;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .date-cell {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .notes-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: help;
        }

        .notes-cell:hover {
            white-space: normal;
            overflow: visible;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }


    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fa fa-bar-chart"></i>
                Pump Calibration History
            </h1>
        </div>

        <div class="content">
            <!-- Statistics Bar -->
            <div class="stats-bar">
                <div class="stat-card">
                    <i class="fa fa-cog"></i>
                    <div class="stat-info">
                        <h3><?php echo count($calibration_history); ?></h3>
                        <p>Recent Calibrations</p>
                    </div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fa fa-calendar"></i>
                    <div class="stat-info">
                        <h3><?php echo !empty($calibration_history) ? date('M d', strtotime($calibration_history[0]['calibrated_at'])) : 'N/A'; ?></h3>
                        <p>Latest Calibration</p>
                    </div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fa fa-forumbee"></i>
                    <div class="stat-info">
                        <h3><?php echo !empty($calibration_history) ? count(array_unique(array_column($calibration_history, 'pump_id'))) : 0; ?></h3>
                        <p>Unique Pumps</p>
                    </div>
                </div>
            </div>

            <!-- Calibration Table -->
            <div class="table-wrapper">
                <?php if (!empty($calibration_history)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-pump-medical"></i> Pump</th>
                            <th><i class="fas fa-calculator"></i> Factor</th>
                            <th><i class="fas fa-flask"></i> Concentration</th>
                            <th><i class="fas fa-tube"></i> Tubing</th>
                            <th><i class="fas fa-user"></i> Calibrated By</th>
                            <th><i class="fas fa-calendar-alt"></i> Date</th>
                            <th><i class="fas fa-note-sticky"></i> Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calibration_history as $history): ?>
                        <tr>
                            <td>
                                <span class="pump-badge">
                                    <?php echo htmlspecialchars($history['pump_label']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo number_format($history['calibration_factor'], 4); ?></strong>
                            </td>
                            <td>
                                <span class="concentration-badge">
                                    <?php echo $history['concentration_mg_per_ml']; ?> mg/mL
                                </span>
                            </td>
                            <td>
                                <span class="tubing-type">
                                    <i class="fas fa-circle-check"></i>
                                    <?php echo htmlspecialchars(isset($history['tubing_type']) ? $history['tubing_type'] : 'system'); ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-user-gear" style="color: #667eea; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($history['calibrated_by']); ?>
                            </td>
                            <td class="date-cell">
                                <i class="far fa-calendar" style="margin-right: 5px;"></i>
                                <?php echo date('M d, Y', strtotime($history['calibrated_at'])); ?>
                                <br>
                                <small style="color: #adb5bd;">
                                    <i class="far fa-clock" style="margin-right: 3px;"></i>
                                    <?php echo date('H:i', strtotime($history['calibrated_at'])); ?>
                                </small>
                            </td>
                            <td class="notes-cell" title="<?php echo htmlspecialchars($history['notes']); ?>">
                                <?php echo htmlspecialchars($history['notes']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Calibration History</h3>
                    <p>No calibration records found in the system.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.js"></script>
</body>
</html>