<?php
echo "=== Quick COM Port Test ===\n\n";

set_time_limit(10); // 10 second timeout

$port = "COM3";
echo "Testing {$port}...\n";

$handle = @fopen("\\\\.\\{$port}", "r+b");

if ($handle === false) {
    echo "? Cannot open {$port}\n";
    echo "Error: " . error_get_last()['message'] . "\n";
} else {
    echo "? Successfully opened {$port}!\n";

    stream_set_blocking($handle, 0); // Non-blocking mode
    stream_set_timeout($handle, 2);  // 2 second timeout

    // Quick write test
    $written = @fwrite($handle, "\r\n");
    echo "Wrote: " . ($written ?: "0") . " bytes\n";

    // Quick read test (non-blocking)
    usleep(500000); // Wait 0.5 seconds
    $data = @fread($handle, 128);
    echo "Read: " . (strlen($data)) . " bytes\n";

    if ($data) {
        echo "Data: " . bin2hex($data) . "\n";
    }

    fclose($handle);
    echo "? Test complete\n";
}
?>