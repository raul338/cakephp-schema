<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Migrations\Migrations;
use PHPUnit\Framework\TestCase;

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
        $this->exec('schema load -n -c test');
        $this->assertExitSuccess();
    }
}
