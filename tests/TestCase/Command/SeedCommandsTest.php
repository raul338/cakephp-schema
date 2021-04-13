<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * Class SeedCommandsTest
 *
 * @uses \Schema\Command\SeedGenerateCommand
 * @uses \Schema\Command\SeedCommand
 */
class SeedCommandsTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    public $schemaFile = TESTS . 'files' . DS . 'schema.php';

    /**
     * @var string
     */
    public $seedFile = TESTS . 'files' . DS . 'seed.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();

        if (file_exists(CONFIG . 'schema.php')) {
            unlink(CONFIG . 'schema.php');
        }
        $this->exec('schema load -n -c test --path ' . $this->schemaFile);
        $this->cleanupConsoleTrait();
        $this->useCommandRunner();
    }

    public function testSeedGenerate(): void
    {
        $profiles = TableRegistry::getTableLocator()->get('profiles');
        $profile = $profiles->newEntity(['name' => 'admin']);
        $profiles->saveOrFail($profile);

        $this->exec('schema generateseed -c test --path ' . $this->schemaFile, ['y']);
        $this->assertOutputContains('seed');
        $this->assertExitSuccess();

        $this->assertFileExists(CONFIG . 'seed.php');
        $seed = require CONFIG . 'seed.php';

        $this->assertSame($profile->id, Hash::get($seed, 'profiles.0.id'), 'Seed should contain same ids');
    }

    public function testSeed(): void
    {
        $this->exec('schema seed -c test -t --seed ' . $this->seedFile, ['y']);
        $this->assertExitSuccess();

        $profiles = TableRegistry::getTableLocator()->get('profiles');
        $profile = $profiles->get(1);
        $seed = require CONFIG . 'seed.php';

        $this->assertSame($profile->get('name'), Hash::get($seed, 'profiles.0.name'), 'Created entity must have same name');
    }
}
