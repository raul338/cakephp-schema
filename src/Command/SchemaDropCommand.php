<?php
declare(strict_types=1);

namespace Schema\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class SchemaDropCommand extends SchemaLoadCommand
{
    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->_config = [
            'connection' => $args->getOption('connection'),
            'no-interaction' => $args->getOption('no-interaction'),
        ];

        $path = $args->getOption('path');
        if (!is_string($path)) {
            throw new \InvalidArgumentException('`path` option is not a string');
        }
        $data = $this->_readSchema($path);
        $queries = $this->_generateDropQueries($io);
        if ($queries === false) {
            $io->err(sprintf('<error>Deletion was terminated by the user.</error>.'), 2);

            return;
        }
        $this->_execute($io, $this->_connection(), $queries);
        $io->out(); // New line
        $io->out(sprintf('<success>%d queries were executed.</success> ', count($queries)));
    }
}
