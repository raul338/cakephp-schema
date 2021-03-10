<?php
declare(strict_types=1);

namespace Schema;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;

class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return parent::console($commands);
    }
}
