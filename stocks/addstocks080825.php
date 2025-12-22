<?php
// Include the database connection file
require_once '../includes/config.php';
include '../includes/header.php';
include '../includes/footer.php';

// Initialize database connection
$host = 'localhost';
$db = 'bonsantepharma';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Initialize variables
$success_message = "";
$receivedBy = "";

// Fetch logged-in user's name
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt = $mysqli->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $receivedBy = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// Fetch products
$sql_products = "SELECT id, productname FROM products";
$result_products = $mysqli->query($sql_products);
if (!$result_products) {
    die("Error fetching product data: " . $mysqli->error);
}

// Fetch suppliers
$sql_suppliers = "SELECT supplier_id, name FROM suppliers";
$result_suppliers = $mysqli->query($sql_suppliers);
if (!$result_suppliers) {
    die("Error fetching supplier data: " . $mysqli->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = json_decode($_POST['items'], true); // Expecting JSON array of items
    $success = true;
    $mysqli->begin_transaction();

    foreach ($items as $item) {
        $productname = $item['productname'];
        $quantityIn = (int)$item['quantityIn'];
        $receivedFrom = $item['receivedFrom'];
        $batch = $item['batch'];
        $expiryDate = $item['expiryDate'];

        // Get opening balance
        $sql_latest_total_qty = "SELECT stockBalance FROM stock_movements WHERE productname = ? ORDER BY transDate DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql_latest_total_qty);
        $stmt->bind_param('s', $productname);
        $stmt->execute();
        $result_latest_total_qty = $stmt->get_result();
        $openingBalance = $result_latest_total_qty->num_rows > 0 ? $result_latest_total_qty->fetch_assoc()['stockBalance'] : 0;
        $stmt->close();

        // Calculate stock balance
        $stockBalance = $openingBalance + $quantityIn;

        // Get product ID
        $sql_get_id = "SELECT id FROM products WHERE productname = ?";
        $stmt = $mysqli->prepare($sql_get_id);
        $stmt->bind_param('s', $productname);
        $stmt->execute();
        $result_id = $stmt->get_result();
        $stockID = $result_id->num_rows > 0 ? $result_id->fetch_assoc()['id'] : null;
        $stmt->close();

        // Insert stock movement
        $sql = "INSERT INTO stock_movements (stockID, productname, openingBalance, quantityIn, receivedFrom, batch, expiryDate, receivedBy, stockBalance, transDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ississssi', $stockID, $productname, $openingBalance, $quantityIn, $receivedFrom, $batch, $expiryDate, $receivedBy, $stockBalance);
        if (!$stmt->execute()) {
            $success = false;
            echo "Error: " . $stmt->error;
            break;
        }
        $stmt->close();
    }

    if ($success) {
        $mysqli->commit();
        $success_message = "All stock data inserted successfully.";
    } else {
        $mysqli->rollback();
        $success_message = "Error inserting stock data.";
    }
}

// Close database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Stock Movement</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        .stock-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .stock-row select, .stock-row input {
            flex: 1;
            min-width: 100px;
        }
        .stock-row .select2-container {
            flex: 1;
            min-width: 150px;
        }
        .stock-row input[type="number"], .stock-row input[type="date"] {
            width: 100px;
        }
        .stock-row input[type="text"] {
            width: 120px;
        }
        .stock-row .remove-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }
        #success-message {
            background-color: #BEFF9E;
            height: 40px;
            display: flex;
            align-items: center;
            font-style: italic;
            padding: 0 10px;
            margin-bottom: 10px;
        }
        .custom-submit-btn {
            background-color: #00246B;
            color: white;
            cursor: pointer;
            padding: 10px;
            width: 200px;
            border: none;
            border-radius: 5px;
        }
        .add-item-btn {
            background-color: #28a745;
            color: white;
            cursor: pointer;
            padding: 10px;
            margin-bottom: 10px;
            width: 200px;
            border: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Success message -->
        <div id="success-message" style="display: <?php echo $success_message ? 'block' : 'none'; ?>;">
            <?php echo $success_message; ?>
        </div>
        <h3>Add Product Stocks</h3>
        <button type="button" class="add-item-btn" onclick="addStockRow()">Add Another Item</button>
        <form id="stock-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="items" id="items">
            <input type="hidden" name="receivedBy" value="<?php echo htmlspecialchars($receivedBy); ?>">
            <div id="stock-rows">
                <!-- Initial row will be added by JavaScript -->
            </div>
            <input type="submit" class="custom-submit-btn" value="Submit All Stocks">
        </form>
    </div>

    <!-- Include jQuery and Select2 JS -->
    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Product and supplier options
        const products = <?php
            $products_array = [];
            $result_products->data_seek(0);
            while ($row = $result_products->fetch_assoc()) {
                $products_array[] = ['id' => $row['id'], 'productname' => $row['productname']];
            }
            echo json_encode($products_array);
        ?>;
        const suppliers = <?php
            $suppliers_array = [];
            $result_suppliers->data_seek(0);
            while ($row = $result_suppliers->fetch_assoc()) {
                $suppliers_array[] = ['supplier_id' => $row['supplier_id'], 'name' => $row['name']];
            }
            echo json_encode($suppliers_array);
        ?>;

        // Counter for unique row IDs
        let rowCount = 0;

        // Add a new stock row
        function addStockRow() {
            rowCount++;
            const rowId = `row-${rowCount}`;
            const rowHtml = `
                <div class="stock-row" id="${rowId}">
                    <select name="productname-${rowId}" class="productname" required>
                        <option value="">Select product</option>
                        ${products.map(p => `<option value="${p.productname}">${p.productname}</option>`).join('')}
                    </select>
                    <input type="number" name="openingBalance-${rowId}" class="openingBalance" value="0" readonly>
                    <label>Quantity Revceived</label>   
                    <input type="number" name="quantityIn-${rowId}" class="quantityIn" required min="1">
                    <select name="receivedFrom-${rowId}" class="receivedFrom" required>
                        <option value="">Select supplier</option>
                        ${suppliers.map(s => `<option value="${s.name}">${s.name}</option>`).join('')}
                    </select>
                    <input type="text" name="batch-${rowId}" class="batch" required>
                    <input type="date" name="expiryDate-${rowId}" class="expiryDate" required>
                    <button type="button" class="remove-btn" onclick="removeRow('${rowId}')">Remove</button>
                </div>
            `;
            $('#stock-rows').append(rowHtml);

            // Initialize Select2 for new row
            $(`#${rowId} .productname`).select2({
                placeholder: "Select or type product",
                allowClear: true,
                width: '100%'
            });
            $(`#${rowId} .receivedFrom`).select2({
                placeholder: "Select or type supplier",
                allowClear: true,
                width: '100%'
            });

            // Attach event listener for opening balance
            $(`#${rowId} .productname`).on('change', function() {
                getOpeningBal(rowId);
            });
        }

        // Remove a row
        function removeRow(rowId) {
            $(`#${rowId}`).remove();
        }

        // Fetch opening balance
        function getOpeningBal(rowId) {
            const productname = $(`#${rowId} .productname`).val();
            const openingBalanceInput = $(`#${rowId} .openingBalance`);
            if (!productname) {
                openingBalanceInput.val(0);
                return;
            }

            $.ajax({
                url: 'get_opening_bal.php',
                method: 'GET',
                data: { productname: productname },
                success: function(response) {
                    const data = JSON.parse(response);
                    openingBalanceInput.val(data.latest_total_qty || 0);
                },
                error: function() {
                    openingBalanceInput.val(0);
                }
            });
        }

        // Handle form submission
        $('#stock-form').on('submit', function(e) {
            e.preventDefault();
            const items = [];
            $('.stock-row').each(function() {
                const rowId = $(this).attr('id');
                items.push({
                    productname: $(`#${rowId} .productname`).val(),
                    quantityIn: $(`#${rowId} .quantityIn`).val(),
                    receivedFrom: $(`#${rowId} .receivedFrom`).val(),
                    batch: $(`#${rowId} .batch`).val(),
                    expiryDate: $(`#${rowId} .expiryDate`).val()
                });
            });

            if (items.length === 0) {
                alert('Please add at least one item.');
                return;
            }

            // Validate all required fields
            let valid = true;
            items.forEach((item, index) => {
                if (!item.productname || !item.quantityIn || !item.receivedFrom || !item.batch || !item.expiryDate) {
                    alert(`Please fill all fields in row ${index + 1}.`);
                    valid = false;
                }
            });

            if (!valid) return;

            // Set items as JSON in hidden input
            $('#items').val(JSON.stringify(items));

            // Submit form
            this.submit();
        });

        // Initialize with one row
        $(document).ready(function() {
            addStockRow();
        });
    </script>
</body>
</html>