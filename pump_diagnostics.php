<?php
// First, check if the class exists and can be loaded
$classExists = class_exists('PhpSerial\PhpSerial');
echo "PhpSerial class exists: " . ($classExists ? 'YES' : 'NO') . "\n";

if (!$classExists) {
    // Try to manually include the file
    $filePath = __DIR__ . '/vendor/gregwar/php-serial/PhpSerial/PhpSerial.php';
    if (file_exists($filePath)) {
        require_once $filePath;
        echo "Manually included PhpSerial.php\n";
    } else {
        echo "PhpSerial.php not found at: $filePath\n";
        // Try to find it
        $files = glob(__DIR__ . '/**/PhpSerial.php', GLOB_BRACE);
        if (!empty($files)) {
            echo "Found PhpSerial.php at:\n";
            foreach ($files as $file) {
                echo " - $file\n";
            }
        }
    }
}

// Now test the mode command alone
echo "\nTesting mode command...\n";
exec('mode COM3', $modeOutput, $modeReturn);
echo "Mode command return code: $modeReturn\n";
echo "Mode output:\n";
print_r($modeOutput);
?>