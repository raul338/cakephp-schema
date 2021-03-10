<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for Schema.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 *
 * @param string $root path find root
 */


require 'config/bootstrap.php';

$directories = [
    TMP . 'cache/models',
    TMP . 'cache/persistent',
    TMP . 'cache/views',
];
foreach ($directories as $dir) {
    if (!file_exists($dir) || !is_dir($dir)) {
        mkdir($dir, 0777);
    }
    chmod($dir,  0777);
}
unset($dir);
unset($directories);
