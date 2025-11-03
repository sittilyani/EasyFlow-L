<?php
session_start();
// Include your database configuration
include '../includes/config.php';

// --- Placeholder for Logged-in User Data ---
// You MUST replace this with your actual logic to fetch the logged-in user's name
$loggedInUserId = $_SESSION['user_id'] ?? 1; // Default to user ID 1 if not set
$loggedInUserName = 'Store Keeper (Demo)'; // Placeholder name

// Function to fetch a user's full name from the database
function getUserFullName($conn, $userId) {
    if (!$userId) return "N/A";
    $sql = "SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return htmlspecialchars($row['full_name']);
    }
    return "Unknown User";
}

// Fetch the current user's name
if (isset($_SESSION['user_id'])) {
    $loggedInUserName = getUserFullName($conn, $loggedInUserId);
}

// --- PHP FORM SUBMISSION LOGIC ---

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize common fields
    $drugName = trim($_POST['drugname'] ?? 'Methadone'); // Default is Methadone
    $transactionType = $_POST['transaction_type'] ?? '';
    $transactionDate = trim($_POST['transaction_date'] ?? date('Y-m-d H:i:s'));
    $issuerId = $_POST['issued_by'] ?? null; // For issues to dispensing

    // 2. Determine quantity and related fields based on type
    if ($transactionType === 'receipt') {
        $quantity = intval($_POST['from_supplier'] ?? 0);
        $supplierName = trim($_POST['supplier_name'] ?? '');
        $fromSupplier = $quantity;
        $toDispensing = 0;
    } elseif ($transactionType === 'issue') {
        $quantity = intval($_POST['to_dispensing'] ?? 0);
        $supplierName = null;
        $fromSupplier = 0;
        $toDispensing = $quantity;
    } else {
        $message = "<div class='error-message'>Invalid transaction type selected.</div>";
        goto end_of_post; // Skip to the end if invalid
    }

    if ($quantity <= 0) {
        $message = "<div class='error-message'>Quantity must be greater than zero.</div>";
        goto end_of_post;
    }

    // 3. Get current inventory balance
    $currentBalance = 0;
    $sql_check = "SELECT stores_balance FROM stores_inventory ORDER BY inventory_id DESC LIMIT 1";
    $result_check = $conn->query($sql_check);
    if ($result_check && $result_check->num_rows > 0) {
        $currentBalance = $result_check->fetch_assoc()['stores_balance'];
    }

    // 4. Calculate new balance and perform validation
    $newBalance = 0;
    if ($transactionType === 'receipt') {
        $newBalance = $currentBalance + $quantity;
    } elseif ($transactionType === 'issue') {
        if ($quantity > $currentBalance) {
            $message = "<div class='error-message'>Issue quantity ($quantity mL) exceeds current stock balance ($currentBalance mL).</div>";
            goto end_of_post;
        }
        $newBalance = $currentBalance - $quantity;
    }

    // 5. Prepare and execute the INSERT query
    $sql_insert = "INSERT INTO stores_inventory (
        drugname, from_supplier, supplier_name, to_dispensing, stores_balance, transaction_date, requested_by_user_id, issued_by_user_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // NOTE: I'm adding requested_by_user_id and issued_by_user_id here.
    // You might need to add these columns to your stores_inventory table.

    $stmt = $conn->prepare($sql_insert);

    // Assuming drugID is handled via a lookup or is fixed (Methadone, drugID 1)
    // We are inserting the transaction information and the calculated new balance

    // Bind parameters: s, i, s, i, i, s, i, i (String, Int, String, Int, Int, String, Int, Int)
    $requestedBy = ($transactionType === 'issue') ? $loggedInUserId : null;
    $issuedBy = ($transactionType === 'issue') ? $issuerId : null;

    // Adjusting for the given table structure which is missing user IDs, we'll try to fit.
    // Since your table only has 7 columns, and `drugID` is not in the POST,
    // we'll assume `drugID` is 1 for Methadone, and we MUST omit `requested_by_user_id` and `issued_by_user_id`
    // If you add the user ID columns to your table, use the full bind list below.

    /*
    $stmt->bind_param("sisiiisi", // Full bind with user IDs
        $drugName, 1, $fromSupplier, $supplierName, $toDispensing, $newBalance, $transactionDate,
        $requestedBy, $issuedBy
    );
    */

    // Simplified bind to match the 7 columns provided in the prompt (assuming drugID=1 is not posted)
    $sql_simplified = "INSERT INTO stores_inventory (drugID, drugname, from_supplier, supplier_name, to_dispensing, stores_balance, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_simplified);
    $drugIdFixed = 1; // Assuming 1 for Methadone

    $stmt->bind_param("isisiss",
        $drugIdFixed, $drugName, $fromSupplier, $supplierName, $toDispensing, $newBalance, $transactionDate
    );


    if ($stmt->execute()) {
        $message = "<div class='success-message'>$transactionType recorded successfully. New balance: $newBalance mL.</div>";
    } else {
        $message = "<div class='error-message'>Database Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

end_of_post:

// Fetch list of potential Issuers (HCW/Pharmacists) for the 'Issue' dropdown
$issuers = [];
$sql_issuers = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name FROM tblusers WHERE userrole IN ('Pharmacist', 'HCW', 'Store Keeper')";
$result_issuers = $conn->query($sql_issuers);
if ($result_issuers && $result_issuers->num_rows > 0) {
    while ($row = $result_issuers->fetch_assoc()) {
        $issuers[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Methadone Inventory Transaction</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #000099;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], input[type="datetime-local"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .transaction-fields {
            padding: 15px;
            border: 1px dashed #ccc;
            margin-top: 10px;
            border-radius: 5px;
            background-color: #fafafa;
        }
        .hidden {
            display: none;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>?? Methadone Inventory Log</h2>
    <?php echo $message; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

        <div class="form-group">
            <label for="transaction_type">Transaction Type:</label>
            <select id="transaction_type" name="transaction_type" required onchange="toggleFields()">
                <option value="">Select Type</option>
                <option value="receipt">Receipt from Supplier</option>
                <option value="issue">Issue to Dispensing Area</option>
            </select>
        </div>

        <div class="form-group">
            <label for="drugname">Drug Name:</label>
            <input type="text" id="drugname" name="drugname" value="Methadone" required readonly class="readonly-input">
        </div>

        <div class="form-group">
            <label for="transaction_date">Transaction Date/Time:</label>
            <input type="datetime-local" id="transaction_date" name="transaction_date"
                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
        </div>

        <div id="receipt_fields" class="transaction-fields hidden">
            <h4 style="color: #4CAF50;">Receipt Details (IN)</h4>
            <div class="form-group">
                <label for="from_supplier">Quantity Received (mL):</label>
                <input type="number" id="from_supplier" name="from_supplier" step="1" min="1" placeholder="Enter quantity in mL">
            </div>
            <div class="form-group">
                <label for="supplier_name">Supplier Name:</label>
                <input type="text" id="supplier_name" name="supplier_name" placeholder="E.g., KEMSA / Wholesaler Name">
            </div>
        </div>

        <div id="issue_fields" class="transaction-fields hidden">
            <h4 style="color: #000099;">Issue Details (OUT)</h4>
            <div class="form-group">
                <label for="to_dispensing">Quantity Issued to Dispensing (mL):</label>
                <input type="number" id="to_dispensing" name="to_dispensing" step="1" min="1" placeholder="Enter quantity in mL">
            </div>
            <div class="form-group">
                <label>Requested By (Logged-in User):</label>
                <input type="text" value="<?php echo $loggedInUserName; ?>" readonly class="readonly-input">
                <input type="hidden" name="requested_by_user_id" value="<?php echo $loggedInUserId; ?>">
            </div>
            <div class="form-group">
                <label for="issued_by">Issued By (Staff who released the stock):</label>
                <select id="issued_by" name="issued_by">
                    <option value="">Select Issuer</option>
                    <?php
                    foreach ($issuers as $user) {
                        echo "<option value='{$user['user_id']}'>" . htmlspecialchars($user['full_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <button type="submit" class="submit-btn">Submit Transaction</button>
    </form>
</div>

<script>
    function toggleFields() {
        const type = document.getElementById('transaction_type').value;
        const receiptFields = document.getElementById('receipt_fields');
        const issueFields = document.getElementById('issue_fields');
        const fromSupplierInput = document.getElementById('from_supplier');
        const supplierNameInput = document.getElementById('supplier_name');
        const toDispensingInput = document.getElementById('to_dispensing');
        const issuedBySelect = document.getElementById('issued_by');

        // Hide all transaction-specific fields initially
        receiptFields.classList.add('hidden');
        issueFields.classList.add('hidden');

        // Remove 'required' from all conditional fields
        fromSupplierInput.removeAttribute('required');
        supplierNameInput.removeAttribute('required');
        toDispensingInput.removeAttribute('required');
        issuedBySelect.removeAttribute('required');


        if (type === 'receipt') {
            receiptFields.classList.remove('hidden');
            fromSupplierInput.setAttribute('required', 'required');
            supplierNameInput.setAttribute('required', 'required');

        } else if (type === 'issue') {
            issueFields.classList.remove('hidden');
            toDispensingInput.setAttribute('required', 'required');
            issuedBySelect.setAttribute('required', 'required');

        }
    }

    // Call on load to ensure initial state is correct (if form retained state)
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>

</body>
</html>