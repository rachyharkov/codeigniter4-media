<?php

echo 'Creating migration file...';
$migrationFile = 'app/Database/Migrations/' . date('Y-m-d-His') . '_CreateMedia.php';

echo 'Migration file: ' . $migrationFile;
copy('table/CreateMedia.php', $migrationFile);

echo 'Creating uploads directory...';
mkdir('public/uploads');

echo 'Creating no-image.png...';
copy('assets/no-image.png', 'public/media/no-image.png');
