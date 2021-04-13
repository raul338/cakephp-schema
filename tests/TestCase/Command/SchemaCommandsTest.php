<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;

/**
 * Class SchemaSaveCommandTest
 *
 * @uses \Schema\Command\SchemaSaveCommand
 */
class SchemaCommandsTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
        $this->dropTables();
    }

    protected function dropTables(): void
    {
        $conn = ConnectionManager::get('default');
        $conn->execute('DROP TABLE IF EXISTS `users`');
        $conn->execute('DROP TABLE IF EXISTS `profiles`');
        $conn->execute('DROP TABLE IF EXISTS `phinxlog`');
    }

    public function testSchemaSave(): void
    {
        if (file_exists(CONFIG . 'schema.php')) {
            unlink(CONFIG . 'schema.php');
        }
        $migration = new Migrations();
        $migration->migrate(['connection' => 'test']);
        $this->exec('schema save -c test');
        $this->assertExitSuccess();
        $this->assertFileExists(CONFIG . 'schema.php', 'Schema file not generated');
        $this->assertFileEquals(TESTS . 'files/schema.php', CONFIG . 'schema.php');
    }

    /**
     * @depends testSchemaSave
     */
    public function testSchemaSaveOverwriteFile(): void
    {
        $this->testSchemaSave();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
        $this->exec('schema save -c test', ['y']);
        $this->assertExitSuccess();
        $this->assertFileExists(CONFIG . 'schema.php', 'Schema file not generated');
    }

    /**
     * @depends testSchemaSave
     */
    public function testSchemaDrop(): void
    {
        $this->testSchemaSave();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
        $this->exec('schema drop -c test', ['y']);
        $this->assertExitSuccess();
    }

    /**
     * @depends testSchemaDrop
     */
    public function testSchemaLoad(): void
    {
        $this->testSchemaDrop();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
        $this->exec('schema load -c test', ['y']);
        $this->assertExitSuccess();
    }

    /**
     * @depends testSchemaSave
     * @depends testSchemaDrop
     */
    public function testLoadFixturesFromSchemaFile(): void
    {
        // build schema.php
        $this->testSchemaSave();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();

        // clean db
        $this->testSchemaDrop();
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();

        // action
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
        $this->exec('schema load -c test --path ' . TESTS . 'files/schema.php', ['y']);
        $this->assertExitSuccess();

        // Asserts
        $profiles = TableRegistry::getTableLocator()->get('Profiles')->find('all')->toArray();
        $this->assertIsArray($profiles);
    }
}
