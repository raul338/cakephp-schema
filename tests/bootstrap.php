<?php
declare(strict_types=1);

require 'config/bootstrap.php';

$directories = [
    TMP . 'cache/models',
    TMP . 'cache/persistent',
    TMP . 'cache/views',
];
foreach ($directories as $dir) {
    if (!file_exists($dir) || !is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    chmod($dir, 0777);
}
unset($dir);
unset($directories);
