<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;

class SchemaFixtureTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use UtilitiesTrait;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * @var string[]
     */
    public $fixtures = [
        'app.Profiles',
        'app.Users',
    ];

    protected function buildSchema(): void
    {
        $this->dropTables();
        if (file_exists(CONFIG . 'schema.php')) {
            unlink(CONFIG . 'schema.php');
        }
        $migration = new Migrations();
        $migration->migrate(['connection' => 'test']);
        $this->useCommandRunner();
        $this->exec('schema save -c test');
        $this->dropTables();
    }

    public function testFixturesWorking(): void
    {
        $this->buildSchema();
        $this->loadFixtures('Profiles', 'Users');
        $profiles = TableRegistry::getTableLocator()->get('Profiles');
        $query = $profiles->find();
        $this->assertSame($query->count(), 1, 'Profile data not loaded from seed');
        $profile = $query->first();
        $this->assertSame($profile->get('name'), 'admin');
    }
}
