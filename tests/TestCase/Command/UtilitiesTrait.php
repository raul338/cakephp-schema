<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\Datasource\ConnectionManager;

trait UtilitiesTrait
{
    protected function dropTables(): void
    {
        $conn = ConnectionManager::get('default');
        $driver = $conn->getDriver()->quoteIdentifier('users');l
        // $conn->getDriver()->quoteIdentifier('users');
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('users'));
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('profiles'));
        $conn->execute('DROP TABLE IF EXISTS ' . $driver->quoteIdentifier('phinxlog')));
    }
}
