<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\Datasource\ConnectionManager;

trait UtilitiesTrait
{
    protected function dropTables(): void
    {
        $conn = ConnectionManager::get('default');
        $conn->execute('DROP TABLE IF EXISTS `users`');
        $conn->execute('DROP TABLE IF EXISTS `profiles`');
        $conn->execute('DROP TABLE IF EXISTS `phinxlog`');
    }
}
