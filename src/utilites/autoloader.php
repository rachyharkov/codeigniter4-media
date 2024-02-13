<?php

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../assets/';
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
// Define the source and destination directories
$sourceDir = __DIR__ . '/../assets';
$destinationDir = __DIR__ . '/../public/assets';

// Get all files in the source directory
$files = scandir($sourceDir);

// Loop through each file
foreach ($files as $file) {
    // Skip "." and ".." directories
    if ($file === '.' || $file === '..') {
        continue;
    }

    // Get the full path of the source file
    $sourceFile = $sourceDir . '/' . $file;

    // Get the full path of the destination file
    $destinationFile = $destinationDir . '/' . $file;

    echo "Copying file $file..." . PHP_EOL;
    // Copy the file to the destination directory
    if (copy($sourceFile, $destinationFile)) {
        echo "File $file copied successfully." . PHP_EOL;
    } else {
        echo "Failed to copy file $file." . PHP_EOL;
    }
}
