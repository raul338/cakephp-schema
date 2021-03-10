<?php

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Schema\Shell\Task\SchemaSaveTask;

$configKey = 'Schema.autoSaveSchemaAfterMigrate';

if (!Configure::check($configKey) || Configure::read($configKey) === true) {
    EventManager::instance()->on('Migration.afterMigrate', function (Event $event) {
        /** @var \Migrations\Command\Migrate $migration */
        $migration = $event->getSubject();
        $manager = $migration->getManager();
        if ($manager == null) {
            return;
        }
        $input = $manager->getInput();
        $connectionName = $input->getOption('connection');

        $task = new SchemaSaveTask();
        $task->interactive = false;
        $task->initialize();
        $task->loadTasks();
        $task->save([
            'connection' => $connectionName ?: 'default',
        ]);
    });
}
