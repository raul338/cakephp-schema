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
        $this->exec('schema save -f -c test');
        $this->assertExitSuccess();
        $this->assertFileExists(CONFIG . 'schema.php', 'Schema file not generated');
    }

    /**
     * @depends testSchemaSave
     */
    public function testSchemaDrop(): void
    {
        $this->testSchemaSave();
        $this->exec('schema drop -n -c test');
        $this->assertExitSuccess();
    }

    /**
     * @depends testSchemaSave
     */
    public function testSchemaLoad(): void
    {
        $this->testSchemaSave();
        $this->exec('schema load -n -c test');
        $this->assertExitSuccess();
    }
}
