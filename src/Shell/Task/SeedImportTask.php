<?php
namespace Schema\Shell\Task;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\File;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use \Exception;
use Schema\Table;

/**
 * @property \Schema\Shell\Task\SeedGenerateTask $SeedGenerate
 */
class SeedImportTask extends Shell
{
    public $tasks = ['Schema.SeedGenerate'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_config = [
        'connection' => 'default',
        'seed' => 'config/seed.php',
        'truncate' => false,
        'no-interaction' => false
    ];

    /**
     * main() method.
     * @param array $options Set connection name and path to the seed.php file.
     * @return bool|int Success or error code.
     */
    public function import(array $options = [])
    {
        $this->_config = array_merge($this->_config, $this->params, $options);

        if (!$this->_config['no-interaction'] && $this->_config['truncate']) {
            $this->_io->out();
            $this->_io->out('<warning>All database tables will be truncated before seeding.</warning>');
            $key = $this->_io->askChoice('Do you want to continue?', ['y', 'n'], 'y');
            if ($key === 'n') {
                return false;
            }
        }
        $this->seed();
    }

    /**
     * Insert data from seed.php file into database.
     *
     * @param array $options Set connection name and path to the seed.php file.
     * @return void
     */
    public function seed($options = [])
    {
        $this->_config = array_merge($this->_config, $this->params, $options);

        $data = $this->_readSeed($this->_config['seed']);

        $db = $this->_connection();

        $this->_truncate($db, $data);
        $this->_insert($db, $data);
    }

    /**
     * Truncate the tables if requested. Because of postgres it must run in separate transaction.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  array $data List tables and rows.
     * @return void
     */
    protected function _truncate($db, $data = null)
    {
        if ($this->_config['truncate']) {
            $this->_io->out('Truncating ', 0);

            $operation = function ($db) use ($data) {
                $db->disableForeignKeys();
                foreach ($data as $table => $rows) {
                    $this->_io->out('.', 0);
                    $this->_truncateTable($db, $table);
                }
                $db->enableForeignKeys();
            };

            $this->_runOperation($db, $operation);
            $this->_io->out(); // New line
        }
    }

    /**
     * Truncates table. Deletes all rows in the table.
     *
     * @param  \Cake\Database\Connection $db Connection where table is stored
     * @param  string $table Table name.
     * @return void
     */
    protected function _truncateTable($db, $table)
    {
        $schema = $db->schemaCollection()->describe($table);
        $truncateSql = $schema->truncateSql($db);
        foreach ($truncateSql as $statement) {
            $db->execute($statement)->closeCursor();
        }
    }

    /**
     * Insert data into tables.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  array $data List tables and rows.
     * @return void
     */
    protected function _insert($db, $data = null)
    {
        $this->_io->out('Seeding ', 0);

        $operation = function ($db) use ($data) {
            $db->disableForeignKeys();
            foreach ($data as $table => $rows) {
                $this->_io->verbose("Seeding table $table", 0);
                $this->_io->out('.', 0);

                $this->_beforeTableInsert($db, $table);
                $this->_insertTable($db, $table, $rows);
                $this->_afterTableInsert($db, $table);

                $this->_io->verbose('');
            }
            $db->enableForeignKeys();
        };

        $this->_runOperation($db, $operation);
        $this->_io->out(); // New line
    }

    /**
     * Insert data into table.
     *
     * @param  \Cake\Database\Connection $db Connection where table is stored.
     * @param  string $table Table name.
     * @param  array $rows Data to be stored.
     * @return void
     */
    protected function _insertTable($db, $table, $rows)
    {
        $modelName = \Cake\Utility\Inflector::camelize($table);
        $model = $this->SeedGenerate->findModel($modelName, $table);

        try {
            foreach ($rows as $row) {
                $query = $model->query();
                $query->insert(array_keys($row))
                    ->values($row)->execute();

                continue;
            }
        } catch (Exception $e) {
            $this->_io->err($e->getMessage());
            exit(1);
        }
    }

    /**
     * Runs operation in the SQL transaction with disabled logging.
     *
     * @param  \Cake\Database\Connection $db Connection to run the transaction on.
     * @param  callable $operation Operation to run.
     * @return void
     */
    protected function _runOperation($db, $operation)
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
            $types[$field] = $schema->getColumnType($field);
        }
        $default = array_fill_keys($fields, null);
        foreach ($records as $record) {
            $values[] = array_merge($default, $record);
        }

        return [$fields, $values, $types];
    }

    /**
     * Prepare table for data insertion.
     * @param \Cake\Database\Connection $db Connection
     * @param \Schema\Table $table Table
     * @return void
     */
    protected function _beforeTableInsert($db, $table)
    {
        // TODO: Move this into the driver
        if ($db->getDriver() instanceof Sqlserver) {
            $table = $db->quoteIdentifier($table);
            $db->query(sprintf('SET IDENTITY_INSERT %s ON', $table))->closeCursor();
        }
    }

    /**
     * Clean after inserting.
     * @param \Cake\Database\Connection $db Connection
     * @param \Schema\Table $table Table
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
    protected function _connection()
    {
        $db = ConnectionManager::get($this->_config['connection'], false);
        if (!method_exists($db, 'schemaCollection')) {
            throw new \RuntimeException(
                'Cannot generate fixtures for connections that do not implement schemaCollection()'
            );
        }

        return $db;
    }

    /**
     * Returns the data array.
     *
     * @param  string $path Path to the seed.php file.
     * @return array Data array.
     */
    protected function _readSeed($path)
    {
        if (file_exists($path)) {
            $return = include $path;
            if (is_array($return)) {
                return $return;
            }
        }

        return [];
    }
}
