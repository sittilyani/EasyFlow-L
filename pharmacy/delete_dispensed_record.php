<?php
session_start();
include '../includes/config.php'; // Ensure this path is correct

// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("location: ../index.php");
    exit;
}

// ----------------------------------------------------------------------
// --- 1. Get Logged-in User and Required URL Parameters ---
// ----------------------------------------------------------------------

// Ensure session variables are set and fallback to a default
$deleted_by_name = ($_SESSION['first_name'] ?? 'Unknown') . " " . ($_SESSION['last_name'] ?? 'User');

// Validate input from the URL for the initial request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request: Missing or invalid prescription ID.");
}

$disp_id = intval($_GET['id']);
$deletion_reason = ''; // Initialize the reason

// ----------------------------------------------------------------------
// --- 2. Retrieve All Data from 'pharmacy' Before Deletion (Important!) ---
// ----------------------------------------------------------------------

// Get the existing prescription data needed for stock update and logging
$pharmacy_data_query = "SELECT * FROM pharmacy WHERE disp_id = $disp_id";
$pharmacy_result = $conn->query($pharmacy_data_query);

if (!$pharmacy_result || $pharmacy_result->num_rows === 0) {
    die("Error: Prescription record not found.");
}
$pharmacy_data = $pharmacy_result->fetch_assoc();

// Extract the key values needed for logging and stock reversal
$clientName = $conn->real_escape_string($pharmacy_data['clientName']);
$mat_id = $conn->real_escape_string($pharmacy_data['mat_id']);
$sex = $conn->real_escape_string($pharmacy_data['sex']);
$drugname = $conn->real_escape_string($pharmacy_data['drugname']);
// Use the original dosage for stock reversal
$dosage = floatval($pharmacy_data['dosage']);
$pharm_officer_name = $conn->real_escape_string($pharmacy_data['pharm_officer_name']);
$dispDate = $conn->real_escape_string($pharmacy_data['dispDate']);


// ----------------------------------------------------------------------
// --- 3. Handle Form Submission (The actual deletion and logging logic) ---
// ----------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    // Get the reason from the form
    if (empty($_POST['deletion_reason'])) {
        die("Deletion failed: A reason for deletion is required.");
    }
    $deletion_reason = $conn->real_escape_string($_POST['deletion_reason']);

    // Start Transaction for Atomicity
    $conn->begin_transaction();
    $success = true;
    $message = "Prescription deleted Successfully and stock reversed.";

    try {
        // --- A. Stock Reversal Logic (Re-adding the dose to stock) ---
        $stock_query = "SELECT * FROM stock_movements WHERE drugname = '$drugname' ORDER BY trans_date DESC LIMIT 1";
        $stock_result = $conn->query($stock_query);

        if ($stock_result && $stock_result->num_rows > 0) {
            $stock_data = $stock_result->fetch_assoc();
            $new_total_qty = $stock_data['total_qty'] + $dosage;

            // Insert a new stock movement record to reverse the dispense
            $insert_stock_query = "INSERT INTO stock_movements (
                                    drugID, drugname, opening_bal, qty_in, received_from,
                                    batch_number, expiry_date, received_by, total_qty, trans_date
                                ) VALUES (
                                    '" . $stock_data['drugID'] . "',
                                    '$drugname',
                                    '" . $stock_data['total_qty'] . "',
                                    '$dosage',
                                    'Dose deleted (Reversed)',
                                    '" . $stock_data['batch_number'] . "',
                                    '" . $stock_data['expiry_date'] . "',
                                    '$deleted_by_name',
                                    '$new_total_qty',
                                    NOW()
                                )";

            if (!$conn->query($insert_stock_query)) {
                $success = false;
                $message = "Error updating stock movements: " . $conn->error;
                throw new Exception($message);
            }
        } else {
            // No stock data is a warning, not a blocker for logging/deletion
            $message .= " Warning: No stock data found for $drugname. Stock not reversed.";
        }

        // --- B. Insert into deleted_prescriptions table ---
        $insert_deleted_query = "INSERT INTO deleted_prescriptions (
                                    disp_id, clientName, mat_id, sex, drugname, dosage,
                                    pharm_officer_name, dispDate, deletion_reason, deleted_by
                                ) VALUES (
                                    '$disp_id', '$clientName', '$mat_id', '$sex', '$drugname', '$dosage',
                                    '$pharm_officer_name', '$dispDate', '$deletion_reason', '$deleted_by_name'
                                )";

        if (!$conn->query($insert_deleted_query)) {
            $success = false;
            $message = "Error logging deleted prescription: " . $conn->error;
            throw new Exception($message);
        }

        // --- C. Delete the original record from 'pharmacy' ---
        $delete_query = "DELETE FROM pharmacy WHERE disp_id = $disp_id";
        if (!$conn->query($delete_query)) {
            $success = false;
            $message = "Error deleting prescription record: " . $conn->error;
            throw new Exception($message);
        }

        // Commit transaction if all steps succeeded
        $conn->commit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        die($message); // Die with the error message
    }

    // Redirect after successful operation
    header("location: dispensing.php?message=" . urlencode($message));
    exit;
}

// ----------------------------------------------------------------------
// --- 4. Display Confirmation/Reason Input Form (Modal-like Styling) ---
// ----------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Deletion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 50vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .modal-content h2 {
            color: #d9534f;
            margin-top: 0;
        }
        .modal-content label {
            display: block;
            margin-bottom: 8px;
            text-align: left;
            font-weight: bold;
        }
        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            resize: vertical;
        }
        .modal-actions button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-weight: bold;
        }
        .modal-actions .confirm-btn {
            background-color: #d9534f;
            color: white;
        }
        .modal-actions .cancel-btn {
            background-color: #f0ad4e;
            color: white;
        }
    </style>
</head>
<body>
    <div class="modal-overlay">
        <div class="modal-content">
            <h2>Confirm Deletion</h2>
            <p>You are about to delete the prescription for **<?php echo htmlspecialchars($clientName); ?>** (<?php echo htmlspecialchars($drugname) . " - " . htmlspecialchars($dosage); ?>).</p>
            <form method="POST" action="">
                <input type="hidden" name="confirm_delete" value="1">
                <label for="deletion_reason">Reason for Deletion:</label>
                <textarea id="deletion_reason" name="deletion_reason" rows="4" required></textarea>
                <div class="modal-actions">
                    <button type="submit" class="confirm-btn">Confirm Deletion</button>
                    <button type="button" class="cancel-btn" onclick="window.location.href='dispensing.php';">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>