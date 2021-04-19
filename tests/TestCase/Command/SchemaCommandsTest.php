<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\Database\Exception;
use Cake\Database\Exception\DatabaseException;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Migrations\Migrations;

/**
 * Class SchemaCommandsTest
 *
 * @uses \Schema\Command\SchemaSaveCommand
 * @uses \Schema\Command\SchemaLoadCommand
 * @uses \Schema\Command\SchemaDropCommand
 */
class SchemaCommandsTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use UtilitiesTrait;

    public $autoFixtures = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
        $this->dropTables();
    }

    public function testSchemaSave(): void
    {
        $this->runMigrations();
        $this->exec('schema save -c test');
        $this->assertExitSuccess();
        $this->assertValidSchemaFile();
    }

    public function testSchemaSaveOverwriteFile(): void
    {
        $this->runMigrations();
        $this->exec('schema save -c test', ['y']);
        $this->assertExitSuccess();
        $this->assertValidSchemaFile();
    }

    protected function assertValidSchemaFile(): void
    {
        $this->assertFileExists(CONFIG . 'schema.php', 'Schema file not generated');
        $content = require CONFIG . 'schema.php';
        $this->assertIsArray($content);
        $tables = Hash::get($content, 'tables');
        $this->assertIsArray($tables);
        $this->assertArrayHasKey('profiles', $tables);
        $this->assertArrayHasKey('users', $tables);
    }

    protected function callDropCommand(): void
    {
        $this->testSchemaSave();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
        $this->exec('schema drop -c test', ['y']);
        $this->assertExitSuccess();
    }

    /**
     * @depends testSchemaSave
     */
    public function testSchemaDrop(): void
    {
        $this->callDropCommand();

        $this->expectException(
            class_exists(DatabaseException::class)
                ? DatabaseException::class
                : Exception::class
        );
        $table = TableRegistry::getTableLocator()->get('Profiles');
        $table->find()->toArray();
    }

    /**
     * @depends testSchemaDrop
     */
    public function testSchemaLoad(): void
    {
        $this->callDropCommand();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
        $this->exec('schema load -c test', ['y']);
        $this->assertExitSuccess();

        $table = TableRegistry::getTableLocator()->get('Profiles');
        $this->assertIsArray($table->find()->toArray());
    }

    /**
     * @depends testSchemaSave
     */
    public function testFixturesWorking(): void
    {
        $this->testSchemaSave();
        $this->dropTables();
        if ($this->fixtureManager === null) {
            $this->markAsRisky();

            return;
        }
        $this->fixtures = [
            'app.Profiles',
            'app.Users',
        ];
        $this->fixtureManager->fixturize($this);
        $this->loadFixtures();
        $profiles = TableRegistry::getTableLocator()->get('Profiles');
        $query = $profiles->find();
        $this->assertSame($query->count(), 1, 'Profile data not loaded from seed');
        $profile = $query->first();
        $this->assertNotNull($profile, 'profile should not be null');
        $this->assertInstanceOf(Entity::class, $profile);
        $this->assertSame($profile->get('name'), 'admin');
    }
}
