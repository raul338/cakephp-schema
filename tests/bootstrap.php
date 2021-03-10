<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ConsoleErrorHandler;
use Cake\Log\Log;

/**
 * Test suite bootstrap for Schema.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 *
 * @param string $root path find root
 */

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new \RuntimeException('Cannot find the root of the application, unable to run tests');
};

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', $findRoot(__FILE__));
unset($findRoot);
define('APP_DIR', 'App');
define('WEBROOT_DIR', 'webroot');
define('APP', ROOT . '/tests/App/');
define('CONFIG', ROOT . '/tests/config/');
define('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
define('TESTS', ROOT . DS . 'tests' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);

chdir(ROOT);

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

require CORE_PATH . 'config' . DS . 'bootstrap.php';

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);
ConnectionManager::setConfig(Configure::consume('Datasources'));
Log::setConfig(Configure::consume('Log'));
(new ConsoleErrorHandler(Configure::read('Error')))->register();
