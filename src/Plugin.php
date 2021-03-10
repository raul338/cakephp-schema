<?php
declare(strict_types=1);

namespace Schema;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Schema\Command\SchemaSaveCommand;

class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('schema save', SchemaSaveCommand::class);

        return parent::console($commands);
    }
}
