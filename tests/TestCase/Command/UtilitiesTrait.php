<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\Datasource\ConnectionManager;
use Migrations\Migrations;

trait UtilitiesTrait
{
    protected function dropTables(): void
    {
        $conn = ConnectionManager::get('default');
        /** @var \Cake\Database\Driver $driver */
        $driver = $conn->getDriver();
        // $conn->getDriver()->quoteIdentifier('users');
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('users'));
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('profiles'));
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('items'));
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('phinxlog'));
    }

    public function runMigrations(): void
    {
        if (file_exists(CONFIG . 'schema.php')) {
            unlink(CONFIG . 'schema.php');
        }
        $migration = new Migrations();
        $migration->migrate(['connection' => 'test']);
    }
}
