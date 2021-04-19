<?php
declare(strict_types=1);

namespace Schema\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

class RecoverTreeCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Recover table tree');
        $parser
            ->addArgument('table', [
                'help' => '',
            ])
            ->addOption('connection', [
                'help' => 'Connection name',
                'short' => 'c',
                'default' => 'default',
            ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $connection = $args->getOption('connection');
        if ($connection !== 'default' && is_string($connection) && $connection) {
            ConnectionManager::drop('default');
            ConnectionManager::setConfig('default', ConnectionManager::getConfig('test'));
        }
        $tableName = $args->getArgument('table');
        if ($tableName === null) {
            throw new \InvalidArgumentException('Table name is required');
        }
        $table = TableRegistry::getTableLocator()->get($tableName);
        if (!$table->hasBehavior('Tree')) {
            $io->err(sprintf('Table `%s` does not use TreeBehavior', $tableName));

            return self::CODE_ERROR;
        }
        $table->recover();
        $io->verbose('done');

        return self::CODE_SUCCESS;
    }
}
