<?php
session_start();
// Include the database connection file
include '../includes/config.php';


// Initialize variables at the top of your script
$success_message = "";
$opening_bal = 0;
$received_by = "Unknown"; // Initialize with a default value

// Fetch the logged-in user's name
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $received_by = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}


// Fetch drug names and IDs from the "drug" table
$sql_drugs = "SELECT drugID, drugName FROM drug ORDER BY drugName ASC";
$result_drugs = $conn->query($sql_drugs);
if (!$result_drugs) {
    die("Error fetching drug data: " . $conn->error);
}

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $transactionType = 'Receiving';
    $drugname = $_POST['drugname'] ?? '';
    $qty_in = isset($_POST['qty_in']) ? (int)$_POST['qty_in'] : 0;
    $received_from = isset($_POST['received_from']) ? $_POST['received_from'] : '';
    $batch_number = isset($_POST['batch_number']) ? $_POST['batch_number'] : '';
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $qty_out = 0; // Fixed value for receiving stock

    // Get the latest total quantity for the selected drug to use as opening balance
    $sql_latest_total_qty = "SELECT total_qty FROM stock_movements WHERE drugName = ? ORDER BY trans_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql_latest_total_qty);
    if ($stmt) {
        $stmt->bind_param('s', $drugname);
        $stmt->execute();
        $result_latest_total_qty = $stmt->get_result();
        if ($result_latest_total_qty->num_rows > 0) {
            $opening_bal = $result_latest_total_qty->fetch_assoc()['total_qty'];
        }
        $stmt->close();
    }

    // Calculate the new total quantity
    $total_qty = $opening_bal + $qty_in;

    // Get the drugID for the selected drugname
    $drugID = null;
    $sql_get_drug_id = "SELECT drugID FROM drug WHERE drugName = ?";
    $stmt = $conn->prepare($sql_get_drug_id);
    if ($stmt) {
        $stmt->bind_param('s', $drugname);
        $stmt->execute();
        $result_drug_id = $stmt->get_result();
        $row_drug_id = $result_drug_id->fetch_assoc();
        $drugID = $row_drug_id['drugID'] ?? null;
        $stmt->close();
    } else {
        error_log("Error preparing drug ID query: " . $conn->error);
    }

    // Insert stock movement record if a valid drugID was found
    if ($drugID !== null) {
        $sql = "INSERT INTO stock_movements (transactionType, drugID, drugName, opening_bal, qty_in, received_from, qty_out, batch_number, expiry_date, received_by, total_qty)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Corrected bind_param types
            $stmt->bind_param('sisiisisssi', $transactionType, $drugID, $drugname, $opening_bal, $qty_in, $received_from, $qty_out, $batch_number, $expiry_date, $received_by, $total_qty);

            if ($stmt->execute()) {
                $success_message = "Stock data inserted successfully.";
            } else {
                echo "Error inserting stock data: " . $stmt->error;
            }
            $stmt->close();
        } else {
            error_log("Error preparing insert query: " . $conn->error);
        }
    } else {
        echo "Error: Drug not found for selected name.";
    }
}

// Close the database connection (moved to after all processing)
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Stock Movement</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div id="success-message" style="display: <?php echo $success_message ? 'flex' : 'none'; ?>;">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <h2>Add Drug Stocks</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="drugname">Drug Name</label>
                <select name="drugname" class='select' id="drugname" onchange="getOpeningBal()" required>
                    <option value="">Select Drug</option>
                    <?php
                    if ($result_drugs && $result_drugs->num_rows > 0) {
                        $result_drugs->data_seek(0);
                        while ($row = $result_drugs->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['drugName']) . '">' . htmlspecialchars($row['drugName']) . '</option>';
                        }
                    } else {
                        echo '<option value="">No drugs found</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="opening_bal">Opening Balance</label>
                <input type="number" name="opening_bal" id="opening_bal" value="<?php echo $opening_bal; ?>" readonly class="readonly-input">
            </div>

            <div class="form-group">
                <label for="qty_in">Quantity Received</label>
                <input type="number" name="qty_in" required>
            </div>

            <div class="form-group">
                <label for="received_from">Received From</label>
                <input type="text" name="received_from" required>
            </div>

            <div class="form-group">
                <label for="batch_number">Batch Number</label>
                <input type="text" name="batch_number" required>
            </div>

            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="date" name="expiry_date" id="expiry-date" required>
            </div>

            <div class="form-group">
                <label for="received_by">Received By</label>
                <input type="text" name="received_by" class="readonly-input" readonly value="<?php echo htmlspecialchars($received_by); ?>">
            </div>

            <input type="submit" class='custom-submit-btn' name="submit" value="Add Drug Stocks">
        </form>
    </div>

    <script>
        function getOpeningBal() {
            var drugname = document.getElementById("drugname").value;
            if (drugname === "") {
                document.getElementById("opening_bal").value = 0;
                return;
            }

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState === 4) {
                    if (this.status === 200) {
                        try {
                            var data = JSON.parse(this.responseText);
                            document.getElementById("opening_bal").value = data.latest_total_qty || 0;
                        } catch (e) {
                            console.error("Error parsing JSON response for opening balance:", e);
                            document.getElementById("opening_bal").value = 0;
                        }
                    } else {
                        console.error("Error fetching opening balance: " + this.status);
                        document.getElementById("opening_bal").value = 0;
                    }
                }
            };
            xhttp.open("GET", "get_opening_bal.php?drugname=" + encodeURIComponent(drugname), true);
            xhttp.send();
        }

        window.onload = function() {
            var successMessage = document.getElementById('success-message');
            if (successMessage.style.display === 'flex') {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 5000);
            }
        };
    </script>
</body>
</html>