<?php

use Cake\Core\Configure;
use Cake\Error\ConsoleErrorHandler;
use Cake\Error\ErrorHandler;

$isCli = PHP_SAPI === 'cli';
if ($isCli) {
    (new ConsoleErrorHandler(Configure::read('Error')))->register();
} else {
    (new ErrorHandler(Configure::read('Error')))->register();
}
