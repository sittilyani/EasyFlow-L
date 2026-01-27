<?php
session_start();
include "../includes/config.php";

$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'capture';
$scannerType = isset($_GET['scanner']) ? $_GET['scanner'] : 'zkteco'; // Default to ZKTeco

// Fetch patient details
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

// Check if fingerprint exists
$existingPrint = null;
if ($userId && $currentSettings) {
    $printQuery = "SELECT id, capture_date FROM fingerprints WHERE mat_id = ? ORDER BY capture_date DESC LIMIT 1";
    $printStmt = $conn->prepare($printQuery);
    $printStmt->bind_param('s', $currentSettings['mat_id']);
    $printStmt->execute();
    $printResult = $printStmt->get_result();
    $existingPrint = $printResult->fetch_assoc();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $visitDate = $_POST['visitDate'];
    $mat_id = $_POST['mat_id'];
    $mat_number = $_POST['mat_number'] ?? '';
    $clientName = $_POST['clientName'];
    $nickName = $_POST['nickName'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $sex = $_POST['sex'];
    $current_status = $_POST['current_status'];
    $fingerprint_type = $_POST['fingerprint_type'] ?? 'Index';
    $scanner_type = $_POST['scanner_type'] ?? 'ZKTeco';
    $formAction = $_POST['action'];

    // Get fingerprint data (binary data from scanner)
    $fingerprint_data = null;
    $template_data = null;

    // Handle different scanner data formats
    if (isset($_POST['fingerprint_data_base64'])) {
        // Base64 encoded fingerprint image
        $fingerprint_data = base64_decode($_POST['fingerprint_data_base64']);
    } elseif (isset($_POST['fingerprint_data_binary'])) {
        // Binary data from file upload
        $fingerprint_data = file_get_contents($_FILES['fingerprint_file']['tmp_name']);
    } elseif (isset($_POST['fingerprint_template'])) {
        // Template data (for verification/matching)
        $template_data = base64_decode($_POST['fingerprint_template']);
    }

    // Check if we have fingerprint data
    if (empty($fingerprint_data) && empty($template_data)) {
        die(json_encode(['success' => false, 'message' => 'No fingerprint data received.']));
    }

    // Calculate quality score (simplified - you can implement real quality check)
    $quality_score = isset($_POST['quality_score']) ? intval($_POST['quality_score']) : 85;

    if ($formAction === 'update' && $existingPrint) {
        // Update existing fingerprint record
        if ($fingerprint_data) {
            $sql = "UPDATE fingerprints SET
                    visitDate = ?,
                    fingerprint_data = ?,
                    template_data = ?,
                    quality_score = ?,
                    fingerprint_type = ?,
                    scanner_type = ?,
                    capture_date = NOW()
                    WHERE mat_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sbbisss",
                $visitDate,
                $fingerprint_data,
                $template_data,
                $quality_score,
                $fingerprint_type,
                $scanner_type,
                $mat_id);
        } else {
            $sql = "UPDATE fingerprints SET
                    visitDate = ?,
                    template_data = ?,
                    quality_score = ?,
                    fingerprint_type = ?,
                    scanner_type = ?,
                    capture_date = NOW()
                    WHERE mat_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sbisss",
                $visitDate,
                $template_data,
                $quality_score,
                $fingerprint_type,
                $scanner_type,
                $mat_id);
        }
        $successMessage = "Fingerprint updated successfully.";
    } else {
        // Insert new fingerprint record
        $sql = "INSERT INTO fingerprints
                (visitDate, mat_id, mat_number, clientName, nickName, dob, sex, current_status,
                 fingerprint_data, template_data, quality_score, fingerprint_type, scanner_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssbbiss",
            $visitDate, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status,
            $fingerprint_data, $template_data, $quality_score, $fingerprint_type, $scanner_type);
        $successMessage = "Fingerprint registered successfully.";
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $successMessage,
            'redirect' => 'fingerprint_search.php?message=' . urlencode($successMessage)
        ]);
        exit();
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Database error: " . $conn->error
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fingerprint <?php echo ucfirst($action); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2C3162 0%, #1a1f4b 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header h2 {
            font-size: 20px;
            font-weight: normal;
            opacity: 0.9;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        .patient-info, .scanner-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .patient-info h3, .scanner-section h3 {
            color: #2C3162;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .readonly-input {
            background-color: #f1f3f4;
            cursor: not-allowed;
        }

        .scanner-container {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            margin: 20px 0;
        }

        .scanner-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(102, 126, 234, 0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .status-container {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .status-info {
            background: #e9ecef;
            color: #495057;
        }

        .scanner-selector {
            margin-bottom: 20px;
        }

        .scanner-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }

        .fingerprint-preview {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            display: none;
        }

        .fingerprint-preview canvas {
            width: 100%;
            height: 100%;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .existing-print {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            border-left: 4px solid #007bff;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Fingerprint <?php echo ucfirst($action); ?></h1>
            <h2><?php echo htmlspecialchars($currentSettings['clientName']); ?> (MAT ID: <?php echo htmlspecialchars($currentSettings['mat_id']); ?>)</h2>
        </div>

        <?php if ($action === 'update' && $existingPrint): ?>
        <div class="existing-print">
            <strong>⚠ Existing Fingerprint Registered</strong><br>
            Last captured: <?php echo date('Y-m-d H:i', strtotime($existingPrint['capture_date'])); ?>
        </div>
        <?php endif; ?>

        <div class="content">
            <div class="patient-info">
                <h3>Patient Information</h3>

                <div class="form-group">
                    <label>Visit Date:</label>
                    <input type="text" name="visitDate" class="form-control readonly-input"
                           value="<?php echo date('Y-m-d H:i'); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>MAT ID:</label>
                    <input type="text" name="mat_id" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['mat_id']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>MAT Number:</label>
                    <input type="text" name="mat_number" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['mat_number']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Client Name:</label>
                    <input type="text" name="clientName" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['clientName']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Sex:</label>
                    <input type="text" name="sex" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['sex']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Current Status:</label>
                    <input type="text" name="current_status" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['current_status']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Fingerprint Type:</label>
                    <select name="fingerprint_type" class="form-control">
                        <option value="Thumb">Thumb</option>
                        <option value="Index" selected>Index Finger</option>
                        <option value="Middle">Middle Finger</option>
                        <option value="Ring">Ring Finger</option>
                        <option value="Little">Little Finger</option>
                    </select>
                </div>
            </div>

            <div class="scanner-section">
                <h3>Fingerprint Scanner</h3>

                <div class="scanner-selector">
                    <label>Select Scanner Type:</label>
                    <select id="scanner-type" class="form-control" onchange="changeScannerType(this.value)">
                        <option value="zkteco" <?php echo $scannerType === 'zkteco' ? 'selected' : ''; ?>>ZKTeco Scanner</option>
                        <option value="suprema" <?php echo $scannerType === 'suprema' ? 'selected' : ''; ?>>Suprema Scanner</option>
                        <option value="digitalpersona" <?php echo $scannerType === 'digitalpersona' ? 'selected' : ''; ?>>DigitalPersona Scanner</option>
                        <option value="secugen" <?php echo $scannerType === 'secugen' ? 'selected' : ''; ?>>SecuGen Scanner</option>
                        <option value="generic" <?php echo $scannerType === 'generic' ? 'selected' : ''; ?>>Generic USB Scanner</option>
                    </select>
                </div>

                <div class="scanner-container">
                    <h4 id="scanner-title">ZKTeco Fingerprint Scanner</h4>
                    <p id="scanner-instructions">Place your finger on the scanner and click "Start Capture"</p>

                    <div class="scanner-controls">
                        <button type="button" id="init-scanner" class="btn btn-primary" onclick="initializeScanner()">
                            <span class="btn-text">Initialize Scanner</span>
                            <span class="loading" style="display:none;"></span>
                        </button>
                        <button type="button" id="start-capture" class="btn btn-success" onclick="startCapture()" disabled>
                            Start Capture
                        </button>
                        <button type="button" id="stop-capture" class="btn btn-danger" onclick="stopCapture()" disabled>
                            Stop Capture
                        </button>
                    </div>

                    <div id="scanner-status" class="status-container status-info">
                        Scanner not initialized
                    </div>

                    <div class="fingerprint-preview" id="fingerprint-preview">
                        <canvas id="fingerprint-canvas" width="400" height="400"></canvas>
                    </div>

                    <div id="quality-indicator" style="margin: 20px 0; display: none;">
                        <label>Quality Score: <span id="quality-score">0</span>/100</label>
                        <div style="background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden;">
                            <div id="quality-bar" style="background: #28a745; height: 100%; width: 0%;"></div>
                        </div>
                    </div>
                </div>

                <button type="button" id="btn-submit" class="btn btn-primary"
                        onclick="submitFingerprint()" style="width: 100%; padding: 15px;" disabled>
                    <span id="submit-text"><?php echo $action === 'update' ? 'Update Fingerprint' : 'Save Fingerprint'; ?></span>
                    <span class="loading" style="display:none;"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="fingerprint-form" style="display: none;">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="visitDate" value="<?php echo date('Y-m-d H:i'); ?>">
        <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($currentSettings['mat_id']); ?>">
        <input type="hidden" name="mat_number" value="<?php echo htmlspecialchars($currentSettings['mat_number']); ?>">
        <input type="hidden" name="clientName" value="<?php echo htmlspecialchars($currentSettings['clientName']); ?>">
        <input type="hidden" name="nickName" value="<?php echo htmlspecialchars($currentSettings['nickName'] ?? ''); ?>">
        <input type="hidden" name="dob" value="<?php echo htmlspecialchars($currentSettings['dob'] ?? ''); ?>">
        <input type="hidden" name="sex" value="<?php echo htmlspecialchars($currentSettings['sex']); ?>">
        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($currentSettings['current_status']); ?>">
        <input type="hidden" name="fingerprint_type" id="form-fingerprint-type" value="Index">
        <input type="hidden" name="scanner_type" id="form-scanner-type" value="ZKTeco">
        <input type="hidden" name="fingerprint_data_base64" id="fingerprint-data-base64">
        <input type="hidden" name="fingerprint_template" id="fingerprint-template">
        <input type="hidden" name="quality_score" id="quality-score-input" value="85">
    </form>

    <script>
        // Global variables
        let currentScanner = null;
        let isScannerInitialized = false;
        let isCapturing = false;
        let fingerprintData = null;
        let templateData = null;
        let qualityScore = 0;
        let scannerType = '<?php echo $scannerType; ?>';

        // Scanner configurations
        const scannerConfigs = {
            zkteco: {
                name: 'ZKTeco Scanner',
                instructions: 'Place your finger firmly on the scanner surface',
                initScript: 'zkteco-sdk.js',
                initFunction: initZKTecoScanner
            },
            suprema: {
                name: 'Suprema Scanner',
                instructions: 'Place your finger on the biometric scanner',
                initScript: 'suprema-sdk.js',
                initFunction: initSupremaScanner
            },
            digitalpersona: {
                name: 'DigitalPersona Scanner',
                instructions: 'Place your finger on the fingerprint reader',
                initScript: 'digitalpersona-sdk.js',
                initFunction: initDigitalPersonaScanner
            },
            secugen: {
                name: 'SecuGen Scanner',
                instructions: 'Place your finger on the optical scanner',
                initScript: 'secugen-sdk.js',
                initFunction: initSecuGenScanner
            },
            generic: {
                name: 'Generic USB Scanner',
                instructions: 'Place your finger on the USB fingerprint scanner',
                initScript: null,
                initFunction: initGenericScanner
            }
        };

        function changeScannerType(type) {
            scannerType = type;
            const config = scannerConfigs[type];

            // Update UI
            document.getElementById('scanner-title').textContent = config.name;
            document.getElementById('scanner-instructions').textContent = config.instructions;
            document.getElementById('form-scanner-type').value = config.name;

            // Reset scanner state
            resetScanner();

            // Load scanner SDK if needed
            if (config.initScript && !document.querySelector(`script[src="${config.initScript}"]`)) {
                loadScript(config.initScript);
            }
        }

        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        function resetScanner() {
            isScannerInitialized = false;
            isCapturing = false;
            fingerprintData = null;
            templateData = null;

            document.getElementById('start-capture').disabled = true;
            document.getElementById('stop-capture').disabled = true;
            document.getElementById('btn-submit').disabled = true;
            document.getElementById('scanner-status').className = 'status-container status-info';
            document.getElementById('scanner-status').textContent = 'Scanner not initialized';
            document.getElementById('fingerprint-preview').style.display = 'none';
            document.getElementById('quality-indicator').style.display = 'none';
        }

        async function initializeScanner() {
            const config = scannerConfigs[scannerType];
            const btn = document.getElementById('init-scanner');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');

            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            btn.disabled = true;

            try {
                updateStatus('Initializing scanner...', 'info');

                // Initialize the selected scanner
                if (config.initFunction) {
                    await config.initFunction();
                }

                isScannerInitialized = true;
                document.getElementById('start-capture').disabled = false;
                updateStatus('✓ Scanner initialized and ready', 'success');

            } catch (error) {
                updateStatus('✗ Scanner initialization failed: ' + error.message, 'error');
                console.error('Scanner init error:', error);
            } finally {
                btnText.style.display = 'inline-block';
                loading.style.display = 'none';
                btn.disabled = false;
            }
        }

        async function startCapture() {
            if (!isScannerInitialized) {
                updateStatus('Please initialize the scanner first', 'error');
                return;
            }

            isCapturing = true;
            document.getElementById('start-capture').disabled = true;
            document.getElementById('stop-capture').disabled = false;

            updateStatus('Capturing... Please place your finger on the scanner', 'info');

            // Simulate fingerprint capture (replace with actual scanner API call)
            simulateFingerprintCapture();
        }

        function stopCapture() {
            isCapturing = false;
            document.getElementById('start-capture').disabled = false;
            document.getElementById('stop-capture').disabled = true;
            updateStatus('Capture stopped', 'info');
        }

        function simulateFingerprintCapture() {
            // This is a simulation - replace with actual scanner API calls

            // Simulate capture delay
            setTimeout(() => {
                if (!isCapturing) return;

                // Generate simulated fingerprint data
                const canvas = document.getElementById('fingerprint-canvas');
                const ctx = canvas.getContext('2d');

                // Create a fingerprint-like pattern
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Draw fingerprint ridges
                ctx.strokeStyle = '#495057';
                ctx.lineWidth = 2;
                ctx.beginPath();

                for (let i = 0; i < 50; i++) {
                    const x = Math.random() * canvas.width;
                    const y = Math.random() * canvas.height;
                    const radius = 10 + Math.random() * 30;
                    const startAngle = Math.random() * Math.PI * 2;
                    const endAngle = startAngle + Math.PI * (0.5 + Math.random() * 0.5);

                    ctx.moveTo(x, y);
                    ctx.arc(x, y, radius, startAngle, endAngle);
                }
                ctx.stroke();

                // Convert canvas to base64 (simulated fingerprint data)
                fingerprintData = canvas.toDataURL('image/png').split(',')[1];

                // Generate simulated template data
                templateData = btoa('SIMULATED_TEMPLATE_' + Date.now() + '_' + Math.random());

                // Generate quality score (70-100)
                qualityScore = 70 + Math.floor(Math.random() * 30);

                // Update UI
                document.getElementById('fingerprint-preview').style.display = 'block';
                document.getElementById('quality-indicator').style.display = 'block';
                document.getElementById('quality-score').textContent = qualityScore;
                document.getElementById('quality-bar').style.width = qualityScore + '%';
                document.getElementById('quality-score-input').value = qualityScore;

                // Update form data
                document.getElementById('fingerprint-data-base64').value = fingerprintData;
                document.getElementById('fingerprint-template').value = templateData;
                document.getElementById('form-fingerprint-type').value =
                    document.querySelector('select[name="fingerprint_type"]').value;

                // Enable submit button
                document.getElementById('btn-submit').disabled = false;

                updateStatus('✓ Fingerprint captured successfully! Quality: ' + qualityScore + '/100', 'success');

            }, 2000); // 2 second simulation delay
        }

        // Scanner initialization functions (stubs - implement with actual SDK)
        async function initZKTecoScanner() {
            // Implementation for ZKTeco SDK
            // Load ZKTeco SDK and initialize device
            updateStatus('Initializing ZKTeco device...', 'info');
            await new Promise(resolve => setTimeout(resolve, 1000)); // Simulate delay

            // Real ZKTeco SDK code would go here:
            // 1. Load ZKFingerReader SDK
            // 2. Initialize device
            // 3. Set callback for fingerprint capture
            // 4. Enable device
        }

        async function initSupremaScanner() {
            // Implementation for Suprema SDK
            updateStatus('Initializing Suprema device...', 'info');
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        async function initDigitalPersonaScanner() {
            // Implementation for DigitalPersona SDK
            updateStatus('Initializing DigitalPersona device...', 'info');
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        async function initSecuGenScanner() {
            // Implementation for SecuGen SDK
            updateStatus('Initializing SecuGen device...', 'info');
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        async function initGenericScanner() {
            // Implementation for generic USB scanner using WebUSB API
            updateStatus('Searching for USB fingerprint devices...', 'info');

            try {
                // WebUSB API for generic scanners
                const device = await navigator.usb.requestDevice({
                    filters: [{ vendorId: 0x1FC9 }] // Example vendor ID
                });

                await device.open();
                await device.selectConfiguration(1);
                await device.claimInterface(0);

                updateStatus('Generic USB scanner connected', 'success');
            } catch (error) {
                updateStatus('No USB fingerprint device found', 'error');
                throw error;
            }
        }

        function updateStatus(message, type = 'info') {
            const statusElement = document.getElementById('scanner-status');
            statusElement.textContent = message;
            statusElement.className = 'status-container status-' + type;
        }

        async function submitFingerprint() {
            const btn = document.getElementById('btn-submit');
            const btnText = document.getElementById('submit-text');
            const loading = btn.querySelector('.loading');

            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            btn.disabled = true;

            try {
                updateStatus('Saving fingerprint data...', 'info');

                const form = document.getElementById('fingerprint-form');
                const formData = new FormData(form);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    updateStatus('✓ ' + result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    updateStatus('✗ ' + result.message, 'error');
                    btnText.style.display = 'inline-block';
                    loading.style.display = 'none';
                    btn.disabled = false;
                }

            } catch (error) {
                updateStatus('✗ Error: ' + error.message, 'error');
                console.error('Submit error:', error);
                btnText.style.display = 'inline-block';
                loading.style.display = 'none';
                btn.disabled = false;
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set fingerprint type
            document.getElementById('form-fingerprint-type').value =
                document.querySelector('select[name="fingerprint_type"]').value;

            // Initialize scanner type display
            changeScannerType(scannerType);
        });
    </script>
</body>
</html>