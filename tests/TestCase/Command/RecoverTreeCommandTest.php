<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Schema\Command\RecoverTreeCommand
 */
class RecoverTreeCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use UtilitiesTrait;

    public function testRecoverTreesCommand(): void
    {
        $this->dropTables();
        $this->runMigrations();
        $table = TableRegistry::getTableLocator()->get('Items');

        $items = [
            [
                'id' => 1,
                'name' => 'Item A',
            ],
            [
                'id' => 2,
                'name' => 'Item A.1',
                'parent_id' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Item A.2',
                'parent_id' => 1,
            ],
        ];
        foreach ($items as $item) {
            $table->query()
                ->insert(array_keys($item))
                ->values($item)
                ->execute();
        }
        $this->assertSame(3, $table->query()->count());

        $this->useCommandRunner();
        $this->exec('recover_tree -c test Items -v');
        $this->assertExitSuccess();

        $this->assertSame(0, $table->query()->where(['lft IS' => null])->count(), 'recover() was not called');
    }
}
