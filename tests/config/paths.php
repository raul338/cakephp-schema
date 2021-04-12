<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
/**
 * Test suite bootstrap for Schema.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 *
 * @param string $root path find root
 * @return string
 */
$findRoot = function (string $root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new \RuntimeException('Cannot find the root of the application, unable to run tests');
};

define('SCHEMA_ROOT', $findRoot(__FILE__));
unset($findRoot);
define('ROOT', SCHEMA_ROOT . DS . 'tests');
define('APP_DIR', 'App');
define('APP', ROOT . DS . APP_DIR);
define('CONFIG', ROOT . DS . 'config' . DS);
define('WEBROOT_DIR', 'webroot');
define('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
define('TESTS', SCHEMA_ROOT . DS . 'tests' . DS);
define('TMP', SCHEMA_ROOT . DS . 'tmp' . DS);
define('LOGS', SCHEMA_ROOT . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('RESOURCES', ROOT . DS . 'resources' . DS);
define('CAKE_CORE_INCLUDE_PATH', SCHEMA_ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
