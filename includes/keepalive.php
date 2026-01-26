<?php
session_start();
include 'config.php';

// Update session timeout
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();

    // Also update database last activity if needed
    $userId = $_SESSION['user_id'];
    $updateQuery = "UPDATE tblusers SET last_activity = NOW() WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    // Return success
    echo json_encode(['status' => 'success', 'timestamp' => date('Y-m-d H:i:s')]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
}
?>