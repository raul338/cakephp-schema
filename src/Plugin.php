<?php
declare(strict_types=1);

namespace Schema;

use Cake\Console\Arguments;
use Cake\Console\CommandCollection;
use Cake\Console\ConsoleIo;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Schema\Command\RecoverTreeCommand;
use Schema\Command\SchemaDropCommand;
use Schema\Command\SchemaLoadCommand;
use Schema\Command\SchemaSaveCommand;
use Schema\Command\SeedCommand;
use Schema\Command\SeedGenerateCommand;

class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        $configKey = 'Schema.autoSaveSchemaAfterMigrate';

        if (!Configure::check($configKey) || Configure::read($configKey) === true) {
            EventManager::instance()->on('Migration.afterMigrate', function (EventInterface $event) {
                /** @var \Migrations\Command\Phinx\Migrate $migration  */
                $migration = $event->getSubject();
                $manager = $migration->getManager();
                if ($manager == null) {
                    return;
                }
                $input = $manager->getInput();
                $connectionName = $input->getOption('connection');

                $command = new SchemaSaveCommand();
                $io = new ConsoleIo();
                $args = new Arguments([], [
                    'interactive' => false,
                    'connection' => $connectionName ?: 'default',
                ], []);
                $command->execute($args, $io);
            });
        }
    }

    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('schema save', SchemaSaveCommand::class);
        $commands->add('schema drop', SchemaDropCommand::class);
        $commands->add('schema load', SchemaLoadCommand::class);
        $commands->add('schema generateseed', SeedGenerateCommand::class);
        $commands->add('schema seed', SeedCommand::class);

        $commands->add('recover_tree', RecoverTreeCommand::class);

        return parent::console($commands);
    }
}
