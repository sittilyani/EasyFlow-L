<?php
echo "=== Simple Pump Test ===\n\n";

// Configure COM3 directly with Windows mode command
echo "Configuring COM3...\n";
exec('mode COM3 BAUD=96 PARITY=e DATA=7 STOP=1 2>&1', $output, $return);
foreach ($output as $line) {
    echo $line . "\n";
}

// Now open it directly
$port = "\\\\.\\COM3";
$handle = @fopen($port, "r+b");

if ($handle === false) {
    die("? Cannot open COM3\n");
}

echo "\n? Port opened successfully!\n\n";
stream_set_blocking($handle, 0);

// Send commands to pump
echo "Sending wake-up commands...\n";
for ($i = 0; $i < 20; $i++) {
    echo "Attempt " . ($i + 1) . ": ";

    fwrite($handle, "\r\n");
    fflush($handle);
    usleep(500000); // 0.5 seconds

    $data = fread($handle, 256);
    if ($data && strlen($data) > 0) {
        echo "? RECEIVED DATA!\n";
        echo "  Raw: '" . $data . "'\n";
        echo "  Hex: " . bin2hex($data) . "\n";
        echo "  Length: " . strlen($data) . " bytes\n";
        break;
    } else {
        echo "No response\n";
    }
}

fclose($handle);
echo "\n? Test complete\n";
?>