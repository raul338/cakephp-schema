<?php

use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Database\Type;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ConsoleErrorHandler;
use Cake\Error\ErrorHandler;
use Cake\Log\Log;

require __DIR__ . '/paths.php';
require CORE_PATH . 'config' . DS . 'bootstrap.php';

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);
ConnectionManager::setConfig((array)Configure::consume('Datasources'));
Log::setConfig((array)Configure::consume('Log'));

$isCli = PHP_SAPI === 'cli';
if ($isCli) {
    (new ConsoleErrorHandler(Configure::read('Error')))->register();
} else {
    (new ErrorHandler(Configure::read('Error')))->register();
}
