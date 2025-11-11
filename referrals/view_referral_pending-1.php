<?php
include('../includes/config.php');

// Check if referral_id is provided
if (!isset($_GET['referral_id']) || empty($_GET['referral_id'])) {
    die("Referral ID is required.");
}

$referral_id = $_GET['referral_id'];

// Fetch referral details using referral_id
$sql = "SELECT * FROM referral WHERE referral_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $referral_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No referral found.");
}

$referral = $result->fetch_assoc();
$stmt->close();

// Get mat_id from the fetched referral for use in links
$mat_id = $referral['mat_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Referral - <?php echo htmlspecialchars($referral['mat_id']); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        .container {
            width: 80%;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .page-title {
            font-size: 2.2rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            font-style: italic;
        }

        .referral-details {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .detail-group {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }

        .detail-group:hover {
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.1);
            transform: translateY(-2px);
        }

        .detail-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 700;
            color: #495057;
            min-width: 140px;
            margin-right: 1rem;
            font-size: 0.95rem;
        }

        .detail-value {
            flex: 1;
            color: #2c3e50;
            font-size: 1rem;
        }

        /* Special styling for specific fields */
        .status-value {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notes-value {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            font-style: italic;
            line-height: 1.5;
            border-left: 4px solid #0056b3;
        }

        .mat-id-value {
            font-family: 'Courier New', monospace;
            background: #e9ecef;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-weight: 600;
            color: #495057;
        }

        .date-value {
            color: #28a745;
            font-weight: 600;
        }

        /* Button section */
        .action-buttons {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .btn-custom {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
            display: inline-block;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            border: none;
        }

        .btn-secondary-custom:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .detail-row {
                flex-direction: column;
            }

            .detail-label {
                min-width: auto;
                margin-bottom: 0.3rem;
            }

            .btn-custom {
                display: block;
                margin: 0.5rem 0;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container fade-in">
    <div class="page-header">
        <h1 class="page-title">Referral Details</h1>
        <p class="page-subtitle">Complete referral information and status</p>
    </div>

    <div class="referral-details">
        <div class="detail-group">
            <div class="detail-row">
                <span class="detail-label">Referral ID:</span>
                <span class="detail-value mat-id-value"><?php echo htmlspecialchars($referral['referral_id']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">MAT ID:</span>
                <span class="detail-value mat-id-value"><?php echo htmlspecialchars($referral['mat_id']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Client Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($referral['clientName']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Age:</span>
                <span class="detail-value"><?php echo htmlspecialchars($referral['age']); ?> years</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Sex:</span>
                <span class="detail-value"><?php echo htmlspecialchars($referral['sex']); ?></span>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-row">
                <span class="detail-label">Refer From:</span>
                <span class="detail-value"><?php echo htmlspecialchars($referral['refer_from']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Refer To:</span>
                <span class="detail-value"><?php echo htmlspecialchars($referral['refer_to']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Referring Officer:</span>
                <span class="detail-value"><?php echo htmlspecialchars($referral['referral_name']); ?></span>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span class="status-value"><?php echo htmlspecialchars($referral['status']); ?></span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value date-value"><?php echo htmlspecialchars($referral['referral_date']); ?></span>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-row">
                <span class="detail-label">Referral Notes:</span>
                <div class="detail-value">
                    <div class="notes-value">
                        <?php echo nl2br(htmlspecialchars($referral['referral_notes'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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