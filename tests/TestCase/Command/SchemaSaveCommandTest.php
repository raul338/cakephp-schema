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
class SchemaSaveCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }

    public function testExecute(): void
    {
        $migration = new Migrations();
        $migration->migrate(['connection' => 'test']);
        $this->exec('schema save -n -c test');
        $this->assertOutputContains('a');
        $this->assertExitSuccess();
        $this->assertFileExists(CONFIG . 'schema.php', 'Schema file not generated');
    }
}
