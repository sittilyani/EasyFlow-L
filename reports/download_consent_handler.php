<?php
// download_consent_handler.php

if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filepath = '../consentsforms/' . $filename;

    // Security check: Ensure the file exists and is in the correct directory
    if (file_exists($filepath) && pathinfo($filename, PATHINFO_EXTENSION) === 'pdf') {

        // Clear output buffer
        if (ob_get_level()) {
            ob_clean();
        }

        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));

        // Read file and output to browser
        readfile($filepath);
        exit;
    } else {
        // File not found or invalid
        http_response_code(404);
        die("Error: File not found or invalid format.");
    }
} else {
    // No file specified
    header("Location: view_consents.php");
    exit;
}
?>