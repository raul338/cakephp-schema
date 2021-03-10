<?php
declare(strict_types=1);

namespace Schema;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Schema\Command\SchemaDropCommand;
use Schema\Command\SchemaLoadCommand;
use Schema\Command\SchemaSaveCommand;
use Schema\Command\SeedGenerateCommand;

class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('schema save', SchemaSaveCommand::class);
        $commands->add('schema drop', SchemaDropCommand::class);
        $commands->add('schema load', SchemaLoadCommand::class);
        $commands->add('seed generate', SeedGenerateCommand::class);

        return parent::console($commands);
    }
}
