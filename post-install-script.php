<?php

echo 'Creating migration file...';
$migrationFile = 'app/Database/Migrations/2023-08-01-092856_CreateMedia.php';

echo 'Migration file: ' . $migrationFile;
copy(__DIR__ . '/table/2023-08-01-092856_CreateMedia.php', $migrationFile);

echo 'Creating uploads directory...';
mkdir(__DIR__ . '/public/uploads');

echo 'Creating no-image.png...';
copy(__DIR__ . '/assets/no-image.png', __DIR__ . '/public/media/no-image.png');
