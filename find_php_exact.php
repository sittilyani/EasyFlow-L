<?php
// find_php_exact.php - Run this in your browser
echo "<pre>";
echo "=== Finding Exact PHP Path ===\n\n";

echo "Current PHP: " . PHP_BINARY . "\n\n";

$laragonPath = 'C:/laragon';
echo "Searching in: $laragonPath/bin/php/\n\n";

// List all PHP directories
$phpDirs = glob($laragonPath . '/bin/php/*', GLOB_ONLYDIR);

if (empty($phpDirs)) {
    echo "No PHP directories found!\n";
    echo "Check if Laragon is installed at: $laragonPath\n";
} else {
    echo "Found PHP directories:\n";
    foreach ($phpDirs as $dir) {
        $dirName = basename($dir);
        $phpExe = $dir . '/php.exe';
        $exists = file_exists($phpExe) ? '? EXISTS' : '? MISSING';
        echo " - $dirName: $exists\n";

        if (file_exists($phpExe)) {
            echo "   Full path: $phpExe\n";
        }
    }
}

echo "\n</pre>";
?>