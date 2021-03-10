<?php

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Error\ExceptionRenderer;
use Cake\Log\Engine\FileLog;

return [
    'debug' => true,

    'App' => [
        'namespace' => 'App',
        'dir' => 'App',
    ],

    'Datasources' => [
        'test' => [
            'className' => Connection::class,
            'driver' => Mysql::class,
            'host' => env('DB_HOST', 'localhost'),
            'username' => env('DB_USER', 'test'),
            'password' => env('DB_PASS', 'test'),
            'database' => env('DB_NAME', 'test'),
            'url' => env('DATABASE_URL'),
        ],
    ],

    'Error' => [
        'errorLevel' => E_ALL,
        'exceptionRenderer' => ExceptionRenderer::class,
        'skipLog' => [],
        'log' => true,
        'trace' => true,
        'ignoredDeprecationPaths' => [],
    ],

    /*
     * Configures logging options
     */
    'Log' => [
        'debug' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'debug',
            'url' => env('LOG_DEBUG_URL', null),
            'scopes' => false,
            'levels' => ['notice', 'info', 'debug'],
        ],
        'error' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'error',
            'url' => env('LOG_ERROR_URL', null),
            'scopes' => false,
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
    ],
];
