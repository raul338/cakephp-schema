<?php
declare(strict_types=1);

namespace Schema\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchema as Schema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Schema\Table;

class SchemaLoadCommand extends Command
{
    /**
     * Default configuration.
     *
     * @var array<mixed>
     */
    protected $_config = [
        'connection' => 'default',
        'no-interaction' => true,
    ];

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
        if ($path === null) {
            $path = CONFIG . 'schema.php';
        }
        if (!is_string($path)) {
            throw new \InvalidArgumentException('`path` option is not a string');
        }
        $data = $this->_readSchema($path);

        $this->_loadTables($io, $data['tables']);
    }

    /**
     * Drop existing tables and load new tables into database.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param  array<array<string,mixed>> $tables List of tables and their fields, indexes, ...
     * @return void
     */
    protected function _loadTables(ConsoleIo $io, array $tables)
    {
        $io->out('Loading the schema from the file ', 0);

        $db = $this->_connection();
        $queries = $this->_generateDropQueries($io, $db);

        if ($queries === false) {
            $io->err(sprintf('<error>Schema was not loaded</error>.'), 2);

            return;
        }

        $tableSchemes = [];

        // Insert tables from the schema.php file
        foreach ($tables as $name => $table) {
            $schema = $this->_schemaFromFields($name, $table);
            $tableSchemes[] = $schema;
            $createSql = $schema->createSql($db);
            $queries = array_merge($queries, $createSql);
        }

        // Add all foreign key constraints
        foreach ($tableSchemes as $table) {
            $table->restoreForeignKeys();
            $foreignKeys = $table->addConstraintSql($db);
            $queries = array_merge($queries, $foreignKeys);
        }

        $this->_execute($io, $db, $queries);

        $io->out(); // New line
        $io->out(sprintf('<success>%d tables were loaded.</success> ', count($tables)));
    }

    /**
     * Returns queries to drop all tables in the database.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  bool $ask Ask question before generating queries. If false reply, no query is generated.
     * @return array<string>|false List of SQL statements dropping tables or false if user stopped the deletion.
     */
    protected function _generateDropQueries(ConsoleIo $io, $db = null, $ask = true)
    {
        if ($db === null) {
            $db = $this->_connection();
        }

        $schemaCollection = $db->getSchemaCollection();
        $tables = $schemaCollection->listTables();

        if (count($tables) > 0 && !$this->_config['no-interaction']) {
            $io->out();
            $io->out(sprintf(
                '<warning>Database is not empty. %d tables will be deleted.</warning>',
                count($tables)
            ));
            $key = $io->askChoice('Do you want to continue?', ['y', 'n'], 'y');
            if ($key === 'n') {
                return false;
            }
        }

        $dropForeignKeys = [];
        $dropTables = [];

        foreach ($tables as $tableName) {
            $io->out('.', 0);
            $table = $schemaCollection->describe($tableName);
            if (!$table instanceof TableSchema) {
                trigger_error("Table $tableName did not return \Cake\Database\Schema\TableSchema", E_USER_WARNING);
                continue;
            }

            $dropKeys = $this->_generateDropForeignKeys($db, $table);
            $dropForeignKeys = array_merge($dropForeignKeys, $dropKeys);

            $dropSql = $table->dropSql($db);
            $dropTables = array_merge($dropTables, $dropSql);
        }

        return array_merge($dropForeignKeys, $dropTables);
    }

    /**
     * Executes list of quries in one transaction.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param \Cake\Datasource\ConnectionInterface $db Connection to run the SQL queries on.
     * @param  array<string> $queries List of SQL statements.
     * @return void
     */
    protected function _execute(ConsoleIo $io, $db, array $queries = []): void
    {
        $logQueries = $db->isQueryLoggingEnabled();
        if ($logQueries) {
            $db->enableQueryLogging(false);
        }

        $db->transactional(function ($db) use ($queries, $io) {
            $db->disableForeignKeys();
            foreach ($queries as $query) {
                $io->out('.', 0);
                $db->execute($query)->closeCursor();
            }
            $db->enableForeignKeys();
        });

        if ($logQueries) {
            $db->enableQueryLogging(true);
        }
    }

    /**
     * Generates SQL statements dropping foreign keys for the table.
     *
     * @param \Cake\Datasource\ConnectionInterface $db Connection to run the SQL queries on.
     * @param  \Cake\Database\Schema\TableSchema $table Drop foreign keys for this table.
     * @return array<string> List of SQL statements dropping foreign keys.
     */
    protected function _generateDropForeignKeys(ConnectionInterface $db, Schema $table): array
    {
        $type = 'other';
        if ($db->getDriver() instanceof Mysql) {
            $type = 'mysql';
        }

        $queries = [];
        foreach ($table->constraints() as $constraintName) {
            $constraint = $table->getConstraint($constraintName);
            if ($constraint && $constraint['type'] === Schema::CONSTRAINT_FOREIGN) {
                // TODO: Move this into the driver
                if ($type === 'mysql') {
                    $template = 'ALTER TABLE %s DROP FOREIGN KEY %s';
                } else {
                    $template = 'ALTER TABLE %s DROP CONSTRAINT %s';
                }
                $queries[] = sprintf($template, $table->name(), $constraintName);
            }
        }

        return $queries;
    }

    /**
     * Returns the database connection.
     *
     * @return \Cake\Database\Connection Object.
     * @throws \RuntimeException on unsupported connections
     */
    protected function _connection(): Connection
    {
        $connection = ConnectionManager::get($this->_config['connection'], false);
        if (!$connection instanceof Connection) {
            throw new \RuntimeException('Connection must be of subtype Connection');
        }

        return $connection;
    }

    /**
     * Returns the schema array.
     *
     * @param  string $path Path to the schema.php file.
     * @return array<array<string,mixed>> Schema array.
     */
    protected function _readSchema($path): array
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('Schema file "%s" does not exist.', $path));
        }

        $return = include $path;
        if (is_array($return)) {
            return $return;
        }

        throw new \RuntimeException(sprintf('Schema file "%s" did not return an array.', $path));
    }

    /**
     * Build the fixtures table schema from the fields property.
     *
     * @param  string $tableName Name of the table.
     * @param  array<string,mixed> $fields Fields saved into the schema.php file.
     * @return \Schema\Table Table schema
     */
    protected function _schemaFromFields(string $tableName, array $fields): Table
    {
        $schema = new Table($tableName);
        foreach ($fields as $field => $data) {
            if ($field === '_constraints' || $field === '_indexes' || $field === '_options') {
                continue;
            }
            $schema->addColumn($field, $data);
        }
        if (!empty($fields['_constraints'])) {
            foreach ($fields['_constraints'] as $name => $data) {
                $schema->addConstraint($name, $data);
            }
        }
        if (!empty($fields['_indexes'])) {
            foreach ($fields['_indexes'] as $name => $data) {
                $schema->addIndex($name, $data);
            }
        }
        if (!empty($fields['_options'])) {
            $schema->setOptions($fields['_options']);
        }

        return $schema;
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
            ->addOption('path', [
                'help' => 'defaults to CONFIG . "schema.php"',
            ])
            ->addOption('no-interaction', [
                'boolean' => true,
                'short' => 'n',
            ]);
    }
}
