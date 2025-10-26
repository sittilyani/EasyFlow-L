<?php
$file = 'vendor/gregwar/php-serial/PhpSerial/PhpSerial.php';
$content = file_get_contents($file);

// Replace all curly brace string access with square brackets
$content = preg_replace('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\{(\d+)\}/', '$$$1[$2]', $content);

file_put_contents($file, $content);
echo "Fixed! PhpSerial.php has been updated for PHP 8+ compatibility.\n";
?>