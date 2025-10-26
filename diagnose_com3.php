<?php
// diagnose_com3.php - Run via batch file as Administrator
error_reporting(E_ALL);

echo "=== COM3 Diagnostic ===\n\n";

// Method 1: Direct file access
echo "1. Testing direct COM3 access...\n";
$handle = @fopen("\\\\.\\COM3", "r+b");
if ($handle) {
    echo "   ? COM3 opened successfully!\n";
    fclose($handle);
} else {
    $error = error_get_last();
    echo "   ? Cannot open COM3: " . $error['message'] . "\n";
}

// Method 2: Check if port exists via PowerShell
echo "\n2. Checking COM port via PowerShell...\n";
exec('powershell "[System.IO.Ports.SerialPort]::getportnames()"', $ports);
echo "   Available ports: ";
if (empty($ports)) {
    echo "None found\n";
} else {
    echo implode(', ', $ports) . "\n";
}

// Method 3: Check device manager
echo "\n3. Checking device manager...\n";
exec('wmic path Win32_PnPEntity get Name /format:list | findstr "COM"', $comDevices);
if (empty($comDevices)) {
    echo "   No COM devices found\n";
} else {
    echo "   COM devices:\n";
    foreach ($comDevices as $device) {
        echo "   - " . $device . "\n";
    }
}

// Method 4: Check if port is in use
echo "\n4. Checking if COM3 is in use...\n";
exec('netstat -an | findstr ":3"', $netstat);
if (empty($netstat)) {
    echo "   ? COM3 not in use by network\n";
} else {
    echo "   COM3 network usage:\n";
    foreach ($netstat as $line) {
        echo "   - " . $line . "\n";
    }
}

echo "\n=== Diagnostic Complete ===\n";
?>