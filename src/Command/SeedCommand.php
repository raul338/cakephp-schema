<?php
declare(strict_types=1);

namespace Schema\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Schema\Helper;
use Schema\Table;

class SeedCommand extends Command
{
    /**
     * @var array<string>
     */
    public $tasks = [
        'Schema.SeedGenerate',
    ];

    /**
     * Default configuration.
     *
     * @var array<mixed>
     */
    protected $_config = [
        'connection' => 'default',
        'seed' => 'config/seed.php',
        'truncate' => false,
        'no-interaction' => false,
    ];

    /**
     * @var \Schema\Helper
     */
    protected $helper = null;

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->_config = [
            'connection' => $args->getOption('connection'),
            'seed' => $args->getOption('seed'),
            'truncate' => filter_var($args->getOption('truncate'), FILTER_VALIDATE_BOOLEAN),
            'no-interaction' => filter_var($args->getOption('no-interaction'), FILTER_VALIDATE_BOOLEAN),
        ];

        if (!$this->_config['no-interaction'] && $this->_config['truncate']) {
            $io->out();
            $io->out('<warning>All database tables will be truncated before seeding.</warning>');
            $key = $io->askChoice('Do you want to continue?', ['y', 'n'], 'y');
            if ($key === 'n') {
                return self::CODE_ERROR;
            }
        }
        if (!is_string($this->_config['seed']) || !file_exists($this->_config['seed'])) {
            throw new \InvalidArgumentException(sprintf('Schema file "%s" does not exist.', $this->_config['seed']));
        }

        $this->helper = new Helper();

        $data = $this->_readSeed($this->_config['seed']);

        $db = $this->_connection();

        $this->_truncate($io, $db, $data);
        $this->_insert($io, $db, $data);

        return self::CODE_SUCCESS;
    }

    /**
     * Truncate the tables if requested. Because of postgres it must run in separate transaction.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  array $data List tables and rows.
     * @return void
     */
    protected function _truncate(ConsoleIo $io, Connection $db, array $data): void
    {
        if ($this->_config['truncate']) {
            $io->out('Truncating ', 0);

            $operation = function ($db) use ($data, $io) {
                $db->disableForeignKeys();
                foreach ($data as $table => $rows) {
                    $io->out('.', 0);
                    $this->_truncateTable($db, $table);
                }
                $db->enableForeignKeys();
            };

            $this->_runOperation($db, $operation);
            $io->out(); // New line
        }
    }

    /**
     * Truncates table. Deletes all rows in the table.
     *
     * @param  \Cake\Database\Connection $db Connection where table is stored
     * @param  string $table Table name.
     * @return void
     */
    protected function _truncateTable(Connection $db, string $table): void
    {
        $schema = $db->getSchemaCollection()->describe($table);
        if (!$schema instanceof TableSchema) {
            trigger_error("Table $table did not return \Cake\Database\Schema\TableSchema", E_USER_WARNING);

            return;
        }
        $truncateSql = $schema->truncateSql($db);
        foreach ($truncateSql as $statement) {
            $db->execute($statement)->closeCursor();
        }
    }

    /**
     * Insert data into tables.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param array $data List tables and rows.
     * @return void
     */
    protected function _insert(ConsoleIo $io, Connection $db, array $data)
    {
        $io->out('Seeding ', 0);

        $operation = function ($db) use ($data, $io) {
            $db->disableForeignKeys();
            foreach ($data as $table => $rows) {
                $io->verbose("Seeding table $table", 0);
                $io->out('.', 0);

                $this->_beforeTableInsert($db, $table);
                $this->_insertTable($io, $db, $table, $rows);
                $this->_afterTableInsert($db, $table);

                $io->verbose('');
            }
            $db->enableForeignKeys();
        };

        $this->_runOperation($db, $operation);
        $io->out(); // New line
    }

    /**
     * Insert data into table.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param  \Cake\Database\Connection $db Connection where table is stored.
     * @param  string $table Table name.
     * @param  array $rows Data to be stored.
     * @return void
     */
    protected function _insertTable(ConsoleIo $io, Connection $db, string $table, array $rows): void
    {
        $modelName = \Cake\Utility\Inflector::camelize($table);
        $model = $this->helper->findModel($this->_config['connection'], $modelName, $table);

        foreach ($rows as $row) {
            $query = $model->query();
            $query->insert(array_keys($row))
                ->values($row)
                ->execute();
        }
    }

    /**
     * Runs operation in the SQL transaction with disabled logging.
     *
     * @param  \Cake\Database\Connection $db Connection to run the transaction on.
     * @param  callable $operation Operation to run.
     * @return void
     */
    protected function _runOperation(Connection $db, callable $operation): void
    {
        $logQueries = $db->isQueryLoggingEnabled();
        if ($logQueries) {
            $db->enableQueryLogging(false);
        }

        $db->transactional($operation);

        if ($logQueries) {
            $db->enableQueryLogging(true);
        }
    }

    /**
     * Converts the internal records into data used to generate a query
     * for given table schema.
     *
     * @param \Schema\Table $schema Table schema.
     * @param  array $records Internal records.
     * @return array Fields, values and types.
     */
    protected function _getRecords(Table $schema, $records)
    {
        $fields = $values = $types = [];
        $columns = $schema->columns();
        foreach ($records as $record) {
            $fields = array_merge($fields, array_intersect(array_keys($record), $columns));
        }
        $fields = array_values(array_unique($fields));
        foreach ($fields as $field) {
            $types[$field] = $schema->getColumnType($field . ''); // trick phpstan
        }
        $default = array_fill_keys($fields, null);
        foreach ($records as $record) {
            $values[] = array_merge($default, $record);
        }

        return [$fields, $values, $types];
    }

    /**
     * Prepare table for data insertion.
     *
     * @param \Cake\Database\Connection $db Connection
     * @param string $table Table
     * @return void
     */
    protected function _beforeTableInsert($db, string $table)
    {
        // TODO: Move this into the driver
        if ($db->getDriver() instanceof Sqlserver) {
            $table = $db->quoteIdentifier($table);
            $db->query(sprintf('SET IDENTITY_INSERT %s ON', $table))->closeCursor();
        }
    }

    /**
     * Clean after inserting.
     *
     * @param \Cake\Database\Connection $db Connection
     * @param string $table Table
     * @return void
     */
    protected function _afterTableInsert($db, $table)
    {
        // TODO: Move this into the driver
        if ($db->getDriver() instanceof Sqlserver) {
            $table = $db->quoteIdentifier($table);
            $db->query(sprintf('SET IDENTITY_INSERT %s OFF', $table))->closeCursor();
        }
    }

    /**
     * Returns the database connection.
     *
     * @return \Cake\Database\Connection Object.
     * @throws  \RuntimeException If the connection does not implement schemaCollection()
     */
    protected function _connection(): Connection
    {
        $db = ConnectionManager::get($this->_config['connection'], false);
        if (!$db instanceof Connection) {
            throw new \RuntimeException('Connection must be a subtype of \Cake\Database\Connection');
        }

        return $db;
    }

    /**
     * Returns the data array.
     *
     * @param  string $path Path to the seed.php file.
     * @return array Data array.
     */
    protected function _readSeed(string $path): array
    {
        if (file_exists($path)) {
            $return = include $path;
            if (is_array($return)) {
                return $return;
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addOption('connection', [
                'default' => 'default',
                'short' => 'c',
            ])
            ->addOption('seed', [
                'help' => 'Path to the seed.php file.',
                'short' => 's',
                'default' => 'config/seed.php',
            ])
            ->addOption('truncate', [
                'help' => 'Truncate tables before seeding.',
                'short' => 't',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('no-interaction', [
                'boolean' => true,
                'short' => 'n',
            ]);
    }
}
