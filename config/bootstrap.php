<?php

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Schema\Command\SchemaSaveCommand;

$configKey = 'Schema.autoSaveSchemaAfterMigrate';

if (!Configure::check($configKey) || Configure::read($configKey) === true) {
    EventManager::instance()->on('Migration.afterMigrate', function (Event $event) {
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
