<?php
session_start();
// Include your database configuration (ensure this path is correct)
include '../includes/config.php';

// --- Placeholder for Logged-in User Data ---
$loggedInUserId = $_SESSION['user_id'] ?? null;

// Function to fetch a user's full name from the database
function getUserFullName($conn, $userId) {
    if (!$userId || $conn->connect_error) return "N/A";
    $sql = "SELECT full_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return "Error preparing user query";

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return htmlspecialchars($row['full_name']);
    }
    return "Unknown User";
}

// Fetch the current user's name (This is used to pre-populate the 'Requester' input)
$loggedInUserName = getUserFullName($conn, $loggedInUserId);


// --- Data Fetching for Dropdowns ---

// Fetch available drugs (assuming 'drug' table has drugID and drugname)
$drugs = [];
if (isset($conn) && !$conn->connect_error) {
    $sql_drugs = "SELECT drugID, drugname FROM drug
                  WHERE drugname IN ('Methadone', 'Buprenorphine 2mg', 'Buprenorphine 4mg',
                                    'Buprenorphine 8mg', 'Naltrexone 50mg', 'Naltrexone 100mg',
                                    'Naltrexone 150mg', 'Naltrexone Implant')
                  ORDER BY drugname ASC";
    $result_drugs = $conn->query($sql_drugs);
    if ($result_drugs) while ($row = $result_drugs->fetch_assoc()) $drugs[] = $row;
}

// Fetch suppliers (assuming 'suppliers' table has a 'name' column)
$suppliers = [];
if (isset($conn) && !$conn->connect_error) {
    $sql_suppliers = "SELECT name FROM suppliers ORDER BY name ASC";
    $result_suppliers = $conn->query($sql_suppliers);
    if ($result_suppliers) while ($row = $result_suppliers->fetch_assoc()) $suppliers[] = $row['name'];
}

// Fetch list of ALL users for the 'Issuer' and 'Receiver' dropdowns (SIMPLIFIED)
$allUsers = [];
if (isset($conn) && !$conn->connect_error) {
    // Note: Fetching ALL users for flexibility as requested
    $sql_all_users = "SELECT full_name FROM tblusers ORDER BY full_name ASC";
    $result_all_users = $conn->query($sql_all_users);
    if ($result_all_users) while ($row = $result_all_users->fetch_assoc()) $allUsers[] = $row['full_name'];
}


// --- PHP FORM SUBMISSION LOGIC ---

$message = '';
$currentBalance = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize common fields
    $drugId = intval($_POST['drug_id'] ?? 0);
    $drugName = trim($_POST['drugname_text'] ?? 'Unknown Drug');
    $transactionType = $_POST['transaction_type'] ?? '';

    // Get Requester Name (Always the logged-in user in this system design)
    $requesterName = $loggedInUserName;

    // Date Formatting (Robust fix)
    $transactionDateRaw = trim($_POST['transaction_date'] ?? '');
    if (!empty($transactionDateRaw)) {
        $transactionDate = str_replace('T', ' ', $transactionDateRaw);
        if (substr_count($transactionDate, ':') === 1) $transactionDate .= ':00';
        try {
            $dateObj = new DateTime($transactionDate);
            $transactionDate = $dateObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $transactionDate = date('Y-m-d H:i:s');
        }
    } else {
        $transactionDate = date('Y-m-d H:i:s');
    }

    if (!$drugId) { $message = "<div class='error-message'>Please select a valid Drug.</div>"; goto end_of_post; }

    // 2. Determine quantity and related fields based on type
    $fromSupplier = 0; $toDispensing = 0; $quantity = 0;
    $supplierName = null;
    $receivedBy = null;   // Column received_by_full_name
    $issuedTo = null;     // Column issued_to_full_name

    if ($transactionType === 'receipt') {
        $quantity = intval($_POST['from_supplier'] ?? 0);
        $supplierName = trim($_POST['supplier_name'] ?? '');
        $fromSupplier = $quantity;
        $receivedBy = $requesterName; // Logged-in user receives the stock

    } elseif ($transactionType === 'issue') {
        // Retrieve the selected full names from the dropdowns
        $issuerName = $_POST['issued_by_name'] ?? null;
        $receiverName = $_POST['receiver_full_name'] ?? null; // NEW FIELD

        $quantity = intval($_POST['to_dispensing'] ?? 0);
        $toDispensing = $quantity;

        // FIX: The supplier name is now 'From Stores' for issues
        $supplierName = 'From Stores';

        // Map the user roles to the database columns
        $receivedBy = $receiverName; // The person RECEIVING the stock
        $issuedTo = $issuerName;     // The person ISSUING the stock (The 'Issuer' in the old logic)

        // Ensure required fields are set for issue
        if (!$issuerName || !$receiverName) {
             $message = "<div class='error-message'>Please select both the Issuer and the Receiver for this stock-out transaction.</div>";
             goto end_of_post;
        }

    } else { $message = "<div class='error-message'>Invalid transaction type selected.</div>"; goto end_of_post; }

    if ($quantity <= 0) { $message = "<div class='error-message'>Quantity must be greater than zero.</div>"; goto end_of_post; }

    // 3. Get current inventory balance (for the specific drugID)
    $sql_check = "SELECT stores_balance FROM stores_inventory WHERE drugID = ? ORDER BY inventory_id DESC LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);

    if (!$stmt_check) { $message = "<div class='error-message'>DB Error preparing balance check: " . $conn->error . "</div>"; goto end_of_post; }

    $stmt_check->bind_param("i", $drugId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check && $result_check->num_rows > 0) $currentBalance = $result_check->fetch_assoc()['stores_balance'];
    $stmt_check->close();

    // 4. Calculate new balance and perform validation
    $newBalance = 0;
    if ($transactionType === 'receipt') {
        $newBalance = $currentBalance + $quantity;
    } elseif ($transactionType === 'issue') {
        if ($quantity > $currentBalance) {
            $message = "<div class='error-message'>Issue quantity ($quantity mL) exceeds current stock balance ($currentBalance mL) for $drugName.</div>";
            goto end_of_post;
        }
        $newBalance = $currentBalance - $quantity;
    }

    // 5. Prepare and execute the INSERT query (9 parameters)
    // NOTE: The `received_by_full_name` column now holds the Receiver's name (for issue) OR the Logged-in User (for receipt).
    // The `issued_to_full_name` column now holds the Issuer's name (for issue) OR NULL/Empty (for receipt).
    $sql_insert = "INSERT INTO stores_inventory (
        drugID, drugname, from_supplier, supplier_name, to_dispensing, stores_balance,
        transaction_date, received_by_full_name, issued_to_full_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) { $message = "<div class='error-message'>Error preparing statement: " . $conn->error . "</div>"; goto end_of_post; }

    // BIND ORDER: i(drugID), s(drugname), i(from_supplier), s(supplier_name), i(to_dispensing), i(stores_balance), s(transaction_date), s(received_by_full_name), s(issued_to_full_name)
    $stmt->bind_param("isisissss",
        $drugId, $drugName, $fromSupplier, $supplierName, $toDispensing, $newBalance, $transactionDate,
        $receiverName, $issuerName
    );

    if ($stmt->execute()) {
        $message = "<div class='success-message'>$transactionType recorded successfully. New $drugName balance: $newBalance mL.</div>";

        if ($transactionType === 'issue') {
            // Placeholder for the Dispensing Log insertion (Next Step)
        }
    } else {
        $message = "<div class='error-message'>Database Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

end_of_post:

if (isset($conn) && !$conn->connect_error) $conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Drug Inventory Transaction</title>
    <style>
        body{font-family:Arial,sans-serif;background-color:#f4f4f4;padding:20px}
        .container{max-width:600px;margin:0 auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,.1)}h2{text-align:center;color:#000099;margin-bottom:20px}
        .form-group{margin-bottom:15px}
        label{display:block;margin-bottom:5px;font-weight:700}
        input[type=text],input[type=number],input[type=datetime-local],select{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box}
        .transaction-fields{padding:15px;border:1px dashed #ccc;margin-top:10px;border-radius:5px;background-color:#fafafa}
        .hidden{display:none}.submit-btn{background-color:#b1f0c2;color:#000000;padding:10px 15px;border:none;border-radius:4px;cursor:pointer;width:100%;font-size:16px}.submit-btn:hover{background-color:#45a049}.success-message{background-color:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:10px;margin-bottom:15px;border-radius:4px}.error-message{background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:10px;margin-bottom:15px;border-radius:4px}
    </style>
</head>
<body>

<div class="container">
    <h2>Daily Drug Inventory Transaction Log</h2>
    <?php echo $message; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

        <div class="form-group">
            <label for="transaction_type">Transaction Type:</label>
            <select id="transaction_type" name="transaction_type" required onchange="toggleFields()">
                <option value="">Select Type</option>
                <option value="issue" style='color: red;'>Issue to Dispensing Area - &#10003</option>
                <option value="receipt">Receipt from Supplier (Stock IN)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="drug_select">Drug Name:</label>
            <select id="drug_select" name="drug_id" required onchange="updateDrugNameText()">
                <option value="">Select Drug</option>
                <?php
                foreach ($drugs as $drug) {
                    echo "<option value='{$drug['drugID']}' data-name='{$drug['drugname']}'>" . htmlspecialchars($drug['drugname']) . "</option>";
                }
                ?>
            </select>
            <input type="hidden" id="drugname_text" name="drugname_text" value="">
        </div>

        <div class="form-group">
            <label for="transaction_date">Transaction Date/Time:</label>
            <input type="datetime-local" id="transaction_date" name="transaction_date"
                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
        </div>

        <div id="receipt_fields" class="transaction-fields hidden">
            <h4 style="color: #4CAF50;">Receipt Details (IN)</h4>
            <div class="form-group">
                <label for="from_supplier">Quantity Received (mL or Units):</label>
                <input type="number" id="from_supplier" name="from_supplier" step="1" min="1" placeholder="Enter quantity in mL or Units">
            </div>
            <div class="form-group">
                <label for="supplier_name">Supplier Name:</label>
                <select id="supplier_name" name="supplier_name">
                    <option value="">Select Supplier</option>
                    <?php
                    foreach ($suppliers as $supplier) {
                        echo "<option value='" . htmlspecialchars($supplier) . "'>" . htmlspecialchars($supplier) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Receiver (Logged-in User):</label>
                <input type="text" value="<?php echo $loggedInUserName; ?>" readonly class="readonly-input">
            </div>
        </div>

        <div id="issue_fields" class="transaction-fields hidden">
            <h4 style="color: #000099;">Issue Details (OUT)</h4>
            <div class="form-group">
                <label for="to_dispensing">Quantity Issued to Dispensing (mL or Units):</label>
                <input type="number" id="to_dispensing" name="to_dispensing" step="1" min="1" placeholder="Enter quantity in mL or Units">
            </div>
            <div class="form-group">
                <label>Requester (Logged-in User):</label>
                <input type="text" value="<?php echo $loggedInUserName; ?>" readonly class="readonly-input">
            </div>

            <div class="form-group">
                <label for="issued_by_name_select">Issuer (Staff who released the stock):</label>
                <select id="issued_by_name_select" name="issued_by_name">
                    <option value="">Select Issuer</option>
                    <?php
                    foreach ($allUsers as $user_name) {
                        echo "<option value='" . htmlspecialchars($user_name) . "'>" . htmlspecialchars($user_name) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="receiver_full_name_select">Receiver (Staff who receives stock at dispensing):</label>
                <select id="receiver_full_name_select" name="receiver_full_name">
                    <option value="">Select Receiver</option>
                    <?php
                    foreach ($allUsers as $user_name) {
                        echo "<option value='" . htmlspecialchars($user_name) . "'>" . htmlspecialchars($user_name) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <button type="submit" class="submit-btn">Submit Transaction</button>
    </form>
</div>

<script>
    function updateDrugNameText() {
        const drugSelect = document.getElementById('drug_select');
        const drugNameText = document.getElementById('drugname_text');
        const selectedOption = drugSelect.options[drugSelect.selectedIndex];
        if (selectedOption.value) {
            drugNameText.value = selectedOption.getAttribute('data-name');
        } else {
            drugNameText.value = '';
        }
    }

    function toggleFields() {
        const type = document.getElementById('transaction_type').value;
        const receiptFields = document.getElementById('receipt_fields');
        const issueFields = document.getElementById('issue_fields');
        const fromSupplierInput = document.getElementById('from_supplier');
        const supplierSelect = document.getElementById('supplier_name');
        const toDispensingInput = document.getElementById('to_dispensing');
        const issuedByNameSelect = document.getElementById('issued_by_name_select');
        const receiverFullNameSelect = document.getElementById('receiver_full_name_select'); // NEW ID

        receiptFields.classList.add('hidden');
        issueFields.classList.add('hidden');

        fromSupplierInput.removeAttribute('required');
        supplierSelect.removeAttribute('required');
        toDispensingInput.removeAttribute('required');
        issuedByNameSelect.removeAttribute('required');
        receiverFullNameSelect.removeAttribute('required'); // NEW

        if (type === 'receipt') {
            receiptFields.classList.remove('hidden');
            fromSupplierInput.setAttribute('required', 'required');
            supplierSelect.setAttribute('required', 'required');
        } else if (type === 'issue') {
            issueFields.classList.remove('hidden');
            toDispensingInput.setAttribute('required', 'required');
            issuedByNameSelect.setAttribute('required', 'required');
            receiverFullNameSelect.setAttribute('required', 'required'); // NEW
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();
        updateDrugNameText();
    });
</script>

</body>
</html>